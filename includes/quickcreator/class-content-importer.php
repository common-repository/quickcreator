<?php

/**
 *  Object that handle importing content from:
 *  - Quickcreator
 *
 * @package QuickcreatorBlog
 * @link https://quickcreator.io
 */

namespace QuickcreatorBlog\Quickcreator;

use QuickcreatorBlog\Quickcreatorblog;
use QuickcreatorBlog\Quickcreator\Content_Parsers\Parsers_Controller;



/**
 * Object that imports data from different sources into WordPress.
 */
class Content_Importer
{

	/**
	 * Object to manager content parsing for different editors.
	 *
	 * @var Parsers_Controller
	 */
	protected $content_parser = null;


	/**
	 * Basic construct.
	 */
	public function __construct()
	{
		$this->content_parser = new Parsers_Controller();
	}


	/**
	 * Save imported data in database.
	 *
	 * @param string $content - post content.
	 * @param array  $args    - array of optional params.
	 * @return int|WP_Error
	 */
	public function save_data_into_database($content, $args = array())
	{
		$user = get_users()[0];
		wp_set_current_user($user->ID, $user->data->display_name);
		if ('elementor' === $args['filter']) {
			$if_elementor_is_active = quickcreator_check_if_plugins_is_active('elementor/elementor.php');
			if ($if_elementor_is_active) {
				$rawContent = "";
			}
		} else if ('raw' !== $args['filter']) {
			$rawContent = $this->content_parser->parse_content($content);
		} else {
			$this->content_parser->choose_parser();
			$rawContent = $content;
		}
		$title = isset($args['post_title']) && strlen($args['post_title']) > 0 ? $args['post_title'] : $this->content_parser->return_title();

		$data = array(
			'post_title'   => $title,
			'post_content' => $rawContent,
		);

		if (isset($args['post_id']) && $args['post_id'] > 0) {

			$provided_post_id = $args['post_id'];
			$data['ID']       = $provided_post_id;
			// $post             = (array) get_post( $provided_post_id );
			// Create copy of the post as a backup.
			// unset( $post['ID'] );
			// $post['post_status'] = 'quickcreator-backup';
			// wp_insert_post( $post );
		}

		$this->resolve_post_author($args, $data);
		$this->resolve_post_status($args, $data);
		$this->resolve_post_type($args, $data);
		$this->resolve_post_permalink($args, $data);
		$this->resolve_post_category($args, $data);
		$this->resolve_post_tags($args, $data);
		$this->resolve_post_meta_details($args, $data);
		$this->resolve_post_excerpt($args, $data);
		$this->resolve_post_parent($args, $data);

		if (isset($post) && 'published' === $post['post_status']) {
			// WordPress set current date as default and we do not want to change publication date.
			$data['post_date'] = $post['post_date'];
		} else {
			$this->resolve_post_date($args, $data);
		}

		$post_id = wp_insert_post($data, true);

		if (! is_wp_error($post_id) && isset($args['draft_id'])) {
			update_post_meta($post_id, 'quickcreator_draft_id', $args['draft_id']);
			update_post_meta($post_id, 'quickcreator_permalink_hash', isset($args['permalink_hash']) ? $args['permalink_hash'] : '');
			update_post_meta($post_id, 'quickcreator_keywords', $args['keywords']);
			update_post_meta($post_id, 'quickcreator_location', $args['location']);
			update_post_meta($post_id, 'quickcreator_scrape_ready', true);
			update_post_meta($post_id, 'quickcreator_last_post_update', round(microtime(true) * 1000));
			update_post_meta($post_id, 'quickcreator_last_post_update_direction', 'from Quickcreator to WordPress');
		}

		$this->resove_post_meta_after_insert_post($post_id, $args);

		if ('elementor' === $args['filter']) {
			$if_elementor_is_active = quickcreator_check_if_plugins_is_active('elementor/elementor.php');
			if ($if_elementor_is_active) {
				\Elementor\Plugin::$instance->documents->ajax_save([
					'editor_post_id' => $post_id,
					'status' => $data["post_status"],
					'elements' => $content['elements'],
					'settings' => $content['settings'],
				]);
				if (isset($args['meta_input']) && isset($args['meta_input']['_elementor_template_type']) && "kit" === $args['meta_input']['_elementor_template_type']) {
					$kits_manager = \Elementor\Plugin::$instance->kits_manager;
					update_option($kits_manager::OPTION_PREVIOUS, $kits_manager->get_active_id());
					update_option($kits_manager::OPTION_ACTIVE, $post_id);
				}
			}
		} else {
			$this->content_parser->run_after_post_insert_actions($post_id);
		}
		return $post_id;
	}

