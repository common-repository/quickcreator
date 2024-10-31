<?php
/**
 *  Main object to controll plugin.
 *
 * @package QuickcreatorBlog
 * @link https://quickcreator.io
 */

namespace QuickcreatorBlog;

use QuickcreatorBlog\Autoloader;
use QuickcreatorBlog\Quickcreator\Quickcreator;
use QuickcreatorBlog\Admin\Quickcreator_Admin;
use QuickcreatorBlog\Admin\Quickcreator_Settings;

/**
 * General object to controll plugin.
 */
class Quickcreatorblog {

	/**
	 * Object Singleton
	 *
	 * @var Quickcreatorblog
	 */
	protected static $instance = null;

	/**
	 * Current version of the plugin
	 *
	 * @var string
	 */
	public $version = null;

	/**
	 * Basedir to the plugin (example: public_html/wp-content/plugins/quickcreator/src/)
	 *
	 * @var string
	 */
	protected $basedir = null;

	/**
	 * URL to the plugin (example: https://example.com/wp-content/plugins/quickcreatorseo/src/)
	 *
	 * @var string
	 */
	protected $baseurl = null;

	/**
	 * Object that contain all Quickcreator features.
	 *
	 * @var Quickcreator
	 */
	protected $quickcreator = null;

	/**
	 * Object that contain wp-admin functions.
	 *
	 * @var Quickcreator_Admin
	 */
	protected $quickcreator_admin = null;

	/**
	 * Class to handle PHP files auto load.
	 *
	 * @var Autoloader
	 */
	protected $autoloader = null;

	/**
	 * URL to WPquickcreator documentation page.
	 *
	 * @var string
	 */
	public $url_wpquickcreator_docs = 'https://docs.quickcreator.io/docs/Integrations/wordpress-plugin';

	/**
	 * URL to quickcreator contact page.
	 *
	 * @var string
	 */
	public $url_wpquickcreator_support = 'https://quickcreatorseo.com/contact/';

	/**
	 * Contains configuration.
	 *
	 * @var Quickcreator_Settings
	 */
	protected $quickcreator_settings = null;

	/**
	 * Object constructor.
	 */
	protected function __construct() {

		$this->basedir = dirname( __DIR__ );
		$this->baseurl = plugin_dir_url( __DIR__ );

		$this->version = QUICKCREATOR_BLOG_VERSION;

		$this->init_hooks();

		add_action( 'init', array( $this, 'register_quickcreator_backup_status' ) );

		add_filter( 'safe_style_css', array( $this, 'allow_display' ) );

		add_filter( 'uagb_post_query_args_grid', array( $this, 'filter_uag_post_query_args_grid'), 10, 2 );

		add_filter( 'uagb_post_query_args_carousel', array( $this, 'filter_uag_post_query_args_grid'), 10, 2 );

		$this->make_imports();
	}

	function filter_uag_post_query_args_grid( $query_args, $block_attributes ) {
    // Check if the className attribute contains 'quickcreator_products' or 'quickcreator_featured_products'
    if ( isset( $block_attributes['className'] ) ) {
        $class_name = $block_attributes['className'];

        if ( strpos( $class_name, 'quickcreator_products' ) !== false ) {
            // Modify the query arguments to filter posts by a specific meta value for qc_products
            $query_args['meta_query'] = array(
                array(
                    'key'     => 'quickcreator_is_products',
                    'value'   => '1',
                    'compare' => '='
                )
            );
        }
        
        if ( strpos( $class_name, 'quickcreator_featured_products' ) !== false ) {
            // Modify the query arguments to filter posts by a specific meta value for qc_products
            $query_args['meta_query'] = array(
                array(
                    'key'     => 'quickcreator_is_featured_products', 
                    'value'   => '1',
                    'compare' => '='
                )
            );
        }

				if ( strpos( $class_name, 'quickcreator_current_archive' ) !== false ) {
				// filter by archive type and archive value like category
				if (is_category()) {
						$archive_type = 'category';
						$archive_value = get_query_var('cat');
				} elseif (is_tag()) {
						$archive_type = 'post_tag';
						$archive_value = get_query_var('tag_id');
				} elseif (is_tax()) {
						$archive_type = get_query_var('taxonomy');
						$archive_value = get_query_var('term');
				} elseif (is_author()) {
						$archive_type = 'author';
						$archive_value = get_query_var('author');
				} elseif (is_date()) {
						// Handle date archives
						if (is_day()) {
								$archive_type = 'day';
								$archive_value = get_the_date('Y-m-d');
						} elseif (is_month()) {
								$archive_type = 'month';
								$archive_value = get_the_date('Y-m');
						} elseif (is_year()) {
								$archive_type = 'year';
								$archive_value = get_the_date('Y');
						}
				} else {
						// Handle other archive types if needed
						$archive_type = '';
						$archive_value = '';
				}

    		if (!empty($archive_type) && !empty($archive_value)) {
						$query_args['tax_query'] = array(
								array(
										'taxonomy' => $archive_type,
										'field'    => 'term_id',
										'terms'    => $archive_value,
								),
						);
					}
			}
    }

    return $query_args;
}

