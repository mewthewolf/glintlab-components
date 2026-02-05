<?php
/**
 * Demo content importer (Team Members).
 *
 * @package GlintLabComponents
 */
 
if (!defined('ABSPATH')) {
	exit;
}

function glintlab_components_demo_importer_admin_notice()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$result = get_transient('glintlab_components_demo_import_result');
	if (!is_array($result)) {
		return;
	}

	delete_transient('glintlab_components_demo_import_result');

	$type = isset($result['type']) ? (string) $result['type'] : 'success';
	$message = isset($result['message']) ? (string) $result['message'] : '';
	if ('' === $message) {
		return;
	}

	$class = 'notice notice-success';
	if ('warning' === $type) {
		$class = 'notice notice-warning';
	} elseif ('error' === $type) {
		$class = 'notice notice-error';
	}

	printf('<div class="%s"><p>%s</p></div>', esc_attr($class), esc_html($message));
}
add_action('admin_notices', 'glintlab_components_demo_importer_admin_notice');

function glintlab_components_demo_importer_maybe_auto_import()
{
	// Allow disabling auto-import (e.g. production installs).
	if (defined('GLINTLAB_COMPONENTS_DISABLE_DEMO_IMPORT') && GLINTLAB_COMPONENTS_DISABLE_DEMO_IMPORT) {
		return;
	}

	$done = get_option('glintlab_components_demo_auto_import_done', false);
	if ($done) {
		return;
	}

	$count_obj = wp_count_posts('glintlab_team');
	$existing = 0;
	if ($count_obj) {
		foreach (get_object_vars($count_obj) as $v) {
			$existing += (int) $v;
		}
	}

	if ($existing > 0) {
		update_option('glintlab_components_demo_auto_import_done', 1);
		set_transient(
			'glintlab_components_demo_import_result',
			array(
				'type' => 'warning',
				'message' => 'GlintLab Components: demo import skipped because Team Members already exist. You can re-run it from Tools → GlintLab Demo Import.',
			),
			5 * MINUTE_IN_SECONDS
		);
		return;
	}

	$results = glintlab_components_demo_importer_import_team_members();
	update_option('glintlab_components_demo_auto_import_done', 1);

	if (isset($results['error'])) {
		set_transient(
			'glintlab_components_demo_import_result',
			array(
				'type' => 'error',
				'message' => 'GlintLab Components: demo import failed: ' . (string) $results['error'],
			),
			5 * MINUTE_IN_SECONDS
		);
		return;
	}

	set_transient(
		'glintlab_components_demo_import_result',
		array(
			'type' => 'success',
			'message' => sprintf(
				'GlintLab Components: demo Team Members imported (%d created, %d skipped, %d images).',
				(int) ($results['created'] ?? 0),
				(int) ($results['skipped'] ?? 0),
				(int) ($results['images'] ?? 0)
			),
		),
		5 * MINUTE_IN_SECONDS
	);
}

function glintlab_components_demo_importer_register_menu()
{
	add_management_page(
		'GlintLab Demo Import',
		'GlintLab Demo Import',
		'manage_options',
		'glintlab-components-demo-import',
		'glintlab_components_demo_importer_render_page'
	);
}
add_action('admin_menu', 'glintlab_components_demo_importer_register_menu');

function glintlab_components_demo_importer_render_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$results = null;
	$error = '';

	if (isset($_POST['glintlab_demo_action'])) {
		check_admin_referer('glintlab_components_demo_import', 'glintlab_components_demo_nonce');
		$action = sanitize_text_field(wp_unslash($_POST['glintlab_demo_action']));

		if ('import' === $action) {
			$results = glintlab_components_demo_importer_import_team_members();
			if (isset($results['error'])) {
				$error = (string) $results['error'];
			}
		}
	}

	$imported_at = (string) get_option('glintlab_components_demo_imported_at', '');
	$count_obj = wp_count_posts('glintlab_team');
	$existing = 0;
	if ($count_obj) {
		foreach (get_object_vars($count_obj) as $k => $v) {
			$existing += (int) $v;
		}
	}

	?>
	<div class="wrap">
		<h1>GlintLab Demo Import</h1>
		<p>Imports demo <strong>Team Members</strong> (posts, role/link meta, and featured images) bundled inside this plugin.</p>
		<p><strong>Auto-import:</strong> if no Team Members exist, demo content is imported automatically on plugin activation.</p>
		<?php if ('' !== $imported_at): ?>
			<p><em>Last import recorded:</em> <?php echo esc_html($imported_at); ?></p>
		<?php endif; ?>
		<p><em>Current Team Members in this site:</em> <?php echo esc_html((string) $existing); ?></p>

		<?php if ('' !== $error): ?>
			<div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
		<?php endif; ?>

		<?php if (is_array($results) && empty($error)): ?>
			<div class="notice notice-success">
				<p>
					Imported: <?php echo esc_html((string) ($results['created'] ?? 0)); ?>,
					Skipped (already exists): <?php echo esc_html((string) ($results['skipped'] ?? 0)); ?>,
					Images imported: <?php echo esc_html((string) ($results['images'] ?? 0)); ?>.
				</p>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field('glintlab_components_demo_import', 'glintlab_components_demo_nonce'); ?>
			<input type="hidden" name="glintlab_demo_action" value="import" />
			<?php submit_button('Import Demo Team Members'); ?>
		</form>

		<p style="max-width: 720px;">
			<strong>Note:</strong> this importer is intentionally safe-by-default. If a Team Member post with the same title already exists, it will be skipped.
		</p>
	</div>
	<?php
}

