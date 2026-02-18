<?php
/**
 * Plugin Name: Share2AI
 * Description: Add AI share buttons to any page or post with auto-prompts.
 * Plugin URI:  https://modeltheme.com/
 * Version:     1.0.0
 * Author:      ModelTheme
 * Author URI:  https://ai-tools.modeltheme.com/share2ai/
 * Text Domain: share2ai
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SHARE2AI_VERSION', '1.0.0' );
define( 'SHARE2AI_PLUGIN_FILE', __FILE__ );
define( 'SHARE2AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SHARE2AI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin textdomain.
 */
function share2ai_load_textdomain() {
	load_plugin_textdomain( 'share2ai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'share2ai_load_textdomain' );

/**
 * Load admin settings.
 */
if ( is_admin() ) {
	require_once SHARE2AI_PLUGIN_DIR . 'admin/settings.php';
}

/**
 * Load frontend functions.
 */
require_once SHARE2AI_PLUGIN_DIR . 'frontend/helpers.php';
require_once SHARE2AI_PLUGIN_DIR . 'frontend/frontend.php';
