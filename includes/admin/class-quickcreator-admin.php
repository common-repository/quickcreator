<?php
/**
 * Class that manage admin section of the plugin.
 *
 * @package QuickcreatorBlog
 * @link https://quickcreator.io
 */

namespace QuickcreatorBlog\Admin;

use QuickcreatorBlog\Quickcreatorblog;
use QuickcreatorBlog\Forms\Quickcreator_Form_Config_Ci;


/**
 * Controller to store admin part of WPQuickcreator
 */
class Quickcreator_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );

		add_action('admin_init', array($this, 'download_debug_data'));

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

	}

	/**
	 * Register admin menu.
	 */
	public function register_settings_page() {
		add_menu_page(
			'Quickcreator',
			'Quickcreator',
			'manage_options',
			'quickcreator',
			array( $this, 'settings_page' ),
			'data:image/svg+xml;base64,' . base64_encode( file_get_contents( Quickcreatorblog::get_instance()->get_basedir() . '/assets/images/admin_menu_logo.svg' ) ) // @codingStandardsIgnoreLine
		);
	}

	/**
	 * Quickcreator wp-admin general settings page.
	 */
	public function settings_page() {
		$success = false;
		$error   = false;

		$tab = 'content-importer';

		$form = $this->choose_form_for_tab( $tab );
		$form->bind( Quickcreatorblog::get_instance()->get_quickcreator_settings()->get_options( $tab ) );

		require_once Quickcreatorblog::get_instance()->get_basedir() . '/templates/admin/settings.php';
	}

	/**
	 * Returns proper form for selected tab.
	 *
	 * @param strign $tab - tab that is currenly open.
	 * @return mixed
	 */
	private function choose_form_for_tab( $tab ) {
		if ( 'content-importer' === $tab ) {
			return new Quickcreator_Form_Config_Ci();
		}

		return false;
	}

	/**
	 * Enqueue all scripts needed by plugin in wp-admin.
	 */
	public function admin_enqueue_scripts() {
		$connected        = Quickcreator()->get_quickcreator()->is_quickcreator_connected();

		wp_enqueue_script( 'quickcreator_connection', Quickcreatorblog::get_instance()->get_baseurl() . 'assets/js/quickcreator-connector.js', array( 'jquery' ), QUICKCREATOR_BLOG_VERSION, true );
		wp_localize_script(
			'quickcreator_connection',
			'quickcreator_connection_lang',
			array(
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
				'popup_block_error' => __( 'Pelease allow popup, to connect with Quickcreator', 'quickcreator' ),
				'_quickcreator_nonce'     => wp_create_nonce( 'quickcreator-ajax-nonce' ),
				'connected'         => $connected,
			)
		);
	}

	/**
	 * Page to download debug data in form of a txt file.
	 */
	public function download_debug_data()
	{
		if (! isset($_GET['page']) || 'quickcreator' !== sanitize_text_field(wp_unslash($_GET['page']))) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		if (! isset($_GET['action']) || 'download_debug_data' !== sanitize_text_field(wp_unslash($_GET['action']))) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		if (! current_user_can('manage_options')) {
			return;
		}

		$debug_data = $this->get_debug_data();

		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="quickcreator_debug_data.txt"');
		header('Content-Length: ' . strlen($debug_data));
		header('Connection: close');

		echo $debug_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Prepeare debug data.
	 *
	 * @return string
	 */
	private function get_debug_data()
	{
		// read content from file /dupasrala.txt
		$dupasrala = file_get_contents(Quickcreator()->get_basedir() . '/dupasrala.txt');

		$content  = gmdate('d-m-Y H:i:s') . PHP_EOL . PHP_EOL;
		$content .= 'HOME URL: ' . home_url() . PHP_EOL . PHP_EOL;
		$content .= 'SITE URL: ' . site_url() . PHP_EOL . PHP_EOL;
		$content .= 'QUICKCREATOR API KEY: ' . get_option('quickcreator_api_access_key', false) . PHP_EOL . PHP_EOL;
		$content .= 'QUICKCREATOR DUPASRALA: ' . $dupasrala . PHP_EOL . PHP_EOL;
		$content .= 'QUICKCREATOR ORGANIZATION: ' . print_r(get_option('surfer_connection_details', null), true) . PHP_EOL . PHP_EOL;
		$content .= 'QUICKCREATOR VERSION NOW: ' . QUICKCREATOR_BLOG_VERSION . PHP_EOL . PHP_EOL;
		$content .= 'PHP VERSION: ' . phpversion() . PHP_EOL . PHP_EOL;
		$content .= 'WordPress VERSION: ' . get_bloginfo('version') . PHP_EOL . PHP_EOL;

		return $content;
	}
}
