<?php
/*
Plugin Name: Reserve Post Board
Description: Reserve Post in Dash Board
Version: 1.0
Author: PRESSMAN
Author URI: https://www.pressman.ne.jp/
copyright: Copyright (c) 2018, PRESSMAN
license: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, v2 or higher
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class
 **/
class Reserve_Post_Board {
	/**
	 * Initialization
	 **/
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_stylesheet' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'widgets' ) );
		add_action( 'admin_init', array( $this, 'text_domain' ) );
	}

	/**
	 * Languages registry
	 **/
	public function text_domain() {
		load_plugin_textdomain( 'reserve-post-board', false, basename( __DIR__ ) . '/languages' );
	}

	/**
	 * Stylesheet registry
	 **/
	public function add_stylesheet() {
		if ( get_current_screen()->id == 'dashboard' ) {
			wp_register_style( 'rpb_css', plugins_url( '/css/style.css', __FILE__ ), array(), null );
			wp_enqueue_style( 'rpb_css' );
		}
	}

	/**
	 * Widgets registry
	 **/
	public function widgets() {
		wp_add_dashboard_widget(
			'reserve_post_board',
			__( 'Reserve Post Info', 'reserve-post-board' ),
			array( $this, 'reserve_post_board' )
		);
	}

	/**
	 * Process of view
	 **/
	public function reserve_post_board() {
		$limit     = apply_filters( 'rbp_list_limit', 10 );
		$hour      = apply_filters( 'rbp_publishing_soon_period', 24 );
		$post_type = apply_filters( 'rbp_target_post_type', array( 'any' ) );

		$query = array(
			'post_type'      => $post_type,
			'post_status'    => array( 'future' ),
			'orderby'        => 'post_date',
			'order'          => 'ASC',
			'posts_per_page' => - 1
		);

		$all_feature_posts = new WP_Query( $query );
		$display_limit     = min( $all_feature_posts->post_count, $limit );
		wp_reset_postdata();

		$query['date_query'] = array(
			array(
				'compare'   => '<=',
				'inclusive' => true,
				'before'    => "+{$hour} hours",
			)
		);

		$within_feature_posts = new WP_Query( $query );
		?>
		<p class="rpb_publishing_soon">
			<?php echo sprintf( __( 'Publishing Soon (within %1$sh) <span>%2$s</span> post', 'reserve-post-board' ), $hour, $within_feature_posts->post_count ); ?>
		</p>
		<?php if ( ! $all_feature_posts->have_posts() ) : ?>
			<b><?php echo __( 'No reserved posts found.', 'reserve-post-board' ); ?></b>
		<?php else: ?>
			<b><?php echo sprintf( __( 'List of reserved post (%1$s of %2$s)', 'reserve-post-board' ), $display_limit, $all_feature_posts->post_count ); ?></b>
			<div id="rpb_future_posts">
				<ul>
					<?php for ( $i = 0; $i < $display_limit; $i ++ ) : ?>
						<?php
						$all_feature_posts->the_post();

						$title     = get_the_title();
						$ID        = get_the_ID();
						$post_type = get_post_type();
						$link      = "<a href='" . admin_url() . "post.php?post=" . $ID . "&action=edit'>" . $title . "</a>";
						$date_txt  = get_the_date() . ' ' . get_the_time();
						$today     = ( get_the_date() == date_i18n( get_option( 'date_format' ) ) ) ? ' today' : '';
						?>
						<li>
							<span class="rpb_date<?php echo $today; ?>"><?php echo $date_txt; ?></span>
							<span class="rpb_title"><?php echo $link; ?></span>
							<span class="rpb_post_type">(<?php echo get_post_type_object( $post_type )->label; ?>)</span>
						</li>
					<?php endfor; ?>
				</ul>
			</div>
		<?php
		endif;
		wp_reset_postdata();
	}//function reserve_post_board
}

$GLOBALS['reserve_post_board'] = new Reserve_Post_Board();