	/**
	 * Saves image from provided URL into WordPress media library
	 *
	 * @param string $image_url - URL to the image.
	 * @param string $image_alt - Alternative text for the image.
	 * @return array URL to image in media library.
	 */
	public function upload_img_to_media_library($image_url, $image_alt)
	{
		$image_data = $this->content_parser->upload_img_to_media_library($image_url, $image_alt);
		$image_url  = $image_data['url'];
		$image_id   = $image_data['id'];
		$file_name  = $image_data['file_name'];
		$metadata = wp_get_attachment_metadata($image_id);
		return array(
			'id'             => $image_id,
			'title'          => $file_name,
			'source_url'     => $image_url,
			'link'           => get_permalink($image_id),
			'alt_text'       => $image_alt,
			'media_details'  => $metadata
		);
	}

	/**
	 * Fill $data array with proper attribute for post_author or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_author($args, &$data)
	{

		if (isset($args['post_author']) && '' !== $args['post_author']) {

			$value = $args['post_author'];

			if (is_numeric($value) && $value > 0) {
				$data['post_author'] = $value;
			} else {
				$data['post_author'] = $this->get_user_id_by_login($value);
			}
		} else {
			$default = Quickcreator()->get_quickcreator_settings()->get_option('content-importer', 'default_post_author', false);

			if (false !== $default) {
				$data['post_author'] = $default;
			}
		}
	}

	/**
	 * Fill $data array with proper attribute for post_excerpt or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_excerpt($args, &$data)
	{
		if (isset($args['post_excerpt']) && '' !== $args['post_excerpt']) {
			$data['post_excerpt'] = $args['post_excerpt'];
		}
	}

	/**
	 * Fill $data array with proper attribute for post_status or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_status($args, &$data)
	{

		$allowed_statuses = array('publish', 'draft', 'pending', 'future', 'private');

		if (isset($args['post_status']) && '' !== $args['post_status'] && in_array($args['post_status'], $allowed_statuses, true)) {
			$data['post_status'] = $args['post_status'];
		} else {
			$default = Quickcreatorblog::get_instance()->get_quickcreator_settings()->get_option('content-importer', 'default_post_status', false);

			if (false !== $default) {
				$data['post_status'] = $default;
			}
		}
	}

	/**
	 * Fill $data array with proper attribute for post_type or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_type($args, &$data)
	{

		$allowed_post_types = quickcreator_return_supported_post_types();

		if (isset($args['post_type']) && '' !== $args['post_type'] && in_array($args['post_type'], $allowed_post_types, true)) {
			$data['post_type'] = $args['post_type'];
		} else {
			$data['post_type'] = 'post';
		}
	}

	/**
	 * Fill $data array with proper attribute for post_date or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_date($args, &$data)
	{

		if (isset($args['post_date']) && strtotime($args['post_date']) > time()) {
			$data['post_date'] = gmdate('Y-m-d H:i:s', strtotime($args['post_date']));
		}
	}

	/**
	 * Fill $data array with proper attribute for post_parent or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_parent($args, &$data)
	{

		if (isset($args['post_parent']) && '' !== $args['post_parent']) {
			$data['post_parent'] = $args['post_parent'];
		}
	}


	/**
	 * Fill $data array with proper attribute for post_name or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_permalink($args, &$data)
	{

		if (isset($args['post_name']) && '' !== $args['post_name']) {
			$data['post_name'] = $args['post_name'];
		}
	}

	/**
	 * Fill $data array with proper attribute for post_category or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_category($args, &$data)
	{

		if (isset($args['post_category']) && is_array($args['post_category']) && ! empty($args['post_category'])) {

			$categories = array();
			foreach ($args['post_category'] as $category) {
				$categories[] = $category;
			}

			$data['post_category'] = $categories;
		} else {
			$default = Quickcreatorblog::get_instance()->get_quickcreator_settings()->get_option('content-importer', 'default_category', false);

			if (false !== $default) {
				$data['post_category'] = array($default);
			}
		}
	}

	/**
	 * Fill $data array with proper attribute for tags_input or leave empty to fill default.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_tags($args, &$data)
	{

		if (isset($args['post_tags']) && is_array($args['post_tags']) && count($args['post_tags']) > 0) {

			$tags = array();
			foreach ($args['post_tags'] as $tag) {
				$tags[] = $tag;
			}

			$data['tags_input'] = $tags;
		} else {
			$default = Quickcreatorblog::get_instance()->get_quickcreator_settings()->get_option('content-importer', 'default_tags', false);

			if (false !== $default) {
				$data['tags_input'] = $default;
			}
		}
	}

	/**
	 * Fill the meta_title and meta_description if any SEO plugin is active.
	 *
	 * @param array $args - array of arguments pasted to request.
	 * @param array $data - pointer to array where we store data to put into post.
	 * @return void
	 */
	private function resolve_post_meta_details($args, &$data)
	{

		$seo_plugin_is_active = false;

		if (! isset($data['meta_input'])) {
			$data['meta_input'] = array();
		}

		if (isset($args['meta_input'])) {
			foreach ($args['meta_input'] as $key => $value) {
				$data['meta_input'][$key] = $value;
			}
		}

		if (isset($args['featured_image']) && '' !== $args['featured_image']) {
			if (is_int($args['featured_image'])) {
				$data['meta_input']['_thumbnail_id'] = $args['featured_image'];
			} else {
				$image_data = $this->content_parser->upload_img_to_media_library($args['featured_image'], '', false);
				$data['meta_input']['_thumbnail_id'] = $image_data['id'];
			}
		}

		// Yoast SEO is active.
		if (quickcreator_check_if_plugins_is_active('wordpress-seo/wp-seo.php')) {

			if (isset($args['meta_title']) && '' !== $args['meta_title']) {
				$data['meta_input']['_yoast_wpseo_title'] = $args['meta_title'];
			}

			if (isset($args['meta_description']) && '' !== $args['meta_description']) {
				$data['meta_input']['_yoast_wpseo_metadesc'] = $args['meta_description'];
			}

			$seo_plugin_is_active = true;
		}

		if (quickcreator_check_if_plugins_is_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
			$seo_plugin_is_active = true;
		}

		// Rank Math SEO.
		if (quickcreator_check_if_plugins_is_active('seo-by-rank-math/rank-math.php')) {

			if (isset($args['meta_title']) && '' !== $args['meta_title']) {
				$data['meta_input']['rank_math_title'] = $args['meta_title'];
			}

			if (isset($args['meta_description']) && '' !== $args['meta_description']) {
				$data['meta_input']['rank_math_description'] = $args['meta_description'];
			}

			$seo_plugin_is_active = true;
		}

		// Save in quickcreator Meta to display.
		if (! $seo_plugin_is_active) {

			if (isset($args['meta_title']) && '' !== $args['meta_title']) {
				$data['meta_input']['_quickcreatorblog_title'] = $args['meta_title'];
			}

			if (isset($args['meta_description']) && '' !== $args['meta_description']) {
				$data['meta_input']['_quickcreatorblog_description'] = $args['meta_description'];
			}
		}
	}

