<?php

/**
 *  Object that manage all classes related to Quickcreator.
 *
 * @package QuickcreatorBlog
 * @link https://quickcreator.io
 */

namespace QuickcreatorBlog\Quickcreator;

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

use Elementor\Data\V2\Base\Exceptions\WP_Error_Exception;
use QuickcreatorBlog\Quickcreatorblog;
use WP_REST_Response;
use Plugin_Upgrader;
use Theme_Upgrader;
use Theme_Installer_Skin;
use Plugin_Installer_Skin;
use File_Upload_Upgrader;

class QuieThmeSkin extends Theme_Installer_Skin
{
	public function feedback($string, ...$args)
	{ /* no output */
	}
	public function header()
	{ /* no output */
	}
	public function footer()
	{ /* no output */
	}
	public function error($errors)
	{ /* no output */
	}
	public function after()
	{/* no output */
	}
}

class QuietSkin extends Plugin_Installer_Skin
{
	public function feedback($string, ...$args)
	{ /* no output */
	}
	public function header()
	{ /* no output */
	}
	public function footer()
	{ /* no output */
	}
	public function error($errors)
	{ /* no output */
	}
	public function after()
	{/* no output */
	}
}

/**
 * Object responsible for handlig all Quickcreator features.
 */
class Quickcreator
{

	/**
	 * URL to Quickcreator.
	 *
	 * @var string
	 */
	protected $quickcreator_url = 'https://app.quickcreator.io/quick-blog';

	/**
	 * URL to quickcreator API.
	 *
	 * @var string
	 */
	protected $quickcreator_api_url = 'https://api.quickcreator.io/landing-page-service';

	/**
	 * URL to quickcreator Privacy Policy
	 *
	 * @var string
	 */
	protected $quickceator_privacy_policy = 'https://quickcreator.io/privacy';

	/**
	 * Class that hadnle importing content from quickcreator/GoogleDocs into WordPress.
	 *
	 * @var Content_Importer
	 */
	protected $content_importer = null;


	/**
	 * Object construct.
	 */
	public function __construct()
	{
		$this->import_features();

		add_action('rest_api_init', array($this, 'register_connection_api_endpoints'));

		add_action('wp_ajax_generate_quickcreator_connection_url', array($this, 'get_ajax_quickcreator_connect_url'));
		add_action('wp_ajax_disconnect_quickcreator', array($this, 'disconnect_quickcreator_from_wp'));
		add_action('wp_ajax_check_quickcreator_connection_status', array($this, 'check_connection_status'));
	}
	/**
	 * Returns URL to Quickcreator API.
	 *
	 * @return string
	 */
	public function get_api_url()
	{
		return $this->quickcreator_api_url;
	}

	/**
	 * Returns URL to Quickcreator.
	 *
	 * @return string
	 */
	public function get_quickcreator_url()
	{
		return $this->quickcreator_url;
	}

	/**
	 * Returns URL to Quickcreator Privacy Policy.
	 *
	 * @return string
	 */
	// public function get_privacy_policy_url()
	// {
	// 	return $this->quickcreator_privacy_policy;
	// }

	/**
	 * Register endpoints in API to make connectio with quickcreator.
	 *
	 * @return void
	 */
	public function register_connection_api_endpoints()
	{
		register_rest_route(
			'quickcreatorblog/v1',
			'/connect/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'quickcreator_connect_verify'),
				'permission_callback' => function ($request) {
					return true;
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/disconnect/',
			array(
				'methods'             => 'DELETE',
				'callback'            => array($this, 'disconnect_quickcretor'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/import_post/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'quickcreator_import_post'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/categories/',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'list_categories'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/tags/',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'list_tags'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/users/',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'list_users'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/post_types/',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'list_post_types'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/disconnect_draft/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'disconnect_post_from_draft'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/get_posts/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'find_posts'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/upload_img_to_media_library/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'upload_img_to_media_library'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_add_level_menu/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_add_level_menu'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_site_init/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_site_init'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_copy_post/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_copy_post'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// site_builder_get_post_meta_by_key
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_get_post_meta_by_key/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_get_post_meta_by_key'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// site_builder_get_attachment_metadata
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_get_attachment_metadata/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_get_attachment_metadata'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// site_builder_add_term
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_add_term/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_add_term'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);
		// site_builder_taxonomy_as_level_menu
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_taxonomy_as_level_menu/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_taxonomy_as_level_menu'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);
		// site_builder_bind_post_taxonomy_term
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_bind_post_taxonomy_term/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_bind_post_taxonomy_term'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);
		// site_builder_get_template_data
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_get_template_data/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_get_template_data'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// site_builder_get_library_data
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_get_library_data/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_get_library_data'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// site_builder_install_plugin
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_install_plugin/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_install_plugin'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// site_builder_install_plugin_upload
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_install_plugin_upload/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_install_plugin_upload'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// site_builder_theme_builder_save_conditions
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_theme_builder_save_conditions/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_theme_builder_save_conditions'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// site_builder_install_theme
		register_rest_route(
			'quickcreatorblog/v1',
			'/site_builder_install_theme/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'site_builder_install_theme'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);

		// quickcreator_import_tag
		register_rest_route(
			'quickcreatorblog/v1',
			'/import_tag/',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'quickcreator_import_tag'),
				'permission_callback' => function ($request) {
					return $this->verify_request_permission($request);
				},
				'args'                => array(),
			)
		);
	}

	/**
	 * Get header Authorization
	 *
	 * @return string
	 */
	private function get_authorization_header()
	{
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = sanitize_text_field(wp_unslash($_SERVER['Authorization']));
		} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			// Nginx or fast CGI.
			$headers = sanitize_text_field(wp_unslash($_SERVER['HTTP_AUTHORIZATION']));
		} elseif (isset($_SERVER['HTTP_X_QC_TOKEN'])) {
			$headers = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_QC_TOKEN']));
		} elseif (function_exists('apache_request_headers')) {
			$request_headers = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization).
			$request_headers = array_combine(array_map('ucwords', array_keys($request_headers)), array_values($request_headers));
			if (isset($request_headers['Authorization'])) {
				$headers = sanitize_text_field(wp_unslash($request_headers['Authorization']));
			} else if (isset($request_headers['X-Qc-Token'])) {
				$headers = sanitize_text_field(wp_unslash($request_headers['X-Qc-Token']));
			}
		}

		return $headers;
	}

