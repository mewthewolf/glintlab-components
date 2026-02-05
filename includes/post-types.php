<?php
/**
 * Custom Post Types and Meta Boxes.
 *
 * @package GlintLabComponents
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register the Team Member Custom Post Type.
 */
function glintlab_components_register_team_cpt()
{
	$labels = array(
		'name' => _x('Team Members', 'post type general name', 'glintlab-components'),
		'singular_name' => _x('Team Member', 'post type singular name', 'glintlab-components'),
		'menu_name' => _x('Team Members', 'admin menu', 'glintlab-components'),
		'add_new' => _x('Add New', 'team member', 'glintlab-components'),
		'add_new_item' => __('Add New Team Member', 'glintlab-components'),
		'new_item' => __('New Team Member', 'glintlab-components'),
		'edit_item' => __('Edit Team Member', 'glintlab-components'),
		'view_item' => __('View Team Member', 'glintlab-components'),
		'all_items' => __('All Team Members', 'glintlab-components'),
		'search_items' => __('Search Team Members', 'glintlab-components'),
		'not_found' => __('No team members found.', 'glintlab-components'),
		'not_found_in_trash' => __('No team members found in Trash.', 'glintlab-components'),
	);

	$args = array(
		'labels' => $labels,
		'public' => false, // We use it for shortcodes, not individual pages
		'publicly_queryable' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'query_var' => true,
		'rewrite' => array('slug' => 'team-member'),
		'capability_type' => 'post',
		'has_archive' => false,
		'hierarchical' => false,
		'menu_position' => 20,
		'menu_icon' => 'dashicons-groups',
		'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'), // page-attributes for ordering
		'show_in_rest' => true,
		'template' => array(
			array('glintlab/team-profile-header', array()),
			array('core/paragraph', array('placeholder' => 'Enter team member bio/description here...')),
		),
		'template_lock' => 'all', // Prevent adding/moving/deleting blocks
	);

	register_post_type('glintlab_team', $args);

	// Register meta for REST API access (Gutenberg)
	register_post_meta('glintlab_team', '_glintlab_team_role', array(
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
	));

	register_post_meta('glintlab_team', '_glintlab_team_link_url', array(
		'show_in_rest' => true,
		'single' => true,
		'type' => 'string',
	));
}
add_action('init', 'glintlab_components_register_team_cpt');

/**
 * Disable the block editor (Gutenberg) for the Team Member CPT.
 *
 * This CPT is intentionally form-like and is easier to manage with the classic editor + meta boxes.
 */
function glintlab_components_disable_block_editor_for_team_cpt($use_block_editor, $post_type)
{
	if ('glintlab_team' === $post_type) {
		return false;
	}

	return $use_block_editor;
}
add_filter('use_block_editor_for_post_type', 'glintlab_components_disable_block_editor_for_team_cpt', 10, 2);
add_filter('gutenberg_can_edit_post_type', 'glintlab_components_disable_block_editor_for_team_cpt', 10, 2);

/**
 * Customize the title placeholder for Team Members.
 */
function glintlab_components_team_member_title_placeholder($title_placeholder, $post)
{
	if ($post && 'glintlab_team' === $post->post_type) {
		return __('Add Name', 'glintlab-components');
	}

	return $title_placeholder;
}
add_filter('enter_title_here', 'glintlab_components_team_member_title_placeholder', 10, 2);

/**
 * Add Meta Boxes for Team Member Details.
 */
function glintlab_components_add_team_meta_boxes()
{
	add_meta_box(
		'glintlab_team_details',
		__('Team Member Details', 'glintlab-components'),
		'glintlab_components_team_details_callback',
		'glintlab_team',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'glintlab_components_add_team_meta_boxes');

/**
 * Render the Meta Box.
 */
function glintlab_components_team_details_callback($post)
{
	wp_nonce_field('glintlab_team_details_save', 'glintlab_team_details_nonce');

	$role = get_post_meta($post->ID, '_glintlab_team_role', true);
	$link_url = get_post_meta($post->ID, '_glintlab_team_link_url', true);
	?>
	<div class="glintlab-meta-field">
		<p>
			<label
				for="glintlab_team_role"><strong><?php _e('Job Title / Role', 'glintlab-components'); ?></strong></label><br>
			<input type="text" id="glintlab_team_role" name="glintlab_team_role" value="<?php echo esc_attr($role); ?>"
				class="widefat" />
		</p>
		<p>
			<label
				for="glintlab_team_link_url"><strong><?php _e('LinkedIn / Profile URL', 'glintlab-components'); ?></strong></label><br>
			<input type="url" id="glintlab_team_link_url" name="glintlab_team_link_url"
				value="<?php echo esc_url($link_url); ?>" class="widefat" />
		</p>
	</div>
	<?php
}

/**
 * Save Meta Box Data.
 */
function glintlab_components_save_team_meta($post_id)
{
	if (!isset($_POST['glintlab_team_details_nonce'])) {
		return;
	}
	if (!wp_verify_nonce($_POST['glintlab_team_details_nonce'], 'glintlab_team_details_save')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (isset($_POST['glintlab_team_role'])) {
		update_post_meta($post_id, '_glintlab_team_role', sanitize_text_field($_POST['glintlab_team_role']));
	}
	if (isset($_POST['glintlab_team_link_url'])) {
		update_post_meta($post_id, '_glintlab_team_link_url', esc_url_raw($_POST['glintlab_team_link_url']));
	}
}
add_action('save_post', 'glintlab_components_save_team_meta');
