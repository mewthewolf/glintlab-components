<?php
/**
 * Plugin Name: GlintLab Components
 * Description: Reusable UI components for GlintLab (team member modal, ABG feature cards).
 * Version: 0.2.0
 * Author: GlintLab
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package GlintLabComponents
 */

if (!defined('ABSPATH')) {
	exit;
}

define('GLINTLAB_COMPONENTS_VERSION', '0.2.0');
define('GLINTLAB_COMPONENTS_FILE', __FILE__);
define('GLINTLAB_COMPONENTS_DIR', plugin_dir_path(__FILE__));
define('GLINTLAB_COMPONENTS_SLUG', 'glintlab-components');

require_once GLINTLAB_COMPONENTS_DIR . 'includes/assets.php';
require_once GLINTLAB_COMPONENTS_DIR . 'includes/post-types.php';
require_once GLINTLAB_COMPONENTS_DIR . 'includes/blocks.php';
require_once GLINTLAB_COMPONENTS_DIR . 'includes/shortcodes.php';
require_once GLINTLAB_COMPONENTS_DIR . 'includes/demo-importer.php';
require_once GLINTLAB_COMPONENTS_DIR . 'includes/updater.php';

function glintlab_components_on_activate()
{
	if (function_exists('glintlab_components_demo_importer_maybe_auto_import')) {
		glintlab_components_demo_importer_maybe_auto_import();
	}
}
register_activation_hook(__FILE__, 'glintlab_components_on_activate');