	private function resove_post_meta_after_insert_post($post_id, $args)
	{
		if (quickcreator_check_if_plugins_is_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
			$seo_data = [];
			if (isset($args['meta_title']) && '' !== $args['meta_title']) {
				$seo_data['title'] = $args['meta_title'];
			}

			if (isset($args['meta_description']) && '' !== $args['meta_description']) {
				$seo_data['description'] = $args['meta_description'];
			}
			\AIOSEO\Plugin\Common\Models\Post::savePost($post_id, $seo_data);
		}
	}


	/**
	 * Returns ID of the user with given name.
	 *
	 * @param string $login - login of the user.
	 * @return int
	 */
	private function get_user_id_by_login($login = false)
	{

		$user_id = 0;
		$user    = get_user_by('login', $login);

		if (false !== $user) {
			$user_id = get_option('quickcreator_auth_user', 0);
		}

		return $user_id;
	}


	/**
	 * Checks if plugin is active even if default function is not loaded.
	 *
	 * @param string $plugin - plugin name to check.
	 * @return bool
	 */
	public function check_if_plugins_is_active($plugin)
	{

		if (! function_exists('is_plugin_active')) {
			return in_array($plugin, (array) get_option('active_plugins', array()), true);
		} else {
			return is_plugin_active($plugin);
		}
	}
}
