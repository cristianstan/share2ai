<?php
/**
 * Share2AI - Admin Settings Page
 *
 * @package Share2AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue admin assets on the plugin settings page.
 */
function share2ai_admin_enqueue_assets( $hook ) {
	if ( 'settings_page_share2ai' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'share2ai-admin',
		SHARE2AI_PLUGIN_URL . 'assets/admin-styles.css',
		array(),
		SHARE2AI_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'share2ai_admin_enqueue_assets' );

/**
 * Register settings page.
 */
function share2ai_admin_menu() {
	add_options_page(
		esc_html__( 'Share2AI Settings', 'share2ai' ),
		esc_html__( 'Share2AI', 'share2ai' ),
		'manage_options',
		'share2ai',
		'share2ai_settings_page'
	);
}
add_action( 'admin_menu', 'share2ai_admin_menu' );

/**
 * Register settings.
 */
function share2ai_register_settings() {
	register_setting(
		'share2ai_settings',
		'share2ai_options',
		array(
			'type'              => 'object',
			'sanitize_callback' => 'share2ai_sanitize_options',
		)
	);
}
add_action( 'admin_init', 'share2ai_register_settings' );

/**
 * Sanitize options on save.
 */
function share2ai_sanitize_options( $input ) {
	$valid_tools      = array_keys( share2ai_get_tools() );
	$valid_positions  = array( 'after_content', 'before_content', 'both' );
	$valid_floating   = array( 'disabled', 'left', 'right' );

	$sanitized = array();

	$sanitized['enabled_tools'] = array();
	if ( isset( $input['enabled_tools'] ) && is_array( $input['enabled_tools'] ) ) {
		foreach ( $input['enabled_tools'] as $tool ) {
			if ( in_array( $tool, $valid_tools, true ) ) {
				$sanitized['enabled_tools'][] = $tool;
			}
		}
	}

	$sanitized['display_position'] = isset( $input['display_position'] ) && in_array( $input['display_position'], $valid_positions, true )
		? $input['display_position']
		: 'after_content';

	$sanitized['floating_position'] = isset( $input['floating_position'] ) && in_array( $input['floating_position'], $valid_floating, true )
		? $input['floating_position']
		: 'disabled';

	$sanitized['post_types'] = array();
	if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
		foreach ( $input['post_types'] as $pt ) {
			$sanitized['post_types'][] = sanitize_key( $pt );
		}
	}

	$sanitized['button_label'] = isset( $input['button_label'] )
		? sanitize_text_field( $input['button_label'] )
		: __( 'Share with', 'share2ai' );

	$sanitized['custom_prompt'] = isset( $input['custom_prompt'] )
		? sanitize_textarea_field( $input['custom_prompt'] )
		: share2ai_get_defaults()['custom_prompt'];

	$sanitized['tooltip_template'] = isset( $input['tooltip_template'] )
		? sanitize_text_field( $input['tooltip_template'] )
		: share2ai_get_defaults()['tooltip_template'];

	// Get existing post_type_prompts to preserve them
	$existing_options          = get_option( 'share2ai_options', array() );
	$existing_post_prompts     = isset( $existing_options['post_type_prompts'] ) ? $existing_options['post_type_prompts'] : array();
	$sanitized['post_type_prompts'] = $existing_post_prompts; // Start with existing

	// Update with newly submitted values
	if ( isset( $input['post_type_prompts'] ) && is_array( $input['post_type_prompts'] ) ) {
		foreach ( $input['post_type_prompts'] as $pt => $prompt ) {
			$pt_key = sanitize_key( $pt );
			if ( ! empty( $prompt ) ) {
				// Save non-empty prompt
				$sanitized['post_type_prompts'][ $pt_key ] = sanitize_textarea_field( $prompt );
			} else {
				// Remove the key if the prompt was cleared
				unset( $sanitized['post_type_prompts'][ $pt_key ] );
			}
		}
	}

	return $sanitized;
}

/**
 * Render settings page.
 */
