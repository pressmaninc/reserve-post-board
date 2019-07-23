<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Reserve_Post_Board
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// copy .mo file
	$tmp_dir   = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress/wp-content/plugins/reserve-post-board/languages';
	$src_dir   = dirname( __DIR__ ) . '/languages';
	$lang_file = '/reserve-post-board-ja.mo';

	if ( ! file_exists( $tmp_dir ) ) {
		mkdir( $tmp_dir, 0777, true );
	}

	copy( $src_dir . $lang_file, $tmp_dir . $lang_file );

	require dirname( dirname( __FILE__ ) ) . '/reserve-post-board.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

tests_add_filter( 'locale', function ( $locale ) {
	return 'ja';
} );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