	/**
	 * Get access token from header
	 *
	 * @return null | string
	 */
	private function get_bearer_token()
	{
		$headers = $this->get_authorization_header();

		// HEADER: Get the access token from the header.
		if (! empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}

		return null;
	}

	/**
	 * Import all classes that handle different features.
	 *
	 * @return void
	 */
	private function import_features()
	{
		$this->content_importer  = new Content_Importer();
	}

	/**
	 * Checks permission of user that is trying to use API.
	 *
	 * @param WP_API_Request $request - Request object.
	 * @return bool
	 */
	public function verify_request_permission($request)
	{
		$received_token = $this->get_bearer_token();
		$saved_token    = get_option('quickcreator_api_access_key', false);

		file_put_contents(Quickcreator()->get_basedir() . '/dupasrala.txt', $received_token . ' - ' . $saved_token);

		if (null !== $received_token && false !== $saved_token && $received_token === $saved_token) {
			return true;
		}
		return false;
	}

	/**
	 * Returns initial connection URL for AJAX request.
	 *
	 * @return void
	 */
	public function get_ajax_quickcreator_connect_url()
	{

		if (! quickcreator_validate_ajax_request()) {
			echo wp_json_encode(array('message' => 'Security check failed.'));
			wp_die();
		}

		$response = array(
			'url' => $this->get_quickcreator_connect_url(),
		);

		echo wp_json_encode($response);
		wp_die();
	}

	/**
	 * Function returns initial connect URL.
	 *
	 * @return string
	 */
	public function get_quickcreator_connect_url()
	{
		$url   = site_url();
		$token = $this->genereate_connection_token();

		return $this->quickcreator_url . '/wordpress/connect?token=' . $token . '&url=' . $url;
	}

	/**
	 * Creates token to make connection with Quickcreator.
	 *
	 * @return string
	 */
	private function genereate_connection_token()
	{
		$token = wp_generate_uuid4();

		set_transient('quickcreator_connection_token', $token, 60 * 5);

		return $token;
	}

	/**
	 * Function to verify response from Quickcreator.
	 *
	 * @param WP_REST_Request $request - Request object.
	 * @return string
	 */
	public function quickcreator_connect_verify($request)
	{
		$token = false;
		if (isset($request['token'])) {
			$token = sanitize_text_field(wp_unslash($request['token']));
		}

		if (false !== $token && $this->verify_connection_token($token)) {
			$api_key     = sanitize_text_field(wp_unslash($request['api_key']));
			$token_saved = update_option('quickcreator_api_access_key', $api_key, false);
			delete_transient('quickcreator_connection_token');

			$connection_details = array(
				'integration_id'    => $request['integration_id'],
				'site_id' 					=> $request['site_id'],
				'via_email'         => $request['via_email'],
			);

			update_option('quickcreator_connection_details', $connection_details, false);

			$response = new WP_REST_Response(array('token_saved' => $token_saved));
		} else {
			$response = new WP_REST_Response(array('error' => __('Token verification failed', 'quickcreator')), 403);
		}

		return $response;
	}

	/**
	 * Verify if provided token is the same generated token.
	 *
	 * @param string $token - Token.
	 * @return bool
	 */
	private function verify_connection_token($token)
	{
		$wp_token = get_transient('quickcreator_connection_token');

		if (false !== $wp_token && $wp_token === $token) {
			return true;
		}

		return false;
	}

	/**
	 * Disconnects Quickcreator from WPquickcreator on quickcreator request.
	 *
	 * @return WP_REST_Response
	 */
	public function disconnect_quickcretor()
	{
		$this->make_disconnection_cleanup();
		$response = new WP_REST_Response();

		return $response;
	}

	/**
	 * Allows to check if connection to quickcreator exists.
	 *
	 * @return void
	 */
	public function check_connection_status()
	{

		if (! quickcreator_validate_ajax_request()) {
			echo wp_json_encode(array('message' => 'Security check failed.'));
			wp_die();
		}

		$response = array(
			'connection' => false,
		);

		$connection_details = get_option('quickcreator_connection_details', false);

		if (false !== $connection_details) {
			$response['connection'] = true;
			$response['details']    = $connection_details;
		}

		echo wp_json_encode($response);
		wp_die();
	}

	/**
	 * Function to disconnect quickcreator and inform Quickcreator about that.
	 *
	 * @return void
	 */
	public function disconnect_quickcreator_from_wp()
	{

		if (! quickcreator_validate_ajax_request()) {
			echo wp_json_encode(array('message' => 'Security check failed.'));
			wp_die();
		}

		$connection_details = Quickcreator()->get_quickcreator()->wp_connection_details();
		$site_id = $connection_details['site_id'];
		$id = $connection_details['integration_id'];

		list(
			'code'     => $code,
			'response' => $response,
			'result'   => $result,
			'api_url'  => $api_url
		) = Quickcreator()->get_quickcreator()->make_quickcreator_request('/integrations/unbind/' . $site_id . '/' . $id, array(), 'PUT');

		$this->make_disconnection_cleanup();

		echo esc_html($response);
		wp_die();
	}

	/**
	 * Function that clears all options during disconnection.
	 *
	 * @return void
	 */
	function make_disconnection_cleanup()
	{
		delete_option('quickcreator_api_access_key');
		delete_option('quickcreator_connection_details');
	}

	/**
	 * Returns list of post categories.
	 *
	 * @param WP_REST_Request $request - request object.
	 * @return array
	 */
	public function list_categories($request)
	{
		return $this->quickcreator_return_categories();
	}

	/**
	 * Returns list of post tags.
	 *
	 * @param WP_REST_Request $request - request object.
	 * @return array
	 */
	public function list_tags($request)
	{
		return $this->quickcreator_return_tags();
	}

