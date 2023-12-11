<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Campaign_Languages {
    /**
     * Add a language from the list of names, codes, flags etc.
     *
     * @param string $code
     */
    public static function add_from_code( $code ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $enabled_languages = $campaign['enabled_languages'] ?? [ 'en_US' ];

        if ( !in_array( $code, $enabled_languages ) ){
            DT_Posts::update_post( 'campaigns', $campaign['ID'], [ 'enabled_languages' => [ 'values' => [ [ 'value' => $code ] ] ] ] );
        }
        return self::get_enabled_languages( $campaign['ID'] );
    }

    /**
     * Get the available languages
    */
    public static function get_installed_languages( $campaign_id ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
        $enabled_languages = $campaign['enabled_languages'] ?? [ 'en_US' ];
        $installed_languages = self::get_translated_languages();


        $all = array_unique( array_merge( $enabled_languages, $installed_languages ) );

        $available_languages = self::get_languages_for_codes( $all );

        foreach ( $available_languages as $code => $language ){
            if ( in_array( $code, $enabled_languages ) ){
                $available_languages[$code]['enabled'] = true;
            } else {
                $available_languages[$code]['enabled'] = false;
            }
            if ( in_array( $code, $installed_languages ) ){
                $available_languages[$code]['default'] = true;
            }
        }

        return $available_languages;
    }

    /**
     * Get the enabled languages
     */
    public static function get_enabled_languages( $campaign_id ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
        $enabled_codes = $campaign['enabled_languages'] ?? [ 'en_US' ];

        $languages = self::get_languages_for_codes( $enabled_codes );

        return $languages;
    }

    /**
     * Get the direction of the language
     *
     * @param string $lang
     * @return string
     */
    public static function get_language_direction( string $lang ) {
        $all_languages = dt_get_global_languages_list();
        if ( isset( $all_languages[ $lang ] ) ) {
            $language = $all_languages[$lang];

            if ( isset( $language['rtl'] ) && $language['rtl'] === true ) {
                return 'rtl';
            }
        }

         return 'ltr';
    }

    /**
     * Disable language
     *
     * @param string $code
     */
    public static function disable( string $code ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $enabled_languages = $campaign['enabled_languages'] ?? [ 'en_US' ];

        if ( in_array( $code, $enabled_languages ) ){
            DT_Posts::update_post( 'campaigns', $campaign['ID'], [ 'enabled_languages' => [ 'values' => [ [ 'value' => $code, 'delete' => true ] ] ] ] );
        }
        return self::get_enabled_languages( $campaign['ID'] );
    }

    /**
     * Enable language
     *
     * @param string $code
     */
    public static function enable( string $code ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $enabled_languages = $campaign['enabled_languages'] ?? [ 'en_US' ];

        if ( !in_array( $code, $enabled_languages ) ){
            DT_Posts::update_post( 'campaigns', $campaign['ID'], [ 'enabled_languages' => [ 'values' => [ [ 'value' => $code ] ] ] ] );
        }
        return self::get_enabled_languages( $campaign['ID'] );
    }

    /**
     * Remove language
     *
     * @param string $code
     */
    public static function remove( string $code ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $enabled_languages = $campaign['enabled_languages'] ?? [ 'en_US' ];

        if ( in_array( $code, $enabled_languages ) ){
            DT_Posts::update_post( 'campaigns', $campaign['ID'], [ 'enabled_languages' => [ 'values' => [ [ 'value' => $code, 'delete' => true ] ] ] ] );
        }
        return self::get_enabled_languages( $campaign['ID'] );
    }

    private static function get_languages_for_codes( $codes ){
        return dt_get_available_languages( true, false, $codes );
    }

    private static function get_translated_languages(){
        $installed_languages = get_available_languages( untrailingslashit( plugin_dir_path( __DIR__ ) ) .'/languages' );

        $available_language_codes = [ 'en_US' ];
        foreach ( $installed_languages as $code ) {
            unload_textdomain( 'disciple-tools-prayer-campaigns' );
            $lang = str_replace( 'disciple-tools-prayer-campaigns-', '', $code );
            add_filter( 'determine_locale', function ( $locale ) use ( $lang ){
                if ( !empty( $lang ) ){
                    return $lang;
                }
                return $locale;
            }, 1000, 1 );
            switch_to_locale( $lang );
            dt_campaign_reload_text_domain();
            $translated = __( 'Strategic prayer for a Disciple Making Movement', 'disciple-tools-prayer-campaigns' );
            if ( $translated !== 'Strategic prayer for a Disciple Making Movement' ){
                $available_language_codes[] = $lang;
            }
        }
        switch_to_locale( 'en_US' );

        array_unshift( $available_language_codes, 'en_US' );

        $remove_plugin_name = function ( $code ) {
            return str_replace( 'disciple-tools-prayer-campaigns-', '', $code );
        };

        $available_language_codes = array_map( $remove_plugin_name, $available_language_codes );

        return $available_language_codes;
    }

    public static function get_translation( $campaign_id, $key, $language, $default = '' ){
        global $wpdb;
        $wpdb->dt_translations = $wpdb->prefix . 'dt_translations';
        $translation = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $wpdb->dt_translations WHERE post_id = %d AND `key` = %s AND language = %s", $campaign_id, $key, $language ),
        ARRAY_A );
        if ( $translation ){
            return $translation['value'];
        }
        return $default;
    }

    public static function save_translation( $campaign_id, $key, $language, $value ){
        global $wpdb;
        $wpdb->dt_translations = $wpdb->prefix . 'dt_translations';

        $existing_translation = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $wpdb->dt_translations WHERE post_id = %d AND `key` = %s AND language = %s", $campaign_id, $key, $language ),
        ARRAY_A );

        if ( $existing_translation ){
            $wpdb->query( $wpdb->prepare( "
                UPDATE $wpdb->dt_translations
                SET value = %s 
                WHERE post_id = %d AND `key` = %s AND language = %s",
            $value, $campaign_id, $key, $language ) );
        } else {
            $wpdb->query( $wpdb->prepare( "
                INSERT INTO $wpdb->dt_translations (post_id, `key`, language, value)
                VALUES (%d, %s, %s, %s)",
            $campaign_id, $key, $language, $value ) );
        }
    }

    public static function get_key_translations( $campaign_id, $key ){
        global $wpdb;
        $wpdb->dt_translations = $wpdb->prefix . 'dt_translations';
        $translations = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM $wpdb->dt_translations WHERE post_id = %d AND `key` = %s", $campaign_id, $key ),
        ARRAY_A );

        $translations_by_language = [];
        foreach ( $translations as $translation ){
            $translations_by_language[$translation['language']] = $translation['value'];
        }
        return $translations_by_language;
    }
    public static function get_translations( $campaign_id ){
        global $wpdb;
        $wpdb->dt_translations = $wpdb->prefix . 'dt_translations';
        $translation_values = $wpdb->get_results(
            $wpdb->prepare( "
                SELECT * FROM $wpdb->dt_translations
                WHERE post_id = %d
                ",
            $campaign_id ),
        ARRAY_A );

        $translations = [];
        foreach ( $translation_values as $translation ){
            if ( !isset( $translations[$translation['key']] ) ){
                $translations[$translation['key']] = [];
            }
            $translations[$translation['key']][$translation['language']] = $translation['value'];
        }
        return $translations;
    }
}
