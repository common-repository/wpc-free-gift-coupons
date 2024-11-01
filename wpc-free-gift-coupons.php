<?php
/*
Plugin Name: WPC Free Gift Coupons for WooCommerce
Plugin URI: https://wpclever.net/
Description: WPC Free Gift Coupons for WooCommerce offers a new way to give away free gifts to customers during special sale occasions.
Version: 1.1.0
Author: WPClever
Author URI: https://wpclever.net
Text Domain: wpc-free-gift-coupons
Domain Path: /languages/
Requires Plugins: woocommerce
Requires at least: 4.0
Tested up to: 6.6
WC requires at least: 3.0
WC tested up to: 9.1
*/

! defined( 'WPCFG_VERSION' ) && define( 'WPCFG_VERSION', '1.1.0' );
! defined( 'WPCFG_LITE' ) && define( 'WPCFG_LITE', __FILE__ );
! defined( 'WPCFG_FILE' ) && define( 'WPCFG_FILE', __FILE__ );
! defined( 'WPCFG_PATH' ) && define( 'WPCFG_PATH', plugin_dir_path( __FILE__ ) );
! defined( 'WPCFG_URI' ) && define( 'WPCFG_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WPCFG_REVIEWS' ) && define( 'WPCFG_REVIEWS', 'https://wordpress.org/support/plugin/wpc-free-gift-coupons/reviews/?filter=5' );
! defined( 'WPCFG_CHANGELOG' ) && define( 'WPCFG_CHANGELOG', 'https://wordpress.org/plugins/wpc-free-gift-coupons/#developers' );
! defined( 'WPCFG_DISCUSSION' ) && define( 'WPCFG_DISCUSSION', 'https://wordpress.org/support/plugin/wpc-free-gift-coupons' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WPCFG_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! function_exists( 'wpcfg_init' ) ) {
	add_action( 'plugins_loaded', 'wpcfg_init', 11 );

	function wpcfg_init() {
		load_plugin_textdomain( 'wpc-free-gift-coupons', false, basename( __DIR__ ) . '/languages/' );

		if ( ! function_exists( 'WC' ) || ! version_compare( WC()->version, '3.0', '>=' ) ) {
			add_action( 'admin_notices', 'wpcfg_notice_wc' );

			return null;
		}

		if ( ! class_exists( 'WPCleverWpcfg' ) && class_exists( 'WC_Product' ) ) {
			class WPCleverWpcfg {
				public function __construct() {
					require_once 'includes/class-helper.php';
					require_once 'includes/class-backend.php';
					require_once 'includes/class-frontend.php';
				}
			}

			new WPCleverWpcfg();
		}
	}
}

if ( ! function_exists( 'wpcfg_notice_wc' ) ) {
	function wpcfg_notice_wc() {
		?>
        <div class="error">
            <p><strong>WPC Free Gift Coupons</strong> requires WooCommerce version 3.0 or greater.</p>
        </div>
		<?php
	}
}