	/**
	 * Returns list of post authors.
	 *
	 * @param WP_REST_Request $request - request object.
	 * @return array
	 */
	public function list_users($request)
	{
		return $this->quickcreator_return_users_list();
	}

	public function list_post_types($request)
	{
		return quickcreator_return_supported_post_types(true);
	}

	/**
	 * Creates parsed post based on content from Quickcreator.
	 *
	 * @param WP_REST_Request $request - request object.
	 * @return WP_REST_Response
	 */
	public function quickcreator_import_post($request)
	{
		$args    = array();
		$content = $request->get_param('content');

		if (empty($content) || '' === $content || (is_string($content) && strlen($content) < 1)) {
			return new WP_REST_Response(
				array(
					'error' => __('Cannot add post with empty content.', 'quickcreator'),
				),
				422
			);
		}

		// $metadata = $request->get_param( 'metadata' );

		// Optional params.
		$args['post_id']          = $request->get_param('post_id');
		$args['post_title']       = $request->get_param('title') ?? '';
		$args['permalink_hash']   = $request->get_param('slug');
		$args['post_category']    = $request->get_param('categories') ?? array();
		$args['post_tags']        = $request->get_param('tags') ?? array();
		$args['post_author']      = $request->get_param('author') ?? '';
		$args['post_excerpt']     = $request->get_param('excerpt') ?? '';
		$args['meta_title']       = $request->get_param('meta_title');
		$args['meta_description'] = $request->get_param('meta_description');
		$args['post_status']      = $request->get_param('status');
		$args['post_type']        = $request->get_param('post_type') ?? '';
		$args["meta_input"]       = $request->get_param('meta') ?? array();
		$args['post_parent']      = $request->get_param('post_parent') ?? 0;
		$args['filter']           = $request->get_param('filter') ?? '';
		$featured_image = $request->get_param('featured_image');
		if ($featured_image !== null && $featured_image !== '') {
			$args['featured_image']   = $featured_image; # quickcreator_sync_image_to_media_library($request->get_param( 'featured_image' ));
		}

		// $args['post_date']        = $metadata['publicationDate'] ?? '';
		// $args['tags_input']       = $request->get_param( 'tags_input' );

		$args['post_name']        = $request->get_param('name');
		// $args['draft_id']         = $request->get_param( 'draft_id' );
		// $args['keywords']         = $request->get_param( 'keywords' );
		// $args['location']         = $request->get_param( 'location' );

		$modification_date = gmdate('Y-m-d');
		if (isset($args['post_id'])) {
			$modification_date = get_the_modified_time('Y-m-d', $args['post_id']);
		}

		$post_id = $this->content_importer->save_data_into_database($content, $args);

		if (! is_wp_error($post_id)) {
			return new WP_REST_Response(
				array(
					'post_id'       => $post_id,
					'edit_post_url' => $this->get_edit_post_link($post_id, 'notdisplay'),
					'post_url'      => get_permalink($post_id),
					'post_status'   => get_post_status($post_id),
					'modified_at'   => $modification_date,
					'url'           => site_url(),
				)
			);
		} else {
			return new WP_REST_Response(
				array(
					'error'            => __('There was an error on post adding', 'quickcreator'),
					'wp_error_message' => $post_id->get_error_message(),
				),
				403
			);
		}
	}

	/**
	 * Creates tag if not exist, or return this.
	 * @param WP_REST_Request $request - request object.
	 * @return WP_REST_Response 
	 * 
	 */
	public function quickcreator_import_tag($request)
	{
		$tag_name = $request->get_param('tag_name');

		if (! $tag_name) {
			return new WP_REST_Response(
				array(
					'error' => __('Tag name is required', 'quickcreator'),
				),
				422
			);
		}

		$tag = get_term_by('name', $tag_name, 'post_tag');

		if (! $tag) {
			$tag = wp_insert_term($tag_name, 'post_tag');
		}

		if (! is_wp_error($tag)) {
			return new WP_REST_Response(
				array(
					'tag_id'   => $tag->term_id,
					'tag_name' => $tag->name,
					'tag_slug' => $tag->slug,
				)
			);
		} else {
			return new WP_REST_Response(
				array(
					'error'            => __('There was an error on tag adding', 'quickcreator'),
					'wp_error_message' => $tag->get_error_message(),
				),
				403
			);
		}
	}

	/**
	 * Returns list of all categories
	 *
	 * @return array
	 */
	public function quickcreator_return_categories()
	{
		$args = array(
			'hide_empty' => false,
		);

		$categories = get_categories($args);

		$categories = array_map(
			function ($category) {
				return array(
					'id' => $category->term_id,
					'count' => $category->count,
					'name' => $category->name,
					'slug' => $category->slug,
					'parent' => $category->category_parent,
				);
			},
			$categories
		);

		return $categories;
	}

	/**
	 * Returns list of all tags
	 *
	 * @return array
	 */
	public function quickcreator_return_tags()
	{
		$args = array(
			'hide_empty' => false,
		);

		$tags = get_tags($args);

		$tags = array_map(
			function ($tag) {
				return array(
					'id'    => $tag->term_id,
					'count' => $tag->count,
					'name'  => $tag->name,
					'slug'  => $tag->slug,
				);
			},
			$tags
		);

		return $tags;
	}

	/**
	 * Returns list of all users
	 *
	 * @return array
	 */
	public function quickcreator_return_users_list()
	{
		$users = array();

		$all_users = get_users(array('number' => -1));

		if ($all_users) {
			foreach ($all_users as $user) {
				$name = $user->data->display_name;
				if (empty($name)) {
					$name = $user->data->user_nicename;
				}
				if (empty($name)) {
					$name = $user->data->user_email;
				}
				$users[] = array(
					'id'    => $user->ID,
					'url'   => $user->data->user_url,
					'name'  => $name,
					'link'  => $user->data->user_url,
				);
			}
		}

		return $users;
	}

