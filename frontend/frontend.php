<?php
/**
 * Share2AI - Frontend Output Functions
 *
 * @package Share2AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue front-end assets.
 */
function share2ai_enqueue_assets() {
	if ( ! is_singular( share2ai_get_option( 'post_types' ) ) ) {
		return;
	}

	wp_enqueue_style(
		'share2ai',
		SHARE2AI_PLUGIN_URL . 'assets/styles.css',
		array(),
		SHARE2AI_VERSION
	);

	wp_enqueue_script(
		'share2ai',
		SHARE2AI_PLUGIN_URL . 'assets/scripts.js',
		array(),
		SHARE2AI_VERSION,
		true
	);

	wp_localize_script( 'share2ai', 'share2aiData', share2ai_get_js_data() );
}
add_action( 'wp_enqueue_scripts', 'share2ai_enqueue_assets' );

/**
 * Render floating panel HTML.
 */
function share2ai_render_floating_panel() {
	$options       = share2ai_get_option();
	$tools         = share2ai_get_tools();
	$enabled_tools = $options['enabled_tools'];
	$floating_pos  = $options['floating_position'];

	if ( 'disabled' === $floating_pos || empty( $enabled_tools ) ) {
		return '';
	}

	if ( ! is_singular( $options['post_types'] ) ) {
		return '';
	}

	$page_url = get_permalink();
	$post_type = get_post_type();
	$prompt   = share2ai_build_prompt( $page_url, $post_type );
	$encoded  = rawurlencode( $prompt );

	$html = '<div class="share2ai-floating-panel share2ai-floating-' . esc_attr( $floating_pos ) . '">';

	foreach ( $enabled_tools as $slug ) {
		if ( ! isset( $tools[ $slug ] ) ) {
			continue;
		}

		$tool = $tools[ $slug ];
		$url  = sprintf( $tool['url_pattern'], $encoded );
		
		// Use custom tooltip template
		$tooltip = sprintf(
			esc_attr( $options['tooltip_template'] ),
			esc_attr( $tool['label'] )
		);

		$html .= sprintf(
			'<a href="%1$s" class="share2ai-btn share2ai-btn-%2$s %3$s" target="_blank" rel="noopener noreferrer" data-tool="%2$s" data-tooltip="%4$s"></a>',
			esc_url( $url ),
			esc_attr( $slug ),
			esc_attr( $tool['icon_class'] ),
			esc_attr( $tooltip )
		);
	}

	$html .= '</div>';

	return $html;
}

/**
 * Render share buttons HTML.
 */
function share2ai_render_buttons() {
	$options       = share2ai_get_option();
	$tools         = share2ai_get_tools();
	$enabled_tools = $options['enabled_tools'];
	$page_url      = get_permalink();
	$post_type     = get_post_type();
	$prompt        = share2ai_build_prompt( $page_url, $post_type );
	$encoded       = rawurlencode( $prompt );

	if ( empty( $enabled_tools ) ) {
		return '';
	}

	$html  = '<div class="share2ai-buttons">';
	$html .= '<span class="share2ai-label">' . esc_html( $options['button_label'] ) . '</span>';

	foreach ( $enabled_tools as $slug ) {
		if ( ! isset( $tools[ $slug ] ) ) {
			continue;
		}

		$tool = $tools[ $slug ];
		$url  = sprintf( $tool['url_pattern'], $encoded );

		// Use custom tooltip template
		$tooltip = sprintf(
			esc_attr( $options['tooltip_template'] ),
			esc_attr( $tool['label'] )
		);

		$html .= sprintf(
			'<a href="%1$s" class="share2ai-btn share2ai-btn-%2$s %3$s" target="_blank" rel="noopener noreferrer" data-tool="%2$s" title="%4$s">%5$s</a>',
			esc_url( $url ),
			esc_attr( $slug ),
			esc_attr( $tool['icon_class'] ),
			$tooltip,
			esc_html( $tool['label'] )
		);
	}

	$html .= '</div>';

	return $html;
}

/**
 * Append/prepend buttons to post content.
 */
function share2ai_filter_content( $content ) {
	$options = share2ai_get_option();

	if ( ! is_singular( $options['post_types'] ) ) {
		return $content;
	}

	if ( ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$buttons  = share2ai_render_buttons();
	$position = $options['display_position'];

	if ( 'before_content' === $position ) {
		return $buttons . $content;
	}

	if ( 'both' === $position ) {
		return $buttons . $content . $buttons;
	}

	return $content . $buttons;
}
add_filter( 'the_content', 'share2ai_filter_content' );

/**
 * Output floating panel in footer.
 */
function share2ai_output_floating_panel() {
	echo share2ai_render_floating_panel(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_footer', 'share2ai_output_floating_panel' );

/**
 * Register shortcode [share2ai].
 */
function share2ai_shortcode() {
	return share2ai_render_buttons();
}
add_shortcode( 'share2ai', 'share2ai_shortcode' );
