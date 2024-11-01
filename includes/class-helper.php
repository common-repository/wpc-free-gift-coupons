<?php

class Wpcfg_Helper {
	protected static $instance = null;
	protected static $settings = [];

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		self::$settings = (array) Wpcfg_Helper::get_setting( 'settings', [] );
	}

	public static function get_settings() {
		return apply_filters( 'wpcfg_get_settings', self::$settings );
	}

	public static function get_setting( $name, $default = false ) {
		if ( ! empty( self::$settings ) && isset( self::$settings[ $name ] ) ) {
			$setting = self::$settings[ $name ];
		} else {
			$setting = get_option( 'wpcfg_' . $name, $default );
		}

		return apply_filters( 'wpcfg_get_setting', $setting, $name, $default );
	}

	public static function sanitize_array( $arr ) {
		foreach ( (array) $arr as $k => $v ) {
			if ( is_array( $v ) ) {
				$arr[ $k ] = self::sanitize_array( $v );
			} else {
				$arr[ $k ] = sanitize_text_field( $v );
			}
		}

		return $arr;
	}
}

Wpcfg_Helper::instance();
