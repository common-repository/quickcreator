<?php
/**
 * Object to manage all actions needed during update or installation
 *
 * @package QuickcreatorBlog
 * @link https://quickcreator.io
 */

namespace QuickcreatorBlog;

defined( 'ABSPATH' ) || exit;

/**
 * Quickcreator Installer class.
 */
class Quickcreator_Installer {

	/**
	 * Object constructor.
	 */
	public function __construct() {
	}

	/**
	 * Runs installation actions.
	 *
	 * @return void
	 */
	public function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( self::is_installing() ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'quickcreator_installing', 'yes', MINUTE_IN_SECONDS * 10 );

		self::set_activation_transients();
		self::update_quickcreator_version();

		delete_transient( 'quickcreator_installing' );
	}

	/**
	 * Returns true if we're installing.
	 *
	 * @return bool
	 */
	private static function is_installing() {
		return 'yes' === get_transient( 'quickcreator_installing' );
	}

	/**
	 * See if we need to set redirect transients for activation or not.
	 *
	 * @return void
	 */
	private static function set_activation_transients() {
		if ( self::is_new_install() ) {
			set_transient( '_quickcreator_activation_redirect', 1, 30 );
		}
	}

	/**
	 * Is this a brand new Quickcreator install?
	 *
	 * A brand new install has no version yet.
	 *
	 * @return boolean
	 */
	public static function is_new_install() {
		return is_null( get_option( 'quickcreator_version', null ) );
	}

	/**
	 * Update quickcreator version to current.
	 *
	 * @return void
	 */
	private static function update_quickcreator_version() {
		update_option( 'quickcreator_version', Quickcreator()->version );
	}

	/**
	 * Set transient when Quickcreator is updated.
	 *
	 * @param object $upgrader_object - Upgrader object.
	 * @param array  $options - Options array.
	 */
	public function quickcreator_upgrade_completed( $upgrader_object, $options ) {
		$our_plugin = Quickcreator()->get_basedir();

		if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( $plugin === $our_plugin ) {
					set_transient( 'quickcreator_updated', 1 );
				}
			}
		}
	}
}
