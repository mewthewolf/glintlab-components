<?php
/**
 * GitHub-based plugin updates (private repo supported via token).
 *
 * @package GlintLabComponents
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Define these in wp-config.php on sites that should auto-update from GitHub:
 * - GLINTLAB_COMPONENTS_GITHUB_REPO  (default: mewthewolf/glintlab-components)
 * - GLINTLAB_COMPONENTS_GITHUB_TOKEN (required for private repo updates)
 */

function glintlab_components_updater_get_repo()
{
	$repo = defined('GLINTLAB_COMPONENTS_GITHUB_REPO') ? (string) GLINTLAB_COMPONENTS_GITHUB_REPO : '';
	if ('' === trim($repo)) {
		$repo = (string) get_option('glintlab_components_github_repo', 'mewthewolf/glintlab-components');
	}
	$repo = trim($repo);
	if ('' === $repo || false === strpos($repo, '/')) {
		$repo = 'mewthewolf/glintlab-components';
	}
	return $repo;
}

function glintlab_components_updater_get_token()
{
	if (defined('GLINTLAB_COMPONENTS_GITHUB_TOKEN')) {
		$token = trim((string) GLINTLAB_COMPONENTS_GITHUB_TOKEN);
		return '' !== $token ? $token : '';
	}

	$stored = (string) get_option('glintlab_components_github_token', '');
	if ('' === $stored) {
		return '';
	}

	if (function_exists('glintlab_components_settings_decrypt')) {
		return (string) glintlab_components_settings_decrypt($stored);
	}

	return $stored;
}

function glintlab_components_updater_http_request_args($args, $url)
{
	$host = wp_parse_url($url, PHP_URL_HOST);
	if ('api.github.com' !== $host) {
		return $args;
	}

	if (!isset($args['headers']) || !is_array($args['headers'])) {
		$args['headers'] = array();
	}

	$token = glintlab_components_updater_get_token();
	if ('' !== $token) {
		$args['headers']['Authorization'] = 'token ' . $token;
	}

	$args['headers']['User-Agent'] = 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/');

	// Release asset downloads require this accept header.
	if (false !== strpos($url, '/releases/assets/')) {
		$args['headers']['Accept'] = 'application/octet-stream';
	} else {
		$args['headers']['Accept'] = 'application/vnd.github+json';
	}

	return $args;
}
add_filter('http_request_args', 'glintlab_components_updater_http_request_args', 10, 2);

function glintlab_components_updater_get_latest_release()
{
	$cache_key = 'glintlab_components_github_latest_release';
	$cached = get_transient($cache_key);
	if (is_array($cached)) {
		return $cached;
	}

	$repo = glintlab_components_updater_get_repo();
	$url = 'https://api.github.com/repos/' . $repo . '/releases/latest';

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
		)
	);

	if (is_wp_error($response)) {
		return null;
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	if ($code < 200 || $code >= 300) {
		return null;
	}

	$body = (string) wp_remote_retrieve_body($response);
	$data = json_decode($body, true);
	if (!is_array($data)) {
		return null;
	}

	set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);
	return $data;
}

function glintlab_components_updater_find_zip_asset($release)
{
	if (!is_array($release) || empty($release['assets']) || !is_array($release['assets'])) {
		return null;
	}

	foreach ($release['assets'] as $asset) {
		if (!is_array($asset) || empty($asset['name']) || empty($asset['id'])) {
			continue;
		}
		$name = (string) $asset['name'];
		if ('glintlab-components.zip' === $name) {
			return $asset;
		}
	}

	return null;
}

function glintlab_components_updater_normalize_version($tag)
{
	$tag = trim((string) $tag);
	$tag = ltrim($tag, " \t\n\r\0\x0BvV");
	return $tag;
}

function glintlab_components_updater_inject_update($transient)
{
	if (!is_object($transient)) {
		return $transient;
	}

	$release = glintlab_components_updater_get_latest_release();
	if (!$release) {
		return $transient;
	}

	$tag = isset($release['tag_name']) ? (string) $release['tag_name'] : '';
	$new_version = glintlab_components_updater_normalize_version($tag);
	if ('' === $new_version) {
		return $transient;
	}

	$current = defined('GLINTLAB_COMPONENTS_VERSION') ? (string) GLINTLAB_COMPONENTS_VERSION : '0.0.0';
	if (version_compare($current, $new_version, '>=')) {
		return $transient;
	}

	$asset = glintlab_components_updater_find_zip_asset($release);
	if (!$asset) {
		return $transient;
	}

	$asset_id = (int) $asset['id'];
	if (!$asset_id) {
		return $transient;
	}

	$repo = glintlab_components_updater_get_repo();
	$package = 'https://api.github.com/repos/' . $repo . '/releases/assets/' . $asset_id;

	$plugin_file = plugin_basename(GLINTLAB_COMPONENTS_FILE);

	$update = new stdClass();
	$update->slug = defined('GLINTLAB_COMPONENTS_SLUG') ? (string) GLINTLAB_COMPONENTS_SLUG : 'glintlab-components';
	$update->plugin = $plugin_file;
	$update->new_version = $new_version;
	$update->url = isset($release['html_url']) ? (string) $release['html_url'] : '';
	$update->package = $package;

	if (!isset($transient->response) || !is_array($transient->response)) {
		$transient->response = array();
	}
	$transient->response[$plugin_file] = $update;

	return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'glintlab_components_updater_inject_update');

function glintlab_components_updater_plugins_api($result, $action, $args)
{
	if ('plugin_information' !== $action || !is_object($args) || empty($args->slug)) {
		return $result;
	}

	$slug = defined('GLINTLAB_COMPONENTS_SLUG') ? (string) GLINTLAB_COMPONENTS_SLUG : 'glintlab-components';
	if ($args->slug !== $slug) {
		return $result;
	}

	$release = glintlab_components_updater_get_latest_release();

	$info = new stdClass();
	$info->name = 'GlintLab Components';
	$info->slug = $slug;
	$info->version = defined('GLINTLAB_COMPONENTS_VERSION') ? (string) GLINTLAB_COMPONENTS_VERSION : '';
	$owner = explode('/', glintlab_components_updater_get_repo())[0];
	$info->author = '<a href="https://github.com/' . esc_attr($owner) . '">GlintLab</a>';
	$info->homepage = is_array($release) && !empty($release['html_url']) ? (string) $release['html_url'] : '';
	$info->short_description = 'Reusable UI components for GlintLab (team member modal, ABG feature cards).';

	$sections = array(
		'description' => 'Installs reusable blocks/shortcodes. Supports GitHub-based updates when configured with a token.',
	);

	if (is_array($release) && !empty($release['body'])) {
		$sections['changelog'] = wp_kses_post((string) $release['body']);
	}

	$info->sections = $sections;
	return $info;
}
add_filter('plugins_api', 'glintlab_components_updater_plugins_api', 10, 3);
