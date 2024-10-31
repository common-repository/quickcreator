<?php

/**
 * Stores general purpose functions to use in multiple places.
 *
 * @package QuickcreatorBlog
 */

/**
 * Verifies if user can perform ajax action
 *
 * @param string $nonce_name Nonce name.
 * @param string $action Action name.
 * @return bool
 */
function quickcreator_validate_ajax_request($nonce_name = '_quickcreator_nonce', $action = 'quickcreator-ajax-nonce')
{

	if (! current_user_can('manage_options')) {
		return false;
	}

	if (! check_ajax_referer($action, $nonce_name, false)) {
		return false;
	}

	return true;
}

/**
 * Checks if plugin is active even if default function is not loaded.
 *
 * @param string $plugin - plugin name to check.
 * @return bool
 */
function quickcreator_check_if_plugins_is_active($plugin)
{

	if (! function_exists('is_plugin_active')) {
		return in_array($plugin, (array) get_option('active_plugins', array()), true);
	} else {
		return is_plugin_active($plugin);
	}
}


/**
 * Returns supported post types.
 *
 * @param bool $quickcreator_select_prepared - if true will return value and label parirs.
 * @return array
 */
function quickcreator_return_supported_post_types($quickcreator_select_prepared = false)
{

	$post_types = get_post_types(array('public' => true), 'objects');

	$post_types = array_filter(
		$post_types,
		function ($post_type) {
			return ! in_array($post_type->name, array('attachment', 'revision', 'nav_menu_item'), true);
		}
	);

	if (true === $quickcreator_select_prepared) {
		$filtered_post_types = array();
		foreach ($post_types as $type) {
			$filtered_post_types[] = array(
				'label' => $type->label,
				'value' => $type->name,
				'capability_type' => $type->capability_type,
			);
		}
		$post_types = $filtered_post_types;
	} else {
		$post_types = array_map(
			function ($post_type) {
				return $post_type->name;
			},
			$post_types
		);
	}

	return apply_filters('quickcreator_supported_post_types', $post_types);
}

/**
 * Register taxonomy if not present.
 *
 * @param string $plural_label - plural label.
 * @param string $singular_label - singular label.
 * @param string $taxonomy_key - taxonomy key.
 * @param array $post_types - post types.
 * @param bool $hierarchical - if true will be hierarchical.
 * @return void
 */
function quickcreator_register_taxonomy($plural_label, $singular_label, $taxonomy_key, $post_types, $hierarchical)
{
	$taxonomy_key = sanitize_key($taxonomy_key);
	if (!taxonomy_exists($taxonomy_key)) {
		$labels = array(
			'name' => $plural_label,
			'singular_name' => $singular_label,
			'search_items' => 'Search ' . $plural_label,
			'all_items' => 'All ' . $plural_label,
			'parent_item' => 'Parent ' . $singular_label,
			'parent_item_colon' => 'Parent ' . $singular_label . ':',
			'edit_item' => 'Edit ' . $singular_label,
			'update_item' => 'Update ' . $singular_label,
			'add_new_item' => 'Add New ' . $singular_label,
			'new_item_name' => 'New ' . $singular_label . ' Name',
			'menu_name' => $plural_label,
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => $hierarchical,
			'rewrite' => array('slug' => $taxonomy_key),

			'taxonomy' => $taxonomy_key,
			'object_type' => $post_types,
			'public' => 1,
			'hierarchical' => $hierarchical,
			'advanced_configuration' => 1,
			'key' => $taxonomy_key,
			'import_source' => '',
			'import_date' => '',
			'sort' => 0,
			'default_term' => [
				'default_term_enabled' => 0
			],
			'description' => '',
			'active' => 1,
			'show_ui' => 1,
			'show_in_menu' => 1,
			'meta_box' => 'default',
			'show_in_nav_menus' => 1,
			'show_tagcloud' => 1,
			'show_in_quick_edit' => 1,
			'show_admin_column' => 1,
			'rewrite' => [
				'permalink_rewrite' => 'taxonomy_key',
				'with_front' => 1,
				'rewrite_hierarchical' => 0
			],
			'publicly_queryable' => 1,
			'query_var' => 'post_type_key',
			'capabilities' => [
				'manage_terms' => 'manage_categories',
				'edit_terms' => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts'
			],
			'show_in_rest' => 1,
			'rest_base' => '',
			'rest_namespace' => 'wp/v2',
			'rest_controller_class' => 'WP_REST_Terms_Controller'
		);

		if (quickcreator_check_if_plugins_is_active('advanced-custom-fields/acf.php')) {
			return acf_update_internal_post_type($args, "acf-taxonomy");
		}
	}
}

/**
 * Register post type if not present.
 *
 * @param string $plural_label - plural label.
 * @param string $singular_label - singular label.
 * @param string $post_type_key - post type key.
 * @param array $supports - supported features.
 * @param array $taxonomies - taxonomies.
 * @return void
 */