function glintlab_components_demo_importer_import_team_members()
{
	$json_path = GLINTLAB_COMPONENTS_DIR . 'assets/demo/team-members.json';
	if (!is_file($json_path)) {
		return array('error' => 'Demo data file not found: assets/demo/team-members.json');
	}

	$raw = file_get_contents($json_path);
	if (false === $raw) {
		return array('error' => 'Unable to read demo data file.');
	}

	$decoded = json_decode($raw, true);
	if (!is_array($decoded) || empty($decoded['items']) || !is_array($decoded['items'])) {
		return array('error' => 'Demo data file is invalid or empty.');
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$created = 0;
	$skipped = 0;
	$images = 0;

	foreach ($decoded['items'] as $item) {
		if (!is_array($item)) {
			continue;
		}

		$title = isset($item['title']) ? (string) $item['title'] : '';
		if ('' === trim($title)) {
			continue;
		}

		$existing = get_page_by_title($title, OBJECT, 'glintlab_team');
		if ($existing) {
			$skipped++;
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type' => 'glintlab_team',
				'post_status' => isset($item['status']) ? (string) $item['status'] : 'publish',
				'post_title' => $title,
				'post_content' => isset($item['content']) ? (string) $item['content'] : '',
				'menu_order' => isset($item['menuOrder']) ? (int) $item['menuOrder'] : 0,
			),
			true
		);

		if (is_wp_error($post_id)) {
			continue;
		}

		$created++;
		update_post_meta($post_id, '_glintlab_demo_imported', 1);

		if (isset($item['meta']) && is_array($item['meta'])) {
			if (isset($item['meta']['_glintlab_team_role'])) {
				update_post_meta($post_id, '_glintlab_team_role', (string) $item['meta']['_glintlab_team_role']);
			}
			if (isset($item['meta']['_glintlab_team_link_url'])) {
				update_post_meta($post_id, '_glintlab_team_link_url', (string) $item['meta']['_glintlab_team_link_url']);
			}
		}

		if (isset($item['featuredImage']) && is_array($item['featuredImage']) && !empty($item['featuredImage']['file'])) {
			$src = GLINTLAB_COMPONENTS_DIR . 'assets/demo-media/' . basename((string) $item['featuredImage']['file']);
			if (is_file($src)) {
				$bits = wp_upload_bits(basename($src), null, (string) file_get_contents($src));
				if (empty($bits['error']) && !empty($bits['file'])) {
					$filetype = wp_check_filetype($bits['file'], null);
					$attachment_id = wp_insert_attachment(
						array(
							'post_mime_type' => $filetype['type'] ?? '',
							'post_title' => sanitize_file_name(basename($bits['file'])),
							'post_content' => '',
							'post_status' => 'inherit',
						),
						$bits['file'],
						$post_id
					);

					if (!is_wp_error($attachment_id) && $attachment_id) {
						$attach_data = wp_generate_attachment_metadata($attachment_id, $bits['file']);
						if (is_array($attach_data)) {
							wp_update_attachment_metadata($attachment_id, $attach_data);
						}
						set_post_thumbnail($post_id, $attachment_id);
						update_post_meta($attachment_id, '_glintlab_demo_imported', 1);

						$alt = isset($item['featuredImage']['alt']) ? (string) $item['featuredImage']['alt'] : '';
						if ('' !== $alt) {
							update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
						}

						$images++;
					}
				}
			}
		}
	}

	update_option('glintlab_components_demo_imported_at', current_time('mysql'));

	return array(
		'created' => $created,
		'skipped' => $skipped,
		'images' => $images,
	);
}
