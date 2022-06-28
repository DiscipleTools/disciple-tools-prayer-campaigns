<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of campaign settings
 */
class DT_Campaign_Settings {
    private $settings_key = 'dt_prayer_campaign_settings';

    private function get_all() {
        return get_option( $this->settings_key, [] );
    }

    public function get( string $name ) {
        $settings = $this->get_all();

        if ( isset( $settings[$name] ) ) {
            return $settings[$name];
        }

        return null;
    }

    public function update( string $name, $value ) {
        $settings = $this->get_all();

        if ( !$value || empty( $value ) ) {
            unset( $settings[$name] );
        } else {
            $new_setting = [];

            $new_setting[$name] = $value;

            $settings = array_merge( $settings, $new_setting );
        }

        update_option( $this->settings_key, $settings );
    }

    public static function get_campaign() {

        $selected_campaign = get_option( 'dt_campaign_selected_campaign', false );

        if ( empty( $selected_campaign ) ) {
            return [];
        }

        $campaign = DT_Posts::get_post( 'campaigns', (int) $selected_campaign, true, false );
        if ( is_wp_error( $campaign ) ) {
            return [];
        }

        return $campaign;
    }
}
