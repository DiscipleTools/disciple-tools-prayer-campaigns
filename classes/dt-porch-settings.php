<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of porch settings
 */
class DT_Porch_Settings {

    /**
     * Get all of the settings for the porch
     *
     * @param string $tab The name of the tab that we want the settings for
     * @param string $section The name of the section we want the settings for
     */
    public static function settings( string $tab = null, string $section = null, $use_cache = true, $campaign_id = null ): array {

        $current_campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
        if ( empty( $current_campaign ) ) {
            return [];
        }
        $cached = wp_cache_get( 'dt_campaign_porch_settings_' . $current_campaign['ID'], 'dt_campaign_porch_settings' );
        if ( $use_cache && $cached && empty( $tab ) && empty( $section ) ) {
            return $cached;
        }
        $campaign_field_settings = DT_Posts::get_post_field_settings( 'campaigns' );

        $lang = dt_campaign_get_current_lang();

        $defaults = [];
        foreach ( $campaign_field_settings as $key => $field ) {
//             if ( !str_starts_with( $field['tile'] ?? '', 'campaign_' ) ) {
// //                continue;
//             }

            $defaults[$key] = $field;
            $defaults[$key]['value'] = $field['type'] === 'key_select' ? '' : ( $field['default'] ?? '' );
            if ( isset( $current_campaign[$key] ) ) {
                if ( $field['type'] === 'key_select' ){
                    $defaults[$key]['value'] = $current_campaign[$key]['key'];
                } else {
                    $defaults[$key]['value'] = $current_campaign[$key];
                }
            }
        }

        /**
         * Filter to allow the defaults to be changed according to the saved settings and the current language
         *
         * @param array $defaults contains all possible porch settings
         * @param array $saved_fields contains the current saved porch settings
         * @param string $lang the current language the campaign is being viewed in
         */
//        $defaults = apply_filters( 'dt_campaign_fields_after_get_saved_settings', $defaults, $saved_fields, $lang );

        $saved_translations = self::get_translations( $current_campaign['ID'] );

        /**
         * Filter to allow the translations to be run through any porch specific massaging
         */
        $saved_translations = apply_filters( 'dt_campaign_translations', $saved_translations, $lang, $current_campaign['ID'] );

        $merged_settings = dt_merge_settings( $saved_translations, $defaults, 'translations' );

        if ( $tab !== null ) {
            $merged_settings = self::filter_settings( $merged_settings, 'campaign_tab', $tab );
        }
        if ( $section !== null ) {
            $merged_settings = self::filter_settings( $merged_settings, 'campaign_section', $section );
        }

        wp_cache_set( 'dt_campaign_porch_settings_' . $current_campaign['ID'], $merged_settings, 'dt_campaign_porch_settings' );

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
            if ( $value === '' && ( !isset( $setting[$key] ) || !$setting[$key] ) ) {
                return true;
            } elseif ( isset( $setting[$key] ) && $setting[$key] === $value ) {
                return true;
            }

            return false;
        };

        return array_filter( $settings, $match_settings_with_tab );
    }

    private static function get_translations( $campaign_id ) {
        return DT_Campaign_Languages::get_translations( $campaign_id );
    }

    public static function update_values( $updates, $campaign_id = null ) {
        $current_campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
        $campaign_field_settings = DT_Posts::get_post_field_settings( 'campaigns' );

        $changes = [];
        foreach ( $updates as $key => $value ){
            $field_type = $campaign_field_settings[$key]['type'] ?? '';
            if ( $field_type === 'key_select' ){
                if ( $value !== ( $current_campaign[$key]['key'] ?? '' ) ){
                    $changes[$key] = $value;
                }
            } else {
                if ( $value !== ( $current_campaign[$key] ?? '' ) ){
                    $changes[$key] = $value;
                }
            }
        }

        if ( !empty( $changes ) ){
            DT_Posts::update_post( 'campaigns', $current_campaign['ID'], $changes, false, false );
        }
        return true;
    }

    public static function update_translations( $campaign_id, $new_translations ) {
        $current_translations = self::get_translations( $campaign_id );

        foreach ( $new_translations as $key => $lang_translations ){
            foreach ( $lang_translations as $language => $value ){
                $existing_translation = isset( $current_translations[$key][$language] ) ? $current_translations[$key][$language] : '';
                if ( $existing_translation !== $value ){
                    DT_Campaign_Languages::save_translation( $campaign_id, $key, $language, $value );
                }
            }
        }

        return true;
    }

    /**
     * Get the list of sections defined in the fields for a particular tab
     *
     * Fields with no section are considered to be in the "Other" section
     *
     * @param string $tab
     */
    public static function sections( string $tab ) {
        $fields = DT_Posts::get_post_field_settings( 'campaigns' );

        $fields = self::filter_settings( $fields, 'tile', $tab );

        $sections = [];

        $has_fields_with_no_section = false;
        foreach ( $fields as $key => $field ) {
            if ( !$has_fields_with_no_section && ( !isset( $field['campaign_section'] ) || !$field['campaign_section'] ) ) {
                $has_fields_with_no_section = true;
            }
            if ( isset( $field['campaign_section'] ) && !in_array( $field['campaign_section'], $sections, true ) ) {
                $sections[] = $field['campaign_section'];
            }
        }

        if ( $has_fields_with_no_section ) {
            array_push( $sections, '' );
        }

        return $sections;
    }

    public static function get_field_translation( string $field_name, string $code = '', $campaign_id = null ) {
        if ( empty( $code ) ) {
            $code = dt_campaign_get_current_lang();
        }

        $fields = self::settings( null, null, true, $campaign_id );

        if ( empty( $field_name ) || !isset( $fields[$field_name] ) ) {
            return '';
        }

        $field = $fields[$field_name];

        if ( isset( $field['translations'][$code] ) && !empty( $field['translations'][$code] ) ) {
            return $field['translations'][$code];
        }

        return $field['value'] ?: ( $field['default'] ?? '' );
    }


    public static function has_user_translations() {
        $settings = self::settings();

        foreach ( $settings as $key => $value ) {
            if ( !in_array( $value['type'], [ 'text', 'textarea' ] ) ) {
                continue;
            }

            if ( ( $value['default'] ?? '' ) !== $value['value'] ) {
                return true;
            }
        }

        return false;
    }
}
