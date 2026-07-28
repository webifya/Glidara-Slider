<?php
/**
 * Plugin Name: My Slider
 * Description: A fast, responsive slider builder with images, text, video, HTML and shortcodes.
 * Version: 0.2.0
 * Author: Webifya
 * License: GPL-2.0-or-later
 * Text Domain: my-slider
 */

defined( 'ABSPATH' ) || exit;

define( 'MY_SLIDER_VERSION', '0.2.0' );
define( 'MY_SLIDER_FILE', __FILE__ );
define( 'MY_SLIDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'MY_SLIDER_URL', plugin_dir_url( __FILE__ ) );

require_once MY_SLIDER_DIR . 'includes/class-my-slider-plugin.php';

My_Slider_Plugin::instance();
