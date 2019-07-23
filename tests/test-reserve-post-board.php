<?php
/**
 * Class Reserve_Post_Board_Test
 *
 * @package Reserve_Post_Board
 */

class Reserve_Post_Board_Test extends WP_UnitTestCase {

	/** @var Reserve_Post_Board */
	private static $reserve_post_board;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		global $reserve_post_board;
		self::$reserve_post_board = $reserve_post_board;
		update_option( 'timezone_string', 'Asia/Tokyo', 'yes' );
		update_option( 'date_format', 'Y-m-d', 'yes' );
		update_option( 'time_format', 'H:i', 'yes' );
	}

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
	}

	############################################################################

	/**
	 * @covers Reserve_Post_Board::__construct
	 */
	public function test____construct() {
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', [ self::$reserve_post_board, 'add_stylesheet' ] ) );
		$this->assertNotFalse( has_action( 'wp_dashboard_setup', [ self::$reserve_post_board, 'widgets' ] ) );
		$this->assertNotFalse( has_action( 'admin_init', [ self::$reserve_post_board, 'text_domain' ] ) );
	}

	/**
	 * @covers Reserve_Post_Board::text_domain
	 */
	public function test__text_domain() {
		self::$reserve_post_board->text_domain();
		global $l10n;
		$this->assertArrayHasKey( 'reserve-post-board', $l10n );
		$this->assertEquals( '予約投稿情報', __( 'Reserve Post Info', 'reserve-post-board' ) );
	}

	/**
	 * @covers Reserve_Post_Board::add_stylesheet
	 */
	public function test__add_stylesheet() {
		set_current_screen( 'tools' );
		self::$reserve_post_board->add_stylesheet();
		$this->assertFalse( wp_style_is( 'rpb_css' ) );

		set_current_screen( 'dashboard' );
		self::$reserve_post_board->add_stylesheet();
		$this->assertTrue( wp_style_is( 'rpb_css', 'enqueued' ) );
	}

	/**
	 * @covers Reserve_Post_Board::widgets
	 */
	public function test__widgets() {
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';
		set_current_screen( 'dashboard' );
		self::$reserve_post_board->widgets();

		global $wp_meta_boxes;
		$this->assertArrayHasKey( 'reserve_post_board', $wp_meta_boxes['dashboard']['normal']['core'] );

		$meta_box = $wp_meta_boxes['dashboard']['normal']['core']['reserve_post_board'];
		$this->assertEquals( 'reserve_post_board', $meta_box['id'] );
		$this->assertEquals( __( 'Reserve Post Info', 'reserve-post-board' ), $meta_box['title'] );
		$this->assertEquals( [ self::$reserve_post_board, 'reserve_post_board' ], $meta_box['callback'] );
	}

	/**
	 * @covers Reserve_Post_Board::reserve_post_board
	 */
	public function test__reserve_post_board() {
		// post is empty.
		ob_start();
		self::$reserve_post_board->reserve_post_board();
		$actual   = ob_get_clean();
		$expected = '<p class="rpb_publishing_soon">直近の公開 (24時間以内) <span>0 件</span></p><b>予約投稿がありません。</b>';
		$this->assertEquals( $expected, str_replace( [ "\n", "\t" ], '', $actual ) );

		// posts exists
		$posts = [];

		for ( $i = 1; $i < 16; $i ++ ) {
			$h = $i * 1;
			// $time = 1563375600 + (60 * 60 * $h) + 32400 + 32400;
			$posts[] = $this->factory()->post->create_and_get( [
				'post_title'  => "Post #{$i}",
				'post_status' => 'future',
				'post_date'   => date_i18n( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( 60 * 60 * $h ) )
			] );
		}

		add_filter( 'rbp_list_limit', function ( $limit ) {
			return 5;
		} );

		add_filter( 'rbp_publishing_soon_period', function ( $hour ) {
			return 10;
		} );

		ob_start();
		self::$reserve_post_board->reserve_post_board();
		$actual = ob_get_clean();

		$doc = new DOMDocument();
		$doc->loadHTML( mb_convert_encoding( $actual, 'HTML-ENTITIES', 'utf-8' ), LIBXML_NOERROR );

		$p = $doc->getElementsByTagName( 'p' )[0];
		$this->assertEquals( '直近の公開 (10時間以内) 10 件', trim( $p->textContent ) );
		$this->assertEquals( 'rpb_publishing_soon', $p->getAttribute( 'class' ) );

		$b = $doc->getElementsByTagName( 'b' )[0];
		$this->assertEquals( '公開予約された記事 (15件中 5件を表示)', trim( $b->textContent ) );

		$div = $doc->getElementById( 'rpb_future_posts' );
		$this->assertNotNull( $div );

		$this->assertNotNull( $div->getElementsByTagName( 'ul' ) );
		$ul = $div->getElementsByTagName( 'ul' )[0];

		$this->assertNotNull( $ul->getElementsByTagName( 'li' ) );
		$i = 0;

		foreach ( $ul->getElementsByTagname( 'li' ) as $li ) {
			$this->assertEquals( 3, $li->getElementsByTagName( 'span' )->length );
			$spans = $li->getElementsByTagName( 'span' );

			$this->assertNotFalse( strpos( $spans[0]->getAttribute( 'class' ), 'rpb_date' ) );
			$this->assertNotFalse( strpos( $posts[ $i ]->post_date, $spans[0]->textContent ) );

			if ( false !== strpos( $spans[0]->textContent, date_i18n( get_option( 'date_format' ) ) ) ) {
				$this->assertNotFalse( strpos( $spans[0]->getAttribute( 'class' ), 'today' ) );
			}

			$this->assertEquals( 'rpb_title', $spans[1]->getAttribute( 'class' ) );
			$this->assertNotNull( $spans[1]->getElementsByTagName( 'a' ) );
			$a = $spans[1]->getElementsByTagName( 'a' )[0];
			$this->assertEquals( admin_url() . "post.php?post={$posts[$i]->ID}&action=edit", $a->getAttribute( 'href' ) );
			$this->assertEquals( $posts[ $i ]->post_title, trim( $a->textContent ) );

			$this->assertEquals( 'rpb_post_type', $spans[2]->getAttribute( 'class' ) );
			$this->assertEquals( '(Posts)', trim( $spans[2]->textContent ) );

			$i ++;
		}

		// file_put_contents( __DIR__ . '/out.html', '<div>' . date_i18n( 'Y-m-d H:i:s' ) . '</div><div>' . date_i18n( 'Y-m-d H:i:s', strtotime( ' +1 hours' ), true ) . '</div>' . str_replace( [ "\n", "\t" ], '', $actual ) );
	}
}