	/**
	 * Singleton
	 *
	 * Creates if NULL and returns quickcreatorseo instance.
	 *
	 * @return Quickcreatorblog
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns plugin basedir.
	 *
	 * @return string
	 */
	public function get_basedir() {
		return $this->basedir;
	}

	/**
	 * Returns plugin base url.
	 *
	 * @return string
	 */
	public function get_baseurl() {
		return $this->baseurl;
	}

	/**
	 * Returns general quickcreator object.
	 *
	 * @return Quickcreator
	 */
	public function get_quickcreator() {
		return $this->quickcreator;
	}

	/**
	 * Returns object that manage settings
	 *
	 * @return Quickcreator_Settings
	 */
	public function get_quickcreator_settings() {
		return $this->quickcreator_settings;
	}

	/**
	 * Instalation hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {

		require_once $this->basedir . '/includes/class-quickcreator-installer.php';
		$installer = new Quickcreator_Installer();

		register_activation_hook( QUICKCREATOR_BLOG_PLUGIN_FILE, array( $installer, 'install' ) );

		add_action( 'upgrader_process_complete', array( $installer, 'quickcreator_upgrade_completed' ), 10, 2 );
	}

	/**
	 * Register new post status, to allow to store backup copies of posts imported from quickcreator.
	 *
	 * @return void
	 */
	public function register_quickcreator_backup_status() {

		register_post_status(
			'quickcreatorblog-backup',
			array(
				'label'                     => _x( 'Quickcreator Blog Backup', 'post', 'quickcreator' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
				/* translators: %s - number */
				'label_count'               => _n_noop( 'Quickcreator Blog Backup <span class="count">(%s)</span>', 'Quickcreator Blog Backups <span class="count">(%s)</span>', 'quickcreator' ),
			)
		);
	}

	/**
	 * Loads textdomain for translation.
	 *
	 * @return void
	 */
	public function load_text_domain() {
		load_plugin_textdomain( 'quickcreatorblog', false, plugin_basename( __DIR__ ) );
	}

	/**
	 * Function that includes all required classes.
	 *
	 * @return void
	 */
	private function make_imports() {

		$this->import_general_imports();
    if ( is_admin() ) {
			$this->import_admin_imports();
		} else {
			$this->import_frontend_imports();
		}
	}

	/**
	 * Makes general imports for the plugin.
	 */
	private function import_general_imports() {
    require_once $this->basedir . '/includes/functions.php';
		require_once $this->basedir . '/includes/class-autoloader.php';
    $this->autoloader = new Autoloader();

		$this->quickcreator = new Quickcreator();
		$this->quickcreator_settings = new Quickcreator_Settings();
	}

	/**
	 * Makes imports related to wp-admin section.
	 */
	private function import_admin_imports() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		$this->quickcreator_admin = new Quickcreator_Admin();
	}

	/**
	 * Includes styles and scripts in wp-admin
	 *
	 * @param string $hook - page where code is executed.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook ) {
		wp_enqueue_style( 'quickcreator-styles', $this->baseurl . 'assets/css/quickcreatorblog.css', array(), QUICKCREATOR_BLOG_VERSION );
	}

	/**
	 * Makes imports related to front-end.
	 */
	private function import_frontend_imports() {
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_enqueue_scripts' ) );
	}

	/**
	 * Includes styles and scripts in wp-front
	 *
	 * @param string $hook - page where code is executed.
	 * @return void
	 */
	public function frontend_enqueue_scripts( $hook ) {
		wp_enqueue_style('quickcreator-styles', $this->baseurl . 'assets/css/quickcreatorblog-front.css', array(), QUICKCREATOR_BLOG_VERSION);
	}

	/**
	 * Allow to use display style in wp_kses.
	 *
	 * @param array $styles - array of safe styles.
	 * @return array
	 */
	public function allow_display( $styles ) {

		$styles[] = 'display';
		return $styles;
	}
}
