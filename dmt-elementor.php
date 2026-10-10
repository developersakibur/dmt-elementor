<?php
/**
 * Plugin Name: Dark Mode Toggle for Elementor
 * Description: Reads your Elementor Global Colors, lets you set a dark value for each one, and adds a Light/Dark toggle shortcode. Follows the visitor's device theme and remembers their choice in localStorage.
 * Version: 1.0.1
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Author: Sakibur Rahman
 * License: GPL-2.0-or-later
 * Text Domain: dmt-elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DMT_VERSION', '1.0.1' );
define( 'DMT_FILE', __FILE__ );
define( 'DMT_DIR', plugin_dir_path( __FILE__ ) );
define( 'DMT_URL', plugin_dir_url( __FILE__ ) );

require_once DMT_DIR . 'includes/class-dmt-colors.php';
require_once DMT_DIR . 'includes/class-dmt-settings.php';
require_once DMT_DIR . 'includes/class-dmt-frontend.php';
require_once DMT_DIR . 'includes/class-dmt-admin.php';

add_action(
	'plugins_loaded',
	function () {
		DMT_Frontend::init();
		if ( is_admin() ) {
			DMT_Admin::init();
		}
	}
);