	/**
	 * Returns post edit link in wp-admin, without checking permission.
	 *
	 * @param int    $post_id - ID of the post.
	 * @param string $context - how to display ampersand char.
	 * @return string
	 */
	private function get_edit_post_link($post_id, $context = 'display')
	{
		$post = get_post($post_id);
		if (! $post) {
			return;
		}

		if ('revision' === $post->post_type) {
			$action = '';
		} elseif ('display' === $context) {
			$action = '&amp;action=edit';
		} else {
			$action = '&action=edit';
		}

		$post_type_object = get_post_type_object($post->post_type);
		if (! $post_type_object) {
			return;
		}

		if ($post_type_object->_edit_link) {
			$link = admin_url(sprintf($post_type_object->_edit_link . $action, $post->ID));
		} else {
			$link = '';
		}

		return $link;
	}

	/**
	 * Returns details of the connection, or false if connection is not made.
	 *
	 * @return array|false
	 */
	public function wp_connection_details()
	{
		return get_option('quickcreator_connection_details', false);
	}

	/**
	 * Checks if page is meeting requirements to connect with quickcreator.
	 *
	 * @return bool
	 */
	public function wp_ready_to_connect()
	{
		$wp_version = get_bloginfo('version');

		if (version_compare($wp_version, '5.7', '<')) {
			return false;
		}

		if (! is_ssl()) {
			return false;
		}

		if ('' === get_option('permalink_structure')) {
			return false;
		}

		return true;
	}

	/**
	 * Return requirements array
	 *
	 * @return array
	 */
	public function wp_ready_to_connect_errors()
	{
		$wp_version = get_bloginfo('version');

		$permalinks = true;
		if ('' === get_option('permalink_structure')) {
			$permalinks = false;
		}

		return array(
			'version'    => array(
				/* translators: %s - version of the WordPress */
				'msg'   => sprintf(__('WordPress version 5.7 or newer. Your version: %s', 'quickcreator'), $wp_version),
				'valid' => version_compare($wp_version, '5.7', '>='),
			),
			'ssl'        => array(
				'msg'   => __('SSL should be enabled.', 'quickcreator'),
				'valid' => is_ssl(),
			),
			'permalinks' => array(
				'msg'   => __('Permalinks should be active', 'quickcreator'),
				'valid' => $permalinks,
			),
		);
	}

