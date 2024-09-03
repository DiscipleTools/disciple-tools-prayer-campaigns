<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of campaign settings
 */
class DT_Campaign_Global_Settings {
    private static $settings_key = 'dt_prayer_campaign_settings';

    private static function get_all() {
//        $campaigns = self::get_campaign();
        return get_option( self::$settings_key, [] );
    }

    public static function get( string $name, $default = null ) {
        $settings = self::get_all();

        if ( isset( $settings[$name] ) ) {
            return $settings[$name];
        }

        return $default;
    }

    public static function update( string $name, $value ) {
        $settings = self::get_all();

        if ( $value === false ){
            $settings[$name] = false;
        } elseif ( empty( $value ) ) {
            unset( $settings[$name] );
        } else {
            $new_setting = [];

            $new_setting[$name] = $value;

            $settings = array_merge( $settings, $new_setting );
        }

        update_option( self::$settings_key, $settings );
    }
}
