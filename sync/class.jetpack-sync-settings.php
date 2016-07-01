<?php

require_once dirname( __FILE__ ) . '/class.jetpack-sync-defaults.php';

class Jetpack_Sync_Settings {
	const SETTINGS_OPTION_PREFIX = 'jetpack_sync_settings_';

	static $valid_settings = array( 
		'dequeue_max_bytes' => true, 
		'upload_max_bytes' => true, 
		'upload_max_rows' => true, 
		'sync_wait_time' => true,
		'max_queue_size' => true
	);

	static function get_settings() {
		$settings = array();
		foreach( array_keys( self::$valid_settings ) as $setting ) {
			$settings[ $setting ] = self::get_setting( $setting );
		}
		return $settings;
	}

	static function get_setting( $setting ) {
		if ( ! isset( self::$valid_settings[ $setting ] ) ) {
			return false;
		}

		$default_name = "default_$setting"; // e.g. default_dequeue_max_bytes
		return (int) get_option( self::SETTINGS_OPTION_PREFIX.$setting, Jetpack_Sync_Defaults::$$default_name );
	}

	static function update_settings( $new_settings ) {
		$validated_settings = array_intersect_key( $new_settings, self::$valid_settings );
		foreach( $validated_settings as $setting => $value ) {
			update_option( self::SETTINGS_OPTION_PREFIX.$setting, $value, true );
		}
	}

	static function reset_data() {
		$valid_settings  = self::$valid_settings;
		$settings_prefix =  self::SETTINGS_OPTION_PREFIX;
		foreach ( $valid_settings as $option => $value ) {
			delete_option( $settings_prefix . $option );
		}
	}
}