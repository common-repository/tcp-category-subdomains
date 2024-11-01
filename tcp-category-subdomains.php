<?php
/**
 * Plugin Name: Category Subdomains for Woocommerce by TheCartPress
 * Plugin URI:
 * Description: This plugin can converts post and woocommerce product categories to subdomains.
 * Version: 1.4.0
 * Stable tag: 1.4.0
 * Requires PHP: 5.6
 * Requires at least: 5.5
 * Tested up to: 6.0.1
 * Author: TCP Team
 * Author URI: https://www.thecartpress.com
 * WC tested up to: 6.7.0
 */
defined('ABSPATH') or exit;

class TCP_category_subdomains {

	public function __construct() {
		$tcp_f = __DIR__ . '/tcp.php';
		if (file_exists($tcp_f)) {
			require_once $tcp_f;
		}
		$this->basename = plugin_basename(__FILE__);
		$this->is_wc_active = is_plugin_active('woocommerce/woocommerce.php');
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
		add_action('plugins_loaded', [$this, 'tcp_subdomain_free'], 100);
		add_action('admin_enqueue_scripts', [$this, 'tcp_subdomain_admin_enqueue']);
		add_action('admin_notices', [$this, 'tcp_subdomain_enqueue_widlcard']);
	}

	public function tcp_subdomain_free() {
		require_once __DIR__ . '/options-base.php';
		require_once __DIR__ . '/options.php';
	}

	function tcp_subdomain_admin_enqueue() {
		$screen = get_current_screen();
		if (!is_plugin_active('woocommerce/woocommerce.php')) {
			if ($screen->id == "plugins" || $screen->id == "settings_page_tcp-subdomain-admin") {
				add_action('admin_notices', [$this, 'admin_notices']);
			}
		}
	}

	function admin_notices() {
		$plugin_data = get_plugin_data(__FILE__);
		$plugin_name = $plugin_data['Name'];
		echo '<div class="notice notice-error is-dismissible"><p>' . "<b>" . $plugin_name . "</b> requires WooCommerce to be activated to modify product category subdomain" . '</p></div>';
	}

	function tcp_subdomain_enqueue_widlcard() {
		$screen = get_current_screen();
		if ($screen->id == "settings_page_tcp-subdomain-admin") {
			echo '<div class="notice notice-warning is-dismissible"><p>' . "Don't Forget to  Set Up Wildcard (*) Subdomains in your Web Server to prevent multisite issues." . '</p></div>';
		}
	}

}

$GLOBALS['tcp_category_subdomains'] = new TCP_category_subdomains();

//require_once ABSPATH . 'wp-admin/includes/plugin.php';
//require_once(ABSPATH . 'wp-admin/includes/screen.php');
//$premium_file = WP_PLUGIN_DIR . '/tcp-category-subdomains-premium/tcp-category-subdomains-premium.php';



