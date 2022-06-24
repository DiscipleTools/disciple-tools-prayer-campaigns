<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of porch settings
 */
class DT_Porch_Settings {

    private static $values_option = 'dt_campaign_porch_settings';
    private static $translations_option = 'dt_campaign_porch_translations';

    /**
     * Get all of the settings for the porch
     *
     * @param string $tab The name of the tab that we want the settings for
     * @param string $section The name of the section we want the settings for
     */
    public static function settings( string $tab = null, string $section = null ): array {
        $defaults = self::fields();

        $saved_fields = self::get_values();

        $lang = dt_campaign_get_current_lang();

        /**
         * Filter to allow the defaults to be changed according to the saved settings and the current language
         *
         * @param array $defaults contains all possible porch settings
         * @param array $saved_fields contains the current saved porch settings
         * @param string $lang the current language the campaign is being viewed in
         */
        $defaults = apply_filters( 'dt_campaign_fields_after_get_saved_settings', $defaults, $saved_fields, $lang );

        $saved_translations = self::get_translations();

        /**
         * Filter to allow the translations to be run through any porch specific massaging
         */
        $saved_translations = apply_filters( 'dt_campaign_translations', $saved_translations, $lang );

        $merged_settings = dt_merge_settings( $saved_fields, $defaults );
        $merged_settings = dt_merge_settings( $saved_translations, $merged_settings, 'translations' );

        if ( $tab !== null ) {
            $merged_settings = self::filter_settings( $merged_settings, 'tab', $tab );
        }
        if ( $section !== null ) {
            $merged_settings = self::filter_settings( $merged_settings, 'section', $section );
        }

        return $merged_settings;
    }

    /**
     * Filter the settings according to a key and a value
     *
     * If the $value is an empty string, settings which don't include the key or which are falsey will be returned.
     *
     * @param array $settings
     * @param string $key
     * @param string $value
     */
    private static function filter_settings( array $settings, string $key, string $value ): array {
        $match_settings_with_tab = function( $setting ) use ( $key, $value ) {
            if ( $value === "" && ( !isset( $setting[$key] ) || !$setting[$key] ) ) {
                return true;
            } elseif ( isset( $setting[$key] ) && $setting[$key] === $value ) {
                return true;
            }

            return false;
        };

        return array_filter( $settings, $match_settings_with_tab );
    }

    private static function get_values() {
        return get_option( self::$values_option, [] );
    }

    private static function get_translations() {
        return get_option( self::$translations_option, [] );
    }

    public static function update_values( $updates ) {
        $current_settings = self::get_values();

        $updated_settings = dt_validate_settings( $updates, self::fields() );

        $updated_settings = array_merge( $current_settings, $updated_settings );

        update_option( self::$values_option, $updated_settings );
    }

    public static function update_translations( $new_translations ) {
        $current_translations = self::get_translations();

        $updated_translations = dt_validate_settings( $new_translations, self::fields() );

        $updated_translations = array_merge( $current_translations, $updated_translations );

        update_option( self::$translations_option, $updated_translations );
    }

    public static function reset() {
        update_option( 'dt_campaign_porch_settings', [] );
    }

    public static function fields() {
        $defaults = self::get_defaults();

        /**
         * Filter to allow porches to add their own specific settings when they are loaded
         *
         * @param array $defaults contains the settings common to all porches
         */
        $defaults = apply_filters( 'dt_campaign_porch_settings', $defaults );

        return $defaults;
    }

    /**
     * Get the list of sections defined in the fields for a particular tab
     *
     * Fields with no section are considered to be in the "Other" section
     *
     * @param string $tab
     */
    public static function sections( string $tab ) {
        $fields = self::fields();

        $fields = self::filter_settings( $fields, 'tab', $tab );

        $sections = [];

        $has_fields_with_no_section = false;
        foreach ( $fields as $key => $field ) {
            if ( !$has_fields_with_no_section && ( !isset( $field["section"] ) || !$field["section"] ) ) {
                $has_fields_with_no_section = true;
            }
            if ( isset( $field["section"] ) && !in_array( $field["section"], $sections, true ) ) {
                $sections[] = $field["section"];
            }
        }

        if ( $has_fields_with_no_section ) {
            array_push( $sections, "" );
        }

        return $sections;
    }

    public static function get_field_translation( string $field_name, string $code = '' ) {
        if ( !$code ) {
            $code = dt_campaign_get_current_lang();
        }

        $fields = self::settings();

        if ( empty( $field_name ) || !isset( $fields[$field_name] ) ) {
            return "";
        }

        $field = $fields[$field_name];

        if ( isset( $field["translations"][$code] ) && !empty( $field["translations"][$code] ) ) {
            return $field["translations"][$code];
        }

        return $field["value"] ?: ( $field["default"] ?? "" );
    }

    private static function get_defaults() {
        $defaults = [
                    'theme_color' => [
                        'label' => 'Theme Color',
                        'value' => 'preset',
                        'type' => 'theme_select',
                        'tab' => 'settings',
                    ],
                    'custom_theme_color' => [
                        'label' => 'Custom Theme Color',
                        'value' => '',
                        'type' => 'text',
                        'tab' => 'settings',
                    ],
                    'logo_url' => [
                        'label' => 'Logo Image URL',
                        'value' => '',
                        'type' => 'text',
                        'tab' => 'settings',
                    ],
                    'logo_link_url' => [
                        'label' => 'Logo Link to URL',
                        'value' => '',
                        'type' => 'text',
                        'tab' => 'settings',
                    ],
                    'header_background_url' => [
                        'label' => 'Header Background URL',
                        'value' => trailingslashit( plugin_dir_url( __FILE__ ) ) . 'site/img/stencil-header.png',
                        'type' => 'text',
                        'tab' => 'settings',
                    ],
                    'what_image' => [
                        'label' => 'What is 24/7 Image',
                        'value' => '',
                        'type' => 'text',
                        'enabled' => false,
                        'tab' => 'settings',
                    ],
                    'show_prayer_timer' => [
                        'label' => 'Show Prayer Timer',
                        'default' => 'Yes',
                        'value' => 'yes',
                        'type' => 'prayer_timer_toggle',
                        'tab' => 'settings',
                    ],
                    'facebook' => [
                        'label' => 'Facebook Url',
                        'value' => '',
                        'type' => 'text',
                        'tab' => 'settings',
                    ],
                    'instagram' => [
                        'label' => 'Instagram Url',
                        'value' => '',
                        'type' => 'text',
                        'tab' => 'settings',
                    ],
                    'twitter' => [
                        'label' => 'Twitter Url',
                        'value' => '',
                        'type' => 'text',
                        'tab' => 'settings',
                    ],
                    'google_analytics' => [
                        'label' => 'Google Analytics',
                        'default' => get_site_option( "p4r_porch_google_analytics" ),
                        'value' => '',
                        'type' => 'textarea',
                        'tab' => 'settings',
                    ],
                    'default_language' => [
                        'label' => 'Default Language',
                        'default' => 'en_US',
                        'value' => '',
                        'type' => 'default_language_select',
                        'tab' => 'settings',
                    ],
                ];

        $keep_enabled_settings = function ( $setting ) {
            return !isset( $setting['enabled'] ) || $setting['enabled'] !== false;
        };

        return array_filter( $defaults, $keep_enabled_settings );
    }
}
