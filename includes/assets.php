<?php
/**
 * Asset registration/enqueue helpers.
 *
 * @package GlintLabComponents
 */

if (!defined('ABSPATH')) {
	exit;
}

function glintlab_components_register_assets()
{
	$ver = defined('GLINTLAB_COMPONENTS_VERSION') ? GLINTLAB_COMPONENTS_VERSION : null;

	wp_register_style(
		'glintlab-team-member-modal',
		plugins_url('assets/css/team-member-modal.css', GLINTLAB_COMPONENTS_FILE),
		array(),
		$ver
	);

	wp_register_script(
		'glintlab-team-member-modal',
		plugins_url('assets/js/team-member-modal.js', GLINTLAB_COMPONENTS_FILE),
		array(),
		$ver,
		true
	);

	wp_register_style(
		'glintlab-abg-features',
		plugins_url('assets/css/abg-features.css', GLINTLAB_COMPONENTS_FILE),
		array(),
		$ver
	);

	wp_register_script(
		'glintlab-abg-features',
		plugins_url('assets/js/abg-features.js', GLINTLAB_COMPONENTS_FILE),
		array(),
		$ver,
		true
	);
}
add_action('init', 'glintlab_components_register_assets', 5);

function glintlab_components_enqueue_team_member_modal_assets()
{
	wp_enqueue_style('glintlab-team-member-modal');
	wp_enqueue_script('glintlab-team-member-modal');
}

function glintlab_components_enqueue_abg_features_assets()
{
	wp_enqueue_style('glintlab-abg-features');
	wp_enqueue_script('glintlab-abg-features');
}
function glintlab_components_enqueue_editor_assets()
{
	wp_enqueue_style('glintlab-team-member-modal');
	// Needed so trigger clicks don't navigate (e.g. href="#") and to allow previewing the modal in-editor.
	wp_enqueue_script('glintlab-team-member-modal');
}
add_action('enqueue_block_editor_assets', 'glintlab_components_enqueue_editor_assets');
