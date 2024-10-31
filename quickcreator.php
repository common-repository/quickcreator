<?php
/**
 * Plugin Name: Quickcreator
 * Plugin URI: https://wordpress.org/plugins/quickcreator/
 * Description: Create post with Quickcreator in WordPress
 * Version: 0.1.1
 * Author: Quickcreator
 * Author URI: https://quickcreator.io
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quickcreator
 * Domain Path: /
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package QuickcreatorBlog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'QUICKCREATOR_BLOG_VERSION' ) ) {
	define( 'QUICKCREATOR_BLOG_VERSION', '0.1.1' );
}

if ( ! defined( 'QUICKCREATOR_BLOG_PLUGIN_FILE' ) ) {
	define( 'QUICKCREATOR_BLOG_PLUGIN_FILE', __FILE__ );
}

use QuickcreatorBlog\Quickcreatorblog;

if ( ! class_exists( 'Quickcreatorblog' ) ) {
	require_once __DIR__ . '/includes/class-quickcreatorblog.php';
	$quickcreatorblog = Quickcreatorblog::get_instance();
}


if ( ! ( function_exists( 'Quickcreator' ) ) ) {
	/**
	 * Returns the main instance of Quickcreator
	 *
	 * @return Quickcreator
	 */
	function Quickcreator() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
		return Quickcreatorblog::get_instance();
	}
}


/**
 * Clears after uninstall.
 */
function quickcreatorblog_uninstall_hook() {
	wp_cache_flush();

	$connection_details = Quickcreator()->get_quickcreator()->wp_connection_details();
	$site_id = $connection_details['site_id'];
	$id = $connection_details['integration_id'];
	
	Quickcreator()->get_quickcreator()->make_quickcreator_request( '/integrations/unbind/' . $site_id . '/' . $id, array(), 'PUT' );
	
	Quickcreator()->get_quickcreator()->make_disconnection_cleanup();

	delete_transient( 'quickcreatorblog_connection_token' );
}

register_uninstall_hook( __FILE__, 'quickcreatorblog_uninstall_hook' );

