<?php
/**
 * Plugin Name: Glidara Slider
 * Description: A fast, responsive slider builder with images, text, video, HTML and shortcodes.
 * Version: 1.0.0
 * Author: Webifya
 * License: GPL-2.0-or-later
 * Text Domain: glidara-slider
 */

defined( 'ABSPATH' ) || exit;

define( 'GLIDARA_SLIDER_VERSION', '1.0.0' );
define( 'GLIDARA_SLIDER_FILE', __FILE__ );
define( 'GLIDARA_SLIDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'GLIDARA_SLIDER_URL', plugin_dir_url( __FILE__ ) );

require_once GLIDARA_SLIDER_DIR . 'includes/class-glidara-slider-plugin.php';

Glidara_Slider_Plugin::instance();