function quickcreator_register_post_type($plural_label, $singular_label, $post_type_key, $supports, $taxonomies = array())
{
	$post_type_key = sanitize_key($post_type_key);
	if (!post_type_exists($post_type_key)) {
		$labels = array(
			'name' => $plural_label,
			'singular_name' => $singular_label,
			'add_new' => 'Add New',
			'add_new_item' => 'Add New ' . $singular_label,
			'edit_item' => 'Edit ' . $singular_label,
			'new_item' => 'New ' . $singular_label,
			'all_items' => 'All ' . $plural_label,
			'view_item' => 'View ' . $singular_label,
			'view_items' => 'View ' . $plural_label,
			'search_items' => 'Search ' . $plural_label,
			'not_found' => 'No ' . $plural_label . ' found',
			'not_found_in_trash' => 'No ' . $plural_label . ' found in Trash',
			'parent_item_colon' => 'Parent ' . $singular_label . ':',
			'archives' => $singular_label . ' Archives',
			'attributes' => $singular_label . ' Attributes',
			'insert_into_item' => 'Insert into ' . $singular_label,
			'uploaded_to_this_item' => 'Uploaded to this ' . $singular_label,
			'featured_image' => 'Featured Image',
			'set_featured_image' => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image' => 'Use as featured image',
			'menu_name' => $plural_label,
			'filter_items_list' => 'Filter ' . $plural_label . ' list',
			'filter_by_date' => 'Filter by date',
			'items_list_navigation' => $plural_label . ' list navigation',
			'items_list' => $plural_label . ' list',
			'item_published' => $singular_label . ' published.',
			'item_published_privately' => $singular_label . ' published privately.',
			'item_reverted_to_draft' => $singular_label . ' reverted to draft.',
			'item_scheduled' => $singular_label . ' scheduled.',
			'item_updated' => $singular_label . ' updated.',
			'item_link' => $singular_label . ' Link',
			'item_link_description' => 'A link to a ' . $singular_label . '.',
		);

		$args = array(
			'title' => $plural_label,
			'key'   => $post_type_key,
			'labels' => $labels,
			'post_type' => $post_type_key,
			'taxonomies' => $taxonomies,
			'public' => '1',
			'hierarchical' => '0',
			'advanced_configuration' => '1',
			'import_source' => '',
			'import_date' => '',
			'supports' => $supports,
			'description' => '',
			'active' => '1',
			'show_ui' => '1',
			'show_in_menu' => '1',
			'admin_menu_parent' => '',
			'menu_position' => '',
			'menu_icon' => [
				'type' => 'dashicons',
				'value' => 'dashicons-admin-post'
			],
			'show_in_admin_bar' => '1',
			'show_in_nav_menus' => '1',
			'exclude_from_search' => '0',
			'rewrite' => [
				'permalink_rewrite' => 'post_type_key',
				'with_front' => '1',
				'feeds' => '0',
				'pages' => '1'
			],
			'has_archive' => '0',
			'publicly_queryable' => '1',
			'query_var' => 'post_type_key',
			'rename_capabilities' => '0',
			'can_export' => '1',
			'delete_with_user' => '0',
			'show_in_rest' => '1',
			'rest_base' => '',
			'rest_namespace' => 'wp/v2',
			'rest_controller_class' => 'WP_REST_Posts_Controller'

		);

		if (quickcreator_check_if_plugins_is_active('advanced-custom-fields/acf.php')) {
			return acf_update_internal_post_type($args, "acf-post-type");
		}
		return null;
	}
}

function quickcreator_elementor_pro_theme_builder_save_conditions($template_id, $conditions)
{
	if (quickcreator_check_if_plugins_is_active('elementor-pro/elementor-pro.php')) {
		/** @var ThemeBuilderModule $theme_builder_module */
		$theme_builder_module = \ElementorPro\Plugin::instance()->modules_manager->get_modules('theme-builder');
		$theme_builder_module->get_conditions_manager()->save_conditions($template_id, $conditions);
		return true;
	}
	return false;
}

function quickcreator_elementor_pro_license_active($lecense_key)
{
	if (quickcreator_check_if_plugins_is_active('elementor-pro/elementor-pro.php')) {
		$data = \ElementorPro\License\API::activate_license($lecense_key);
		if (is_wp_error($data)) {
			wp_die(sprintf('%s (%s) ', wp_kses_post($data->get_error_message()), wp_kses_post($data->get_error_code())), esc_html__('Elementor Pro', 'elementor-pro'), [
				'back_link' => true,
			]);
		}

		if (empty($data['success'])) {
			$error_msg = \ElementorPro\License\API::get_error_message($data['error']);
			wp_die(wp_kses_post($error_msg), esc_html__('Elementor Pro', 'elementor-pro'), [
				'back_link' => true,
			]);
		}

		\ElementorPro\License\Admin::set_license_key($lecense_key);
		\ElementorPro\License\API::set_license_data($data);
	}
	return false;
}
