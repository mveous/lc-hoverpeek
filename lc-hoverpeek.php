<?php
/**
 * Plugin Name: LC HoverPeek
 * Plugin URI: https://mveous.com
 * Description: Show instant link previews in a popup when hovering over links.
 * Version: 1.0.5
 * Author:  Mveous
 * Text Domain: lc-hoverpeek
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Tested up to: 7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'LCHO_VERSION', '1.0.5' );
define( 'LCHO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LCHO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader
if ( file_exists( LCHO_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once LCHO_PLUGIN_DIR . 'vendor/autoload.php';
}

class LCHO_HoverPeek {

	private static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( __FILE__, [ $this, 'lcho_activate_plugin' ] );
		add_action( 'admin_init', [ $this, 'lcho_activation_redirect' ] );

		if ( class_exists( '\lchoverpeek\LCHO_Core' ) ) {
			\lchoverpeek\LCHO_Core::get_instance();
		}

		if ( is_admin() && class_exists( '\lchoverpeek\admin\LCHO_Admin' ) ) {
			\lchoverpeek\admin\LCHO_Admin::get_instance();
		}
	}

	public function lcho_activate_plugin() {
		if ( ! get_option( 'lcho_dashboard_redirection' ) ) {
			add_option( 'lcho_do_activation_redirect', true );
			update_option( 'lcho_dashboard_redirection', true );
		}
	}

	public function lcho_activation_redirect() {
		if ( ! get_option( 'lcho_do_activation_redirect', false ) ) {
			return;
		}

		delete_option( 'lcho_do_activation_redirect' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=lc-hoverpeek-settings' ) );
		exit;
	}
}

LCHO_HoverPeek::get_instance();