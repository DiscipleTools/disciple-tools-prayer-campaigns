<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of porch settings
 */
class DT_Porch_Settings {

    public static function porch_fields() {
        $defaults = self::get_defaults();

        /**
         * Filter to allow porches to add their own specific settings when they are loaded
         *
         * @param array $defaults contains the settings common to all porches
         */
        $defaults = apply_filters( 'dt_campaign_porch_settings', $defaults );

        $saved_fields = get_option( 'dt_campaign_porch_settings', [] );

        $lang = dt_campaign_get_current_lang();

        /**
         * Filter to allow the defaults to be changed according to the saved settings and the current language
         *
         * @param array $defaults contains all possible porch settings
         * @param array $saved_fields contains the current saved porch settings
         * @param string $lang the current language the campaign is being viewed in
         */
        $defaults = apply_filters( 'dt_campaign_after_get_porch_fields', $defaults, $saved_fields, $lang );

        return recursive_parse_args( $saved_fields, $defaults );
    }

    public static function get_field_translation( $field, $code ) {
        if ( empty( $field ) ) {
            return "";
        }

        if ( isset( $field["translations"][$code] ) && !empty( $field["translations"][$code] ) ) {
            return $field["translations"][$code];
        }

        return $field["value"] ?: ( $field["default"] ?? "" );
    }

    private static function get_defaults() {
        return [
                    'theme_color' => [
                        'label' => 'Theme Color',
                        'value' => 'preset',
                        'type' => 'theme_select',
                    ],
                    'custom_theme_color' => [
                        'label' => 'Custom Theme Color',
                        'value' => '',
                        'type' => 'text'
                    ],
                    'logo_url' => [
                        'label' => 'Logo Image URL',
                        'value' => '',
                        'type' => 'text',
                    ],
                    'logo_link_url' => [
                        'label' => 'Logo Link to URL',
                        'value' => '',
                        'type' => 'text',
                    ],
                    'header_background_url' => [
                        'label' => 'Header Background URL',
                        'value' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'site/img/stencil-header.png',
                        'type' => 'text',
                    ],
                    'what_image' => [
                        'label' => 'What is Ramadan Image',
                        'value' => '',
                        'type' => 'text',
                        'enabled' => false
                    ],
                    'show_prayer_timer' => [
                        'label' => 'Show Prayer Timer',
                        'default' => 'Yes',
                        'value' => 'yes',
                        'type' => 'prayer_timer_toggle',
                    ],
                    'facebook' => [
                        'label' => 'Facebook Url',
                        'value' => '',
                        'type' => 'text',
                    ],
                    'instagram' => [
                        'label' => 'Instagram Url',
                        'value' => '',
                        'type' => 'text',
                    ],
                    'twitter' => [
                        'label' => 'Twitter Url',
                        'value' => '',
                        'type' => 'text',
                    ],
                    'google_analytics' => [
                        'label' => 'Google Analytics',
                        'default' => get_site_option( "p4r_porch_google_analytics" ),
                        'value' => '',
                        'type' => 'textarea',
                    ],
                    'default_language' => [
                        'label' => 'Default Language',
                        'default' => 'en_US',
                        'value' => '',
                        'type' => 'default_language_select',
                    ],
                ];
    }
}