	/**
	 * Returns array with a response from quickcreator API.
	 *
	 * @param string $endpoint - endpoint to quickcreator API.
	 * @param array  $params - params to send.
	 * @param string $method - method to send. (Optional, default: POST).
	 */
	public function make_quickcreator_request($endpoint, $params, $method = 'POST')
	{
		$token   = get_option('quickcreator_api_access_key', false);
		$api_url = Quickcreator()->get_quickcreator()->get_api_url() . $endpoint;

		if (false === $token) {
			return array('message' => __('You need to connect your page to quickcreator first.', 'quickcreator'));
		}

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Lp-Token'      => $token,
			),
			'body'    => wp_json_encode($params),
		);

		if ('GET' === $method) {
			unset($args['body']);
		}

		$result = wp_remote_request($api_url, $args);
		$code   = wp_remote_retrieve_response_code($result);

		if (200 !== $code && 201 !== $code) {
			$response = $this->handle_quickcreator_errors($code, $result);
		} else {
			$response = json_decode(wp_remote_retrieve_body($result), true);
		}

		return array(
			'code'     => $code,
			'response' => $response,
			'result'   => $result,
			'api_url'  => $api_url
		);
	}

	/**
	 * Handle Quickcreator API errors.
	 *
	 * @param int   $code - error code.
	 * @param array $result - result from Quickcreator API.
	 * @return array
	 */
	private function handle_quickcreator_errors($code, $result)
	{
		$error_message = wp_remote_retrieve_body($result);
		$response      = $code;

		if (401 === $code) {
			$response = array('message' => __('401: Authorization process failed.', 'quickcreator'));
		}

		if (404 === $code) {
			$response = array('message' => __('404: Endpoint do not exists. Please reach our support.', 'quickcreator'));
		}

		if (422 === $code) {
			/* translators: %s - error message */
			$response = array('message' => sprintf(__('422: Request failed with message: %s', 'quickcreator'), $error_message));
		}

		if (500 === $code) {
			$response = array('message' => __('500: Unknown error. Please reach our support', 'quickcreator'));
		}

		if (566 === $code) {
			$response = array('message' => __('566: Unknown error. Please reach our support', 'quickcreator'));
		}

		return $response;
	}

	/**
	 * Checks if Quickcreator is connected to WordPress site.
	 *
	 * @return bool
	 */
	public function is_quickcreator_connected()
	{
		$connected          = false;
		$connection_details = Quickcreatorblog::get_instance()->get_quickcreator()->wp_connection_details();
		if (is_array($connection_details) && false !== $connection_details) {
			$connected = true;
		}

		return $connected;
	}

	/**
	 * Disconnects post from quickcreator draft
	 *
	 * @param WP_REST_Request $request - request object.
	 * @return WP_REST_Response
	 */
	public function disconnect_post_from_draft($request)
	{

		$post_id = $request->get_param('wp_post_id');

		if (! $post_id || null === get_post($post_id)) {
			return new WP_REST_Response(
				array(
					'error' => __('Cannot add post with empty content.', 'quickcreator'),
				),
				422
			);
		}

		$this->disconnect_post_and_draft($post_id);

		return new WP_REST_Response(
			array(
				'post_id' => $post_id,
			)
		);
	}

	/**
	 * Removes all meta data related to quickcreator from post.
	 *
	 * @param int $post_id - ID of the post.
	 */
	private function disconnect_post_and_draft($post_id)
	{

		delete_post_meta($post_id, 'quickcreator_draft_id');
		delete_post_meta($post_id, 'quickcreator_permalink_hash');
		delete_post_meta($post_id, 'quickcreator_last_post_update');
		delete_post_meta($post_id, 'quickcreator_last_post_update_direction');
		delete_post_meta($post_id, 'quickcreator_keywords');
		delete_post_meta($post_id, 'quickcreator_location');
	}

	public function find_posts($request)
	{
		$posts = get_posts($request->get_json_params());
		return new WP_REST_Response(
			$posts
		);
	}

	/**
	 * Saves image from provided URL into WordPress media library
	 *
	 * @param WP_REST_Request $request - request object.
	 * @return WP_REST_Response
	 */
	public function upload_img_to_media_library($request)
	{
		$image_url = $request->get_param('image_url');
		$image_alt = $request->get_param('image_alt');
		return new WP_REST_Response(
			$this->content_importer->upload_img_to_media_library($image_url, $image_alt)
		);
	}

	private function site_builder_add_nav_menu_item($menu_item_title, $page_id, $item_type, $item_object)
	{
		$menu_name = "Primary Menu";
		$nav_menu = wp_get_nav_menu_object($menu_name);
		if (! $nav_menu) {
			$nav_menu_id = wp_create_nav_menu($menu_name);
		} else {
			$nav_menu_id = $nav_menu->term_id;
		}
		$nav_menu_items = wp_get_nav_menu_items($menu_name);

		$nav_menu_item_exists = false;
		if ($nav_menu_items) {
			foreach ($nav_menu_items as $menu_item) {
				if ($menu_item->title == $menu_item_title) {
					$nav_menu_item_exists = $menu_item->ID;
					break;
				}
			}
		}
		if (!$nav_menu_item_exists) {
			if (isset($page_id) && "" !== $page_id) {
				$nav_menu_item_exists = wp_update_nav_menu_item($nav_menu_id, 0, array(
					'menu-item-title' => $menu_item_title,
					'menu-item-status' => 'publish',
					'menu-item-object' => $item_object,
					'menu-item-object-id' => $page_id,
					'menu-item-type' => $item_type,
				));
			} else {
				$nav_menu_item_exists = wp_update_nav_menu_item($nav_menu_id, 0, array(
					'menu-item-title' => $menu_item_title,
					'menu-item-url' => '#',
					'menu-item-status' => 'publish'
				));
			}
		}
		$locations = get_theme_mod('nav_menu_locations');
		$locations["primary"] = $nav_menu_id;
		set_theme_mod('nav_menu_locations', $locations);
		return array(
			'nav_menu_id' => $nav_menu_id,
			'nav_menu_item_id' => $nav_menu_item_exists
		);
	}

	public function site_builder_add_level_menu($request)
	{
		$level1_menu = $request->get_param('level1_menu');
		$level1_menu_page = $request->get_param('level1_menu_page');
		$level2_menu = $request->get_param('level2_menu');
		$level2_menu_page = $request->get_param('level2_menu_page');
		$item_type = $request->get_param('item_type') ?? 'post_type';
		$item_object = $request->get_param('item_object') ?? 'page';

		// builder parent menu
		if (isset($level1_menu) && "" !== $level1_menu) {
			$level1_menu_obj = $this->site_builder_add_nav_menu_item($level1_menu, $level1_menu_page, $item_type, $item_object);
			$nav_menu_id = $level1_menu_obj["nav_menu_id"];
			$level1_menu_id = $level1_menu_obj["nav_menu_item_id"];
			$nav_menu_items = wp_get_nav_menu_items($nav_menu_id);

			if (isset($level2_menu_page) && '' !== $level2_menu_page) {
				$page_menu_item_id = 0;
				foreach ($nav_menu_items as $menu_item) {
					if ($menu_item->object_id == $level2_menu_page && $menu_item->object == $item_object && $menu_item->menu_item_parent == $level1_menu_id) {
						$page_menu_item_id = $menu_item->ID;
						break;
					}
				}
				if (isset($level2_menu) && '' !== $level2_menu) {
					$leve2_menu_title = $level2_menu;
				} else {
					$leve2_menu_title = get_the_title($level2_menu_page);
				}
				wp_update_nav_menu_item($nav_menu_id, $page_menu_item_id, array(
					'menu-item-title' => $leve2_menu_title,
					'menu-item-status' => 'publish',
					'menu-item-parent-id' => $level1_menu_id,
					'menu-item-object' => $item_object,
					'menu-item-object-id' => $level2_menu_page,
					'menu-item-type' => $item_type,
				));
			}
		}

		return new WP_REST_Response(
			array(
				'state' => 'success',
			)
		);
	}

	public function site_builder_site_init($request)
	{
		$user = get_users()[0];
		wp_set_current_user($user->ID, $user->data->display_name);
		$is_astra_based_theme = defined('ASTRA_THEME_SETTINGS');
		if ($is_astra_based_theme) {
			$options = get_option(ASTRA_THEME_SETTINGS, false);
			$global_color_palette = $request->get_param('global_color_palette');
			if (isset($global_color_palette) && is_array($global_color_palette) && count($global_color_palette) == 8) {
				$options["global-color-palette"] = array(
					"palette" => $global_color_palette,
					"flag" => false
				);
			} else {
				$options["global-color-palette"] = array(
					"palette" => ["#ffc03d", "#f8b526", "#212d45", "#4b4f58", "#F5F5F5", "#FFFFFF", "#F2F5F7", "#212d45", "#000000"],
					"flag" => false
				);
			}
			$color0 = str_split('var(--ast-global-color-0)');
			$color0['desktop'] = 'var(--ast-global-color-0)';
			$color5 = str_split('var(--ast-global-color-5)');
			$color5['desktop'] = 'var(--ast-global-color-5)';
			unset($options['transparent-header-bg-color-responsive']);
			$options['transparent-header-color-site-title-responsive'] = $color5;
			$options['transparent-menu-color-responsive'] = $color5;
			$options['transparent-menu-h-color-responsive'] = $color0;
			$options['header-menu1-submenu-item-border'] = false;

			update_option(ASTRA_THEME_SETTINGS, $options);
			wp_update_custom_css_post(".dynamic-listing .listing {\n\tpadding: 0!important;\n}\n.e-loop-item {\n\tpadding: 0!important;\n}\n.ast-container {\n\tmax-width: 100%;\n\tpadding: 0; \n}\n.qc-square-image img {\n    aspect-ratio: 1 / 1!important;\n    object-fit: fill!important;\n}");
		}
		$page_on_front = $request->get_param('page_on_front');
		if (isset($page_on_front) && "" !== $page_on_front) {
			update_option('show_on_front', 'page');
			update_option('page_on_front', $page_on_front);
		}
		$site_name = $request->get_param('site_name');
		if (isset($site_name) && "" !== $site_name) {
			update_option('blogname', $site_name);
		}
		$acf_init = $request->get_param('acf_init') ?? false;
		if ($acf_init) {
			quickcreator_register_post_type("Listing", "Listings", "listing", ["title", "editor", "comments", "revisions", "author", "excerpt", "thumbnail"], ["post_tag"]);
			quickcreator_register_taxonomy("Collection", "Collections", "collection", ["listing"], true);
			if (quickcreator_check_if_plugins_is_active('elementor/elementor.php')) {
				$options = get_option('elementor_cpt_support', \ELEMENTOR\Plugin::ELEMENTOR_DEFAULT_POST_TYPES);
				if (is_array($options)) {
					if (!in_array('listing', $options)) {
						$options[] = 'listing';
					}
					update_option('elementor_cpt_support', $options, true);
				}
			}
		}
		$megamenu_init = $request->get_param("megamenu_init") ?? false;
		if ($megamenu_init) {
			if (quickcreator_check_if_plugins_is_active('megamenu/megamenu.php')) {
				$saved_themes = max_mega_menu_get_themes();
				$saved_themes["default_quickcreator"] = array(
					'title' => 'Default Quickcreator',
					'container_background_from' => 'rgba(34, 34, 34, 0)',
					'container_background_to' => 'rgba(34, 34, 34, 0)',
					'menu_item_background_hover_from' => 'rgba(255, 0, 0, 0)',
					'menu_item_background_hover_to' => 'rgba(255, 0, 0, 0)',
					'menu_item_link_font_size' => '16px',
					'menu_item_link_color_hover' => 'rgb(255, 192, 61)',
					'panel_background_from' => 'rgb(255, 255, 255)',
					'panel_background_to' => 'rgb(255, 255, 255)',
					'panel_width' => '#qc-custom-header',
					'panel_inner_width' => '1200px',
					'panel_border_color' => 'rgb(51, 51, 51)',
					'panel_header_font_size' => '24px',
					'panel_padding_top' => '24px',
					'panel_padding_bottom' => '24px',
					'panel_font_size' => '14px',
					'panel_font_color' => '#666',
					'panel_font_family' => 'inherit',
					'panel_second_level_font_color' => '#555',
					'panel_second_level_font_color_hover' => '#555',
					'panel_second_level_text_transform' => 'uppercase',
					'panel_second_level_font' => 'inherit',
					'panel_second_level_font_size' => '16px',
					'panel_second_level_font_weight' => 'bold',
					'panel_second_level_font_weight_hover' => 'bold',
					'panel_second_level_text_decoration' => 'none',
					'panel_second_level_text_decoration_hover' => 'none',
					'panel_second_level_border_color' => 'rgba(255, 0, 0, 0)',
					'panel_third_level_font_color' => '#666',
					'panel_third_level_font_color_hover' => '#666',
					'panel_third_level_font' => 'inherit',
					'panel_third_level_font_size' => '14px',
					'flyout_link_size' => '14px',
					'flyout_link_color' => '#666',
					'flyout_link_color_hover' => '#666',
					'flyout_link_family' => 'inherit',
					'toggle_background_from' => '#222',
					'toggle_background_to' => '#222',
					'mobile_background_from' => '#222',
					'mobile_background_to' => '#222',
					'mobile_menu_item_link_font_size' => '14px',
					'mobile_menu_item_link_color' => '#ffffff',
					'mobile_menu_item_link_text_align' => 'left',
					'mobile_menu_item_link_color_hover' => '#ffffff',
					'mobile_menu_item_background_hover_from' => '#333',
					'mobile_menu_item_background_hover_to' => '#333',
					'custom_css' => '/** Push menu onto new line **/ 
					#{$wrap} { 
							clear: both; 
					}
					#{$wrap} #{$menu} .qc-menu-level-1 .qc-menu-level-2 {
						border-right: solid 1px #eee!important;
					}
					#{$wrap} #{$menu} .qc-menu-level-1 .qc-menu-level-2:last-child {
						border-right: none!important;
					}
					#{$wrap} #{$menu} .qc-menu-level-1 {
						padding: 12px;
					}
					#{$wrap} #{$menu} .qc-menu-level-1 > ul {
						display: flex!important;
					}
					#{$wrap} #{$menu} .qc-menu-level-2 > .mega-sub-menu {
						display:flex!important;
					}
					#{$wrap} #{$menu} .qc-menu-level-2 > .mega-sub-menu img {
						width: 100%!important;
						aspect-ratio: 1!important;
						object-fit: cover!important;
					}
					#{$wrap} #{$menu} .qc-menu-level-2 > .mega-sub-menu > li:first-child {
						padding-top: 36px!important;
						padding-right: 0!important;
						width: 120px!important;
					}',
				);
				max_mega_menu_save_themes($saved_themes);
				$submitted_settings = [
					"primary" => [
						"enabled" => "1",
						"event" => "hover",
						"effect" => "fade_up",
						"effect_speed" => "200",
						"effect_mobile" => "disabled",
						"effect_speed_mobile" => "200",
						"theme" => "default_quickcreator",
						"second_click" => "go",
						"mobile_behaviour" => "standard",
						"mobile_state" => "collapse_all",
						"descriptions" => "enabled",
						"unbind" => "enabled",
						"container" => "div",
						"active_instance" => "0",
						"prefix" => "disabled",
					],
				];
				if (! get_option('megamenu_settings')) {
					update_option('megamenu_settings', $submitted_settings);
				} else {
					$existing_settings = get_option('megamenu_settings');
					$new_settings      = array_merge($existing_settings, $submitted_settings);
					update_option('megamenu_settings', $new_settings);
				}
				delete_transient('megamenu_failed_to_write_css_to_filesystem');
				do_action('megamenu_after_save_general_settings');
				do_action('megamenu_delete_cache');
			}
		}
		$elementor_license_key = $request->get_param("elementor_pro_license_key");
		if (isset($elementor_license_key) && "" !== $elementor_license_key) {
			quickcreator_elementor_pro_license_active($elementor_license_key);
		}
		$theplus_options = get_option("theplus_options", false);
		if (is_array($theplus_options)) {
			// tp_dynamic_listing
			if (!in_array("tp_dynamic_listing", $theplus_options["check_elements"])) {
				$theplus_options["check_elements"][] = "tp_dynamic_listing";
			}
			// tp_search_filter
			if (!in_array("tp_search_filter", $theplus_options["check_elements"])) {
				$theplus_options["check_elements"][] = "tp_search_filter";
			}
			update_option("theplus_options", $theplus_options);
		}
		return new WP_REST_Response(
			array(
				'state' => 'success',
			)
		);
	}

	public function site_builder_get_post_meta_by_key($request)
	{
		$post_id = $request->get_param('post_id');
		$post_meta = get_post_meta($post_id, $request->get_param('key'), true);
		return new WP_REST_Response(
			array(
				'post_meta' => $post_meta,
			)
		);
	}

	public function site_builder_get_attachment_metadata($request)
	{
		$attachment_id = $request->get_param('attachment_id');
		$attachment_metadata = wp_get_attachment_metadata($attachment_id);
		return new WP_REST_Response(
			array(
				'attachment_metadata' => $attachment_metadata,
			)
		);
	}

	public function site_builder_add_term($request)
	{
		$qucikcreator_id = $request->get_param('qucikcreator_id');
		$term_name = $request->get_param('term_name');
		$taxonomy = $request->get_param('taxonomy');
		$args = $request->get_param('args') ?? [];
		$terms = get_terms([
			'taxonomy'   => $taxonomy,
			'meta_query' => array(
				array(
					'key'      => "__qucikcreator_id",
					'value'    => $qucikcreator_id,
					'compare'  => "=",
				)
			),
			'hide_empty' => 0,
			'number'     => 1,
		]);
		$term = $terms[0] ?? null;
		if ($term !== 0 && $term !== null) {
			$term_id = $term->term_id;
			$args['name'] = $term_name;
			$term = wp_update_term($term_id, $taxonomy, $args);
		} else {
			$term = wp_insert_term($term_name, $taxonomy, $args);
			if (!is_wp_error($term)) {
				update_term_meta($term['term_id'], "__qucikcreator_id", $qucikcreator_id);
			} else {
				return new WP_REST_Response(
					array(
						'error'            => __('There was an error on insert term', 'quickcreator'),
						'wp_error_message' => $term->get_error_message(),
					),
					403
				);
			}
		}
		return new WP_REST_Response(
			array(
				'term' => $term,
			)
		);
	}

	public function site_builder_taxonomy_as_level_menu($request)
	{
		$level1_menu = $request->get_param('level1_menu');
		$level1_menu_page = $request->get_param('level1_menu_page');
		$item_type = $request->get_param('item_type') ?? 'post_type';
		$item_object = $request->get_param('item_object') ?? 'page';
		$taxonomy = $request->get_param('taxonomy');
		$max_depth = $request->get_param('max_depth') ?? 10;
		if (isset($level1_menu) && "" !== $level1_menu) {
			$level1_menu_obj = $this->site_builder_add_nav_menu_item($level1_menu, $level1_menu_page, $item_type, $item_object);
			$nav_menu_id = $level1_menu_obj["nav_menu_id"];
			$level1_menu_id = $level1_menu_obj["nav_menu_item_id"];
			// delete all sub navmenu of nav_menu_item_exists
			$nav_menu_items = wp_get_nav_menu_items($nav_menu_id);
			foreach ($nav_menu_items as $menu_item) {
				if ($menu_item->object === "collection") {
					wp_delete_post($menu_item->ID);
				}
			}
			$terms = get_terms([
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
				'parent' => 0, // Only get top-level terms
			]);

			// Recursive function to add submenu items
			function add_term_to_menu($term, $nav_menu_id, $parent_menu_id, $depth, $max_depth)
			{
				$term_menu_item_id = wp_update_nav_menu_item($nav_menu_id, 0, [
					'menu-item-title' => $term->name,
					'menu-item-parent-id' => $parent_menu_id,
					'menu-item-status' => 'publish',
					'menu-item-object' => $term->taxonomy,
					'menu-item-object-id' => $term->term_id,
					'menu-item-type' => 'taxonomy',
				]);

				// Get child terms
				$child_terms = get_terms([
					'taxonomy' => $term->taxonomy,
					'hide_empty' => false,
					'parent' => $term->term_id,
				]);

				if (!is_wp_error($child_terms) && !empty($child_terms) && $depth + 1 <= $max_depth) {
					foreach ($child_terms as $child_term) {
						add_term_to_menu($child_term, $nav_menu_id, $term_menu_item_id, $depth + 1, $max_depth);
					}
				}
			}

			// Build the navigation menu for the nav_menu_id
			if (!is_wp_error($terms) && !empty($terms) && $max_depth > 1) {
				foreach ($terms as $term) {
					add_term_to_menu($term, $nav_menu_id, $level1_menu_id, 2, $max_depth);
				}
			}
		}
	}

	public function site_builder_bind_post_taxonomy_term($request)
	{
		$post_id = $request->get_param('post_id');
		$term_id = $request->get_param('term_id');
		$taxonomy = $request->get_param('taxonomy');
		$append = $request->get_param('append') ?? false;
		$term = wp_set_object_terms($post_id, $term_id, $taxonomy, $append);
		return new WP_REST_Response(
			array(
				'term' => $term,
			)
		);
	}

	private function copy_post($post_id)
	{
		// Get the original post
		$post = get_post($post_id);

		// If the post doesn't exist, return false
		if (!$post) {
			return false;
		}

		// Prepare the new post data
		$new_post = array(
			'post_title'    => $post->post_title . ' - Copy',
			'post_content'  => $post->post_content,
			'post_status'   => 'draft', // Set the status of the new post
			'post_type'     => $post->post_type,
			'post_author'   => $post->post_author,
		);

		// Insert the new post
		$new_post_id = wp_insert_post($new_post);

		// If the new post was created successfully, copy the metadata
		if ($new_post_id && !is_wp_error($new_post_id)) {
			$meta_data = get_post_meta($post_id);
			foreach ($meta_data as $key => $values) {
				foreach ($values as $value) {
					add_post_meta($new_post_id, $key, maybe_unserialize($value));
				}
			}
			// Optionally copy post thumbnail
			$thumbnail_id = get_post_thumbnail_id($post_id);
			if ($thumbnail_id) {
				set_post_thumbnail($new_post_id, $thumbnail_id);
			}

			// Optionally copy taxonomy terms
			$taxonomies = get_object_taxonomies($post->post_type);
			foreach ($taxonomies as $taxonomy) {
				$terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'ids'));
				wp_set_object_terms($new_post_id, $terms, $taxonomy);
			}
		}

		return $new_post_id;
	}

	public function site_builder_copy_post($request)
	{
		$post_id = $request->get_param('post_id');
		$new_post_id = $this->copy_post($post_id);
		return new WP_REST_Response(
			array(
				'post_id' => $new_post_id,
			)
		);
	}

	public function site_builder_get_template_data($request)
	{
		$user_email = $request->get_param('user_email');
		if (isset($user_email) && "" !== $user_email) {
			$user = get_user_by('email', $user_email);
			if ($user) {
				wp_set_current_user($user->ID, $user->data->display_name);
			} else {
				return new WP_REST_Response(
					array(
						'error'            => __('There was an error on get template', 'quickcreator'),
						'wp_error_message' => 'user not found',
					),
					403
				);
			}
		}
		$args = $request->get_param('args');
		$if_elementor_is_active = quickcreator_check_if_plugins_is_active('elementor/elementor.php');
		if ($if_elementor_is_active) {
			$template_data = \Elementor\Plugin::$instance->templates_manager->get_template_data($args);
			return new WP_REST_Response(
				array(
					'template_data' => $template_data,
				)
			);
		} else {
			return new WP_REST_Response(
				array(
					'error'            => __('There was an error on get template', 'quickcreator'),
					'wp_error_message' => 'plugin elementor is not active',
				),
				403
			);
		}
	}

	public function site_builder_get_library_data($request)
	{
		$args = $request->get_json_params();
		$if_elementor_is_active = quickcreator_check_if_plugins_is_active('elementor/elementor.php');
		if ($if_elementor_is_active) {
			$library_data = \Elementor\Plugin::$instance->templates_manager->get_library_data($args);
			return new WP_REST_Response(
				array(
					'library_data' => $library_data,
				)
			);
		} else {
			return new WP_REST_Response(
				array(
					'error'            => __('There was an error on get library', 'quickcreator'),
					'wp_error_message' => 'plugin elementor is not active',
				),
				403
			);
		}
	}

	public function site_builder_install_plugin_upload($request)
	{
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		if (isset($_FILES['pluginzip']['name']) && ! str_ends_with(strtolower($_FILES['pluginzip']['name']), '.zip')) {
			wp_die(__('Only .zip archives may be uploaded.'));
		}

		$file_upload = new File_Upload_Upgrader('pluginzip', 'package');
		$title = sprintf(__('Installing plugin from uploaded file: %s'), esc_html(basename($file_upload->filename)));
		$nonce = 'plugin-upload';
		$type  = 'upload';
		$upgrader = new Plugin_Upgrader(new QuietSkin(compact('type', 'title', 'nonce', 'overwrite')));
		$result   = $upgrader->install($file_upload->package, array('overwrite_package' => $overwrite));
		$installed = false;
		if ($result || is_wp_error($result)) {
			$file_upload->cleanup();
		}
		if ($result) {
			$plugin_slug = $request->get_param('plugin_slug');
			$installed = activate_plugin(WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . $plugin_slug . '.php');
		}
		return new WP_REST_Response(
			array(
				'installed' => $installed,
			)
		);
	}

	private function install_plugin($plugin_slug, $main)
	{
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		$plugin_info = plugins_api('plugin_information', array('slug' => $plugin_slug));

		if (is_wp_error($plugin_info)) {
			return $plugin_info; // Return error if any
		}

		$skin = new QuietSkin(array('api' => $plugin_info));
		$upgrader = new Plugin_Upgrader($skin);
		$installed = $upgrader->install($plugin_info->download_link);

		if (!is_wp_error($installed)) {
			activate_plugin(WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . ($main ?? $plugin_slug) . '.php');
			return true;
		}

		return false;
	}

	public function site_builder_install_plugin($request)
	{
		$plugin_slug = $request->get_param('plugin_slug');
		$main = $request->get_param('main');
		$installed = $this->install_plugin($plugin_slug, $main);
		return new WP_REST_Response(
			array(
				'installed' => $installed,
			)
		);
	}

	private function install_theme($theme)
	{
		require_once ABSPATH . 'wp-admin/includes/theme.php';
		require_once ABSPATH . 'wp-admin/includes/theme-install.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';

		$api = themes_api(
			'theme_information',
			array(
				'slug'   => $theme,
				'fields' => array(
					'sections' => false,
					'tags'     => false,
				),
			)
		);

		$skin = new QuieThmeSkin(array('api' => $api));
		$upgrader = new Theme_Upgrader($skin);
		$installed = $upgrader->install($api->download_link);

		if (!is_wp_error($installed)) {
			switch_theme($theme);
			return true;
		}

		return false;
	}

	public function site_builder_install_theme($request)
	{
		$theme = $request->get_param('theme');
		$installed = $this->install_theme($theme);
		return new WP_REST_Response(
			array(
				'installed' => $installed,
			)
		);
	}

	public function site_builder_theme_builder_save_conditions($request)
	{
		$template_id = $request->get_param("template_id");
		$conditions = $request->get_param("conditions");
		$status = quickcreator_elementor_pro_theme_builder_save_conditions($template_id, $conditions);
		return new WP_REST_Response(
			array(
				"status" => $status
			)
		);
	}
}
