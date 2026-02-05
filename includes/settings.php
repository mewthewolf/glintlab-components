<?php
/**
 * Admin settings (GitHub updater configuration).
 *
 * @package GlintLabComponents
 */

if (!defined('ABSPATH')) {
	exit;
}

function glintlab_components_settings_on_activate()
{
	// Store updater settings as non-autoloaded options.
	if (false === get_option('glintlab_components_github_repo', false)) {
		add_option('glintlab_components_github_repo', 'mewthewolf/glintlab-components', '', false);
	}
	if (false === get_option('glintlab_components_github_token', false)) {
		add_option('glintlab_components_github_token', '', '', false);
	}
}

function glintlab_components_settings_register()
{
	register_setting(
		'glintlab_components_settings',
		'glintlab_components_github_repo',
		array(
			'type' => 'string',
			'sanitize_callback' => 'glintlab_components_settings_sanitize_repo',
			'default' => 'mewthewolf/glintlab-components',
		)
	);
}
add_action('admin_init', 'glintlab_components_settings_register');

function glintlab_components_settings_sanitize_repo($value)
{
	$value = trim((string) $value);
	$value = preg_replace('/\\s+/', '', $value);
	if ('' === $value || false === strpos($value, '/')) {
		return 'mewthewolf/glintlab-components';
	}
	return $value;
}

function glintlab_components_settings_encrypt($plaintext)
{
	$plaintext = (string) $plaintext;
	if ('' === $plaintext) {
		return '';
	}

	$key = hash('sha256', (string) wp_salt('auth'), true);

	if (function_exists('sodium_crypto_secretbox')) {
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$cipher = sodium_crypto_secretbox($plaintext, $nonce, $key);
		return 'sod:' . base64_encode($nonce . $cipher);
	}

	if (function_exists('openssl_encrypt')) {
		$iv = random_bytes(12);
		$tag = '';
		$cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
		if (false === $cipher) {
			return '';
		}
		return 'ossl:' . base64_encode($iv . $tag . $cipher);
	}

	// Fallback (not encrypted).
	return 'raw:' . base64_encode($plaintext);
}

function glintlab_components_settings_decrypt($stored)
{
	$stored = (string) $stored;
	if ('' === $stored) {
		return '';
	}

	$key = hash('sha256', (string) wp_salt('auth'), true);

	if (0 === strpos($stored, 'sod:')) {
		if (!function_exists('sodium_crypto_secretbox_open')) {
			return '';
		}
		$bin = base64_decode(substr($stored, 4), true);
		if (!is_string($bin) || strlen($bin) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
			return '';
		}
		$nonce = substr($bin, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$cipher = substr($bin, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
		return is_string($plain) ? $plain : '';
	}

	if (0 === strpos($stored, 'ossl:')) {
		if (!function_exists('openssl_decrypt')) {
			return '';
		}
		$bin = base64_decode(substr($stored, 5), true);
		if (!is_string($bin) || strlen($bin) < (12 + 16 + 1)) {
			return '';
		}
		$iv = substr($bin, 0, 12);
		$tag = substr($bin, 12, 16);
		$cipher = substr($bin, 28);
		$plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
		return is_string($plain) ? $plain : '';
	}

	if (0 === strpos($stored, 'raw:')) {
		$plain = base64_decode(substr($stored, 4), true);
		return is_string($plain) ? $plain : '';
	}

	// Back-compat: treat as plaintext.
	return $stored;
}

function glintlab_components_settings_add_menu()
{
	add_options_page(
		'GlintLab Components',
		'GlintLab Components',
		'manage_options',
		'glintlab-components',
		'glintlab_components_settings_render_page'
	);
}
add_action('admin_menu', 'glintlab_components_settings_add_menu');

function glintlab_components_settings_render_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$notice = '';
	$notice_class = 'notice-success';

	if (isset($_POST['glintlab_components_settings_action'])) {
		check_admin_referer('glintlab_components_settings_save', 'glintlab_components_settings_nonce');

		$repo = isset($_POST['glintlab_components_github_repo']) ? sanitize_text_field(wp_unslash($_POST['glintlab_components_github_repo'])) : '';
		update_option('glintlab_components_github_repo', glintlab_components_settings_sanitize_repo($repo), false);

		$token = isset($_POST['glintlab_components_github_token']) ? (string) wp_unslash($_POST['glintlab_components_github_token']) : '';
		$token = trim($token);
		if ('' !== $token) {
			update_option('glintlab_components_github_token', glintlab_components_settings_encrypt($token), false);
		}

		$notice = 'Settings saved.';
	}

	$repo_val = (string) get_option('glintlab_components_github_repo', 'mewthewolf/glintlab-components');
	$has_token = '' !== (string) get_option('glintlab_components_github_token', '');
	?>
	<div class="wrap">
		<h1>GlintLab Components</h1>

		<?php if ('' !== $notice): ?>
			<div class="notice <?php echo esc_attr($notice_class); ?> is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field('glintlab_components_settings_save', 'glintlab_components_settings_nonce'); ?>
			<input type="hidden" name="glintlab_components_settings_action" value="save" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="glintlab_components_github_repo">GitHub Repo</label></th>
					<td>
						<input name="glintlab_components_github_repo" id="glintlab_components_github_repo" type="text"
							class="regular-text" value="<?php echo esc_attr($repo_val); ?>" />
						<p class="description">Format: <code>owner/repo</code>. Must contain a release asset named <code>glintlab-components.zip</code>.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="glintlab_components_github_token">GitHub Token</label></th>
					<td>
						<input name="glintlab_components_github_token" id="glintlab_components_github_token" type="password"
							class="regular-text" value="" autocomplete="new-password" />
						<p class="description">
							<?php if ($has_token): ?>
								A token is already saved. Leave blank to keep it unchanged.
							<?php else: ?>
								Required for private repos. Use a fine-grained token with Contents: Read-only on this repo.
							<?php endif; ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button('Save Settings'); ?>
		</form>

		<hr />
		<p><strong>Optional:</strong> you can still configure via <code>wp-config.php</code> constants to override these settings.</p>
	</div>
	<?php
}

