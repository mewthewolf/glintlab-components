<?php
/**
 * Shortcodes.
 *
 * @package GlintLabComponents
 */

if (!defined('ABSPATH')) {
	exit;
}

function glintlab_components_shortcode_team_member($atts)
{
	$atts = shortcode_atts(
		array(
			'id' => '',
			'name' => '',
			'role' => '',
			'description' => '',
			'image_id' => '',
			'image_url' => '',
			'image_alt' => '',
			'link_url' => '',
			'max_width' => '520',
		),
		$atts,
		'glintlab_team_member'
	);

	$id = absint($atts['id']);
	$name = trim((string) $atts['name']);
	$role = trim((string) $atts['role']);
	$description = (string) $atts['description'];
	$image_url = '';
	$image_alt = (string) $atts['image_alt'];
	$link_url = trim((string) $atts['link_url']);

	// If ID is provided, fetch from CPT.
	if ($id) {
		$post = get_post($id);
		if ($post && 'glintlab_team' === $post->post_type) {
			$name = $post->post_title;
			$description = $post->post_content;
			$role = get_post_meta($id, '_glintlab_team_role', true);
			$link_url = get_post_meta($id, '_glintlab_team_link_url', true);

			if (has_post_thumbnail($id)) {
				$img = wp_get_attachment_image_src(get_post_thumbnail_id($id), 'full');
				if (is_array($img)) {
					$image_url = $img[0];
				}
				$image_alt = get_post_meta(get_post_thumbnail_id($id), '_wp_attachment_image_alt', true);
			}
		}
	}

	if ('' === $name) {
		return '';
	}

	glintlab_components_enqueue_team_member_modal_assets();

	if ('' === $image_url && '' !== (string) $atts['image_id']) {
		$img_id = absint($atts['image_id']);
		if ($img_id) {
			$img = wp_get_attachment_image_src($img_id, 'full');
			if (is_array($img) && !empty($img[0])) {
				$image_url = (string) $img[0];
			}
			if ('' === $image_alt) {
				$image_alt = (string) get_post_meta($img_id, '_wp_attachment_image_alt', true);
			}
		}
	}

	if ('' === $image_url && '' !== (string) $atts['image_url']) {
		$image_url = esc_url_raw((string) $atts['image_url']);
	}

	$max_width = absint($atts['max_width']);
	if (!$max_width) {
		$max_width = 520;
	}

	$description_html = wp_kses_post(wpautop($description));

	$name_inner = esc_html($name);

	ob_start();
	?>
	<a class="wp-block-tiptip-hyperlink-group-block glintlab-team-member-trigger" href="#" aria-expanded="false"
		data-link-url="<?php echo esc_url($link_url); ?>">
		<div class="c-team-member">
			<div class="c-team-member__summary">
				<?php if ('' !== $image_url): ?>
					<div class="c-team-member__media">
						<figure class="c-team-member__avatar">
							<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>"
								style="aspect-ratio: 1; object-fit: cover; width: 240px" />
						</figure>
					</div>
				<?php endif; ?>

				<h6 class="c-team-member__name">
					<span><?php echo $name_inner; ?></span>
				</h6>

				<?php if ('' !== $role): ?>
					<p class="c-team-member__bio"><?php echo esc_html($role); ?></p>
				<?php endif; ?>
			</div>

			<?php if ('' !== trim(wp_strip_all_tags($description_html))): ?>
				<div class="c-team-member__description" style="display: none;">
					<?php echo $description_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>
	</a>
	<?php
	return (string) ob_get_clean();
}
add_shortcode('glintlab_team_member', 'glintlab_components_shortcode_team_member');

/**
 * Shortcode to display a grid of all team members.
 */