function share2ai_settings_page() {
	$options    = share2ai_get_option();
	$tools      = share2ai_get_tools();
	$post_types = get_post_types( array( 'public' => true ), 'objects' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'share2ai_settings' ); ?>
			<table class="form-table" role="presentation">

				<tr>
					<th scope="row"><?php esc_html_e( 'Enabled AI Tools', 'share2ai' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $tools as $slug => $tool ) : ?>
								<label>
									<input
										type="checkbox"
										name="share2ai_options[enabled_tools][]"
										value="<?php echo esc_attr( $slug ); ?>"
										<?php checked( in_array( $slug, $options['enabled_tools'], true ) ); ?>
									/>
									<?php echo esc_html( $tool['label'] ); ?>
								</label><br/>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="share2ai_button_label"><?php esc_html_e( 'Button Label Prefix', 'share2ai' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="share2ai_button_label"
							name="share2ai_options[button_label]"
							value="<?php echo esc_attr( $options['button_label'] ); ?>"
							class="regular-text"
						/>
						<p class="description">
							<?php esc_html_e( 'Text shown before the tool name, e.g. "Share with ChatGPT".', 'share2ai' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Display Position', 'share2ai' ); ?></th>
					<td>
						<select name="share2ai_options[display_position]">
							<option value="after_content" <?php selected( $options['display_position'], 'after_content' ); ?>>
								<?php esc_html_e( 'After content', 'share2ai' ); ?>
							</option>
							<option value="before_content" <?php selected( $options['display_position'], 'before_content' ); ?>>
								<?php esc_html_e( 'Before content', 'share2ai' ); ?>
							</option>
							<option value="both" <?php selected( $options['display_position'], 'both' ); ?>>
								<?php esc_html_e( 'Before & after content', 'share2ai' ); ?>
							</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Floating Panel', 'share2ai' ); ?></th>
					<td>
						<select name="share2ai_options[floating_position]">
							<option value="disabled" <?php selected( $options['floating_position'], 'disabled' ); ?>>
								<?php esc_html_e( 'Disabled', 'share2ai' ); ?>
							</option>
							<option value="left" <?php selected( $options['floating_position'], 'left' ); ?>>
								<?php esc_html_e( 'Float Left', 'share2ai' ); ?>
							</option>
							<option value="right" <?php selected( $options['floating_position'], 'right' ); ?>>
								<?php esc_html_e( 'Float Right', 'share2ai' ); ?>
							</option>
						</select>
						<p class="description">
							<?php esc_html_e( 'Display buttons as a fixed floating panel on the page.', 'share2ai' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="share2ai_tooltip_template"><?php esc_html_e( 'Tooltip Text Template', 'share2ai' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="share2ai_tooltip_template"
							name="share2ai_options[tooltip_template]"
							value="<?php echo esc_attr( $options['tooltip_template'] ); ?>"
							class="regular-text"
						/>
						<p class="description">
							<?php esc_html_e( 'Use %s for the tool name placeholder, e.g. "Ask questions in %s".', 'share2ai' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="share2ai_custom_prompt"><?php esc_html_e( 'Custom Prompt Template', 'share2ai' ); ?></label>
					</th>
					<td>
						<textarea
							id="share2ai_custom_prompt"
							name="share2ai_options[custom_prompt]"
							rows="6"
							class="large-text code"
						><?php echo esc_textarea( $options['custom_prompt'] ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Available variables:', 'share2ai' ); ?>
							<code>%page_url%</code>,
							<code>%site_url%</code>,
							<code>%page_title%</code>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Post Types', 'share2ai' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( $post_types as $pt ) : ?>
								<label>
									<input
										type="checkbox"
										name="share2ai_options[post_types][]"
										value="<?php echo esc_attr( $pt->name ); ?>"
										<?php checked( in_array( $pt->name, $options['post_types'], true ) ); ?>
									/>
									<?php echo esc_html( $pt->labels->singular_name ); ?>
								</label><br/>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row" colspan="2">
						<h3 class="share2ai-admin-section-title"><?php esc_html_e( 'Post Type Specific Prompts', 'share2ai' ); ?></h3>
						<p class="share2ai-admin-section-desc"><?php esc_html_e( 'Optional: Override the global prompt for specific post types. Leave empty to use the global prompt.', 'share2ai' ); ?></p>
					</th>
				</tr>

				<?php foreach ( $post_types as $pt ) : ?>
					<?php 
						$pt_prompts = isset( $options['post_type_prompts'] ) ? $options['post_type_prompts'] : array();
						$pt_prompt = isset( $pt_prompts[ $pt->name ] ) ? $pt_prompts[ $pt->name ] : '';
					?>
					<tr>
						<th scope="row">
							<label for="share2ai_pt_prompt_<?php echo esc_attr( $pt->name ); ?>">
								<?php echo esc_html( $pt->labels->singular_name ); ?> (<?php echo esc_html( $pt->name ); ?>)
							</label>
						</th>
						<td>
							<textarea
								id="share2ai_pt_prompt_<?php echo esc_attr( $pt->name ); ?>"
								name="share2ai_options[post_type_prompts][<?php echo esc_attr( $pt->name ); ?>]"
								rows="4"
								class="large-text code"
							><?php echo esc_textarea( $pt_prompt ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Available variables:', 'share2ai' ); ?>
								<code>%page_url%</code>,
								<code>%site_url%</code>,
								<code>%page_title%</code>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>

			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Add settings link on plugins list page.
 */
function share2ai_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=share2ai' ) ),
		esc_html__( 'Settings', 'share2ai' )
	);
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( SHARE2AI_PLUGIN_FILE ), 'share2ai_plugin_action_links' );
