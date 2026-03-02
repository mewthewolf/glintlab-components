<?php
/**
 * Gutenberg Block registration.
 *
 * @package GlintLabComponents
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Gutenberg blocks.
 */
function glintlab_components_register_blocks()
{
    if (!function_exists('register_block_type')) {
        return;
    }

    wp_register_script(
        'glintlab-team-blocks',
        plugins_url('assets/js/blocks.js', GLINTLAB_COMPONENTS_FILE),
        array('wp-blocks', 'wp-element', 'wp-server-side-render', 'wp-components', 'wp-block-editor', 'wp-data'),
        GLINTLAB_COMPONENTS_VERSION
    );

    // Parent Grid Block
    register_block_type('glintlab/team-grid', array(
        'editor_script' => 'glintlab-team-blocks',
        'editor_style' => 'glintlab-team-member-modal',
        'style' => 'glintlab-team-member-modal',
        'script' => 'glintlab-team-member-modal',
        'view_script' => 'glintlab-team-member-modal',
        'render_callback' => 'glintlab_components_render_team_grid_block',
        'supports' => array(
            'align' => array('wide', 'full'),
        ),
        'attributes' => array(
            'columns' => array(
                'type' => 'string',
                'default' => '3',
            ),
            'align' => array(
                'type' => 'string',
                'default' => 'wide',
            ),
        ),
    ));

    // Child Member Block
    register_block_type('glintlab/team-member', array(
        'parent' => array('glintlab/team-grid'),
        'editor_script' => 'glintlab-team-blocks',
        'script' => 'glintlab-team-member-modal',
        'view_script' => 'glintlab-team-member-modal',
        'render_callback' => 'glintlab_components_render_team_member_block',
        'attributes' => array(
            'memberId' => array(
                'type' => 'number',
                'default' => 0,
            ),
        ),
    ));

    // Meta-syncing Header Block for Team Member CPT
    register_block_type('glintlab/team-profile-header', array(
        'editor_script' => 'glintlab-team-blocks',
    ));
}
add_action('init', 'glintlab_components_register_blocks', 5);

/**
 * Render callback for the Team Grid block.
 */
function glintlab_components_render_team_grid_block($attributes, $content, $block = null)
{
    $columns = isset($attributes['columns']) ? $attributes['columns'] : '3';

    $classes = 'glintlab-team-grid glintlab-team-grid--cols-' . $columns;
    $grid_style = sprintf('--glintlab-columns:%s;', esc_attr($columns));

    if (function_exists('get_block_wrapper_attributes')) {
        $surface_attributes = get_block_wrapper_attributes(array(
            'class' => 'glintlab-team-grid-surface',
        ));

        $inner = sprintf(
            '<div class="%s" style="%s">%s</div>',
            esc_attr($classes),
            esc_attr($grid_style),
            $content
        );

        return sprintf('<div %s>%s</div>', $surface_attributes, $inner);
    }

    return sprintf(
        '<div class="%s"><div class="%s" style="%s">%s</div></div>',
        esc_attr('glintlab-team-grid-surface'),
        esc_attr($classes),
        esc_attr($grid_style),
        $content
    );
}

/**
 * Render callback for the Team Member block.
 */
function glintlab_components_render_team_member_block($attributes)
{
    if (empty($attributes['memberId'])) {
        return is_admin() ? '<div style="padding:20px; border:1px dashed #ccc; text-align:center;">Select a Team Member</div>' : '';
    }

    return sprintf(
        '<div class="glintlab-team-grid__item">%s</div>',
        glintlab_components_shortcode_team_member(array('id' => $attributes['memberId']))
    );
}