function glintlab_components_shortcode_team_grid($atts)
{
	$atts = shortcode_atts(
		array(
			'columns' => '3',
			'ids' => '', // Can be array or comma-separated string
		),
		$atts,
		'glintlab_team_grid'
	);

	$query_args = array(
		'post_type' => 'glintlab_team',
		'posts_per_page' => -1,
	);

	if (!empty($atts['ids'])) {
		$ids = is_array($atts['ids']) ? $atts['ids'] : explode(',', $atts['ids']);
		$ids = array_map('absint', $ids);
		$query_args['post__in'] = $ids;
		$query_args['orderby'] = 'post__in';
	} else {
		$query_args['orderby'] = 'menu_order';
		$query_args['order'] = 'ASC';
	}

	$query = new WP_Query($query_args);

	if (!$query->have_posts()) {
		return '';
	}

	ob_start();
	?>
	<div class="glintlab-team-grid-surface">
		<div class="glintlab-team-grid glintlab-team-grid--cols-<?php echo esc_attr($atts['columns']); ?>"
			style="--glintlab-columns: <?php echo esc_attr($atts['columns']); ?>;">
			<?php while ($query->have_posts()):
				$query->the_post(); ?>
				<div class="glintlab-team-grid__item">
					<?php echo glintlab_components_shortcode_team_member(array('id' => get_the_ID())); ?>
				</div>
			<?php endwhile;
			wp_reset_postdata(); ?>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode('glintlab_team_grid', 'glintlab_components_shortcode_team_grid');

function glintlab_components_shortcode_abg_features($atts)
{
	static $instance = 0;
	$instance++;

	$atts = shortcode_atts(
		array(
			'heading' => 'Capabilities',
			'subheading' => 'Tap a card to see details.',
			'aria_label' => 'ABG capabilities',
		),
		$atts,
		'glintlab_abg_features'
	);

	glintlab_components_enqueue_abg_features_assets();

	$default_data = array(
		array(
			'icon' => 'briefcase',
			'title' => 'Investment Management',
			'description' => 'Managing investment vehicles for energy infrastructure',
			'keyBenefits' => array(
				'Comprehensive portfolio oversight',
				'Risk-adjusted return optimization',
				'Institutional-grade governance',
				'Transparent reporting standards',
			),
			'typicalOutcomes' => array(
				'Enhanced capital efficiency',
				'Diversified energy infrastructure exposure',
				'Long-term value creation',
				'Sustainable cash flow generation',
			),
		),
		array(
			'icon' => 'factory',
			'title' => 'LNG & Energy Logistics',
			'description' => 'Financing and structuring LNG export facilities, gas pipelines, and energy logistics networks',
			'keyBenefits' => array(
				'End-to-end project development',
				'Strategic location advantages',
				'Integrated supply chain management',
				'Advanced logistics coordination',
			),
			'typicalOutcomes' => array(
				'Reduced transportation costs',
				'Improved energy security',
				'Enhanced regional connectivity',
				'Accelerated market access',
			),
		),
		array(
			'icon' => 'energy',
			'title' => 'Downstream Projects',
			'description' => 'Supporting greenfield and brownfield downstream projects including petrochemicals, steel, and fertilisers',
			'keyBenefits' => array(
				'Proven industrial expertise',
				'Environmental compliance leadership',
				'Technology integration capabilities',
				'Operational excellence focus',
			),
			'typicalOutcomes' => array(
				'Increased processing efficiency',
				'Reduced environmental impact',
				'Enhanced product quality',
				'Strengthened market position',
			),
		),
		array(
			'icon' => 'handshake',
			'title' => 'Strategic Coordination',
			'description' => 'Coordinating with developers, governments, and multilateral institutions',
			'keyBenefits' => array(
				'Multi-stakeholder engagement',
				'Regulatory expertise',
				'Cultural and local knowledge',
				'Diplomatic relationship management',
			),
			'typicalOutcomes' => array(
				'Streamlined approval processes',
				'Enhanced stakeholder buy-in',
				'Reduced regulatory risk',
				'Sustainable community partnerships',
			),
		),
	);

	$data = apply_filters('glintlab_components_abg_features_data', $default_data, $atts);
	if (!is_array($data)) {
		$data = $default_data;
	}

	$root_id = 'glintlab-abg-features-' . $instance;

	$options = array(
		'heading' => (string) $atts['heading'],
		'subheading' => (string) $atts['subheading'],
		'ariaLabel' => (string) $atts['aria_label'],
	);

	$inline = sprintf(
		'window.GlintLabABGFeatures=window.GlintLabABGFeatures||{}; if (window.GlintLabABGFeatures.init) { window.GlintLabABGFeatures.init(%s,%s,%s); }',
		wp_json_encode($root_id),
		wp_json_encode($data),
		wp_json_encode($options)
	);
	wp_add_inline_script('glintlab-abg-features', $inline, 'after');

	return sprintf('<div class="glintlab-abg-features" id="%s"></div>', esc_attr($root_id));
}
add_shortcode('glintlab_abg_features', 'glintlab_components_shortcode_abg_features');
