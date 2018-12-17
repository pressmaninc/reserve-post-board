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
	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_stylesheet' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'widgets' ) );
		add_action( 'admin_init', array( $this, 'text_domain' ) );
	}

	/**
	 * Languages registry
	 **/
	public function text_domain() {
		load_plugin_textdomain( 'reserve-post-board', false, plugin_basename( plugin_dir_path( __FILE__ ) ) . '/languages' );
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

		$query      = array(
			'post_type'   => $post_type,
			'post_status' => array( 'future' )
		);
		$all_result = new WP_Query( $query );
		$all_cnt    = $all_result->post_count;
		wp_reset_postdata();

		$query       = array(
			'post_type'      => $post_type,
			'post_status'    => array( 'future' ),
			'orderby'        => 'post_date',
			'order'          => 'ASC',
			'posts_per_page' => $limit
		);
		$list_result = new WP_Query( $query );
		$list_cnt    = $list_result->post_count;

		$before = date_i18n( 'Y-m-d H:i:s', strtotime( date_i18n( 'Y-m-d 23:59:59' ) . '+24 hour' ) );
		$after  = date_i18n( 'Y-m-d 00:00:00' );

		$query = array(
			'post_type'      => $post_type,
			'post_status'    => array( 'future' ),
			'orderby'        => 'post_date',
			'order'          => 'ASC',
			'posts_per_page' => $limit,
			'date_query'     => array(
				array(
					'compare'   => 'BETWEEN',
					'inclusive' => true,
					'before'    => $before,
					'after'     => $after
				)
			)
		);


		$within_result            = new WP_Query( $query );
		$within_reserve_posts_cnt = $within_result->post_count;


		?>
		<p class="rpb_publishing_soon">
			<?php echo sprintf( __( 'Publishing Soon (within %sh) <span>%s</span> post', 'reserve-post-board' ), $hour, $within_reserve_posts_cnt ); ?>
		</p>
		<?php
		if ( $list_cnt > 0 ) {
			?>
			<b>
				<?php echo sprintf( __( 'List of reserved post (%s of %s)', 'reserve-post-board' ), $all_cnt, $list_cnt ); ?>
				&nbsp;
			</b>
			<?php
		} else {
			?>
			<b>
				<?php echo __( 'No reserved posts found.', 'reserve-post-board' ); ?>
				&nbsp;
			</b>
			<?php
		}
		if ( $list_cnt > 0 ) {
			?>

			<div id="rpb_future_posts">
				<ul>
					<?php
					if ( $list_result->have_posts() ) {
						while ( $list_result->have_posts() ) {
							$list_result->the_post();

							$title     = get_the_title();
							$ID        = get_the_ID();
							$post_type = $list_result->post->post_type;
							$link      = "<a href='" . admin_url() . "post.php?post=" . $ID . "&action=edit'>" . $title . "</a>";

							$date_txt = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $list_result->post->post_date ) );

							$today = ( date_i18n( 'Ymd', strtotime( $list_result->post->post_date ) ) == date_i18n( 'Ymd' ) ) ? ' today' : '';
							?>

							<li>
								<span class="rpb_date<?php echo $today; ?>"><?php echo $date_txt; ?></span>
								<span class="rpb_title"><?php echo $link; ?></span>
								<span class="rpb_post_type">(<?php echo get_post_type_object( $post_type )->label; ?>)</span>
							</li>
							<?php

						}
					}
					?>
				</ul>
			</div>
			<?php
			wp_reset_postdata();
		}


	}//function reserve_post_board
}

new Reserve_Post_Board();

