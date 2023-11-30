<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Campaign_Languages {

    const CACHE_EXPIRY = 3600000;
    private $option_name = 'dt_campaign_languages';
    private $allowed_fields = [
        'language',
        'label',
        'native_name',
        'flag',
        'enabled',
    ];
    private $default_language = 'en_US';

    /**
     * Add new language
     *
     * @param string $code
     * @param array $language
     */
    public function add( string $code, array $language ) {
        $languages = $this->get();

        if ( $this->is_language_in_list( $code, $languages ) ) {
            return;
        }

        $new_language = [
            'language' => $code,
            'label' => $language['label'],
            'english_name' => $language['label'],
            'native_name' => $language['native_name'],
            'flag' => $language['flag'],
            'rtl' => $language['rtl'],
            'enabled' => true,
        ];
        $custom_options = get_option( $this->option_name, [] );
        $custom_options[$code] = $new_language;
        update_option( $this->option_name, $custom_options );

        return $this->get( true );
    }

    /**
     * Add a language from the list of names, codes, flags etc.
     *
     * @param string $code
     */
    public function add_from_code( $code ) {
        $available_languages = $this->language_list();

        if ( !array_key_exists( $code, $available_languages ) ) {
            return;
        }

        $new_language = $available_languages[$code];

        $this->add( $code, $new_language );
    }

    /**
     * Get the available languages
    */
    public function get( $reset_cache = false ) {
        $languages = $reset_cache ? [] : $this->get_from_cache();

        if ( empty( $languages ) ) {
            $languages = $this->get_from_options_or_defaults();

            $this->cache( $languages );
        }

        return $languages;
    }

    /**
     * Get the enabled languages
     */
    public function get_enabled_languages() {
        $languages = $this->get();

        $filter_by_enabled = function ( $language ) {
            return isset( $language['enabled'] ) && $language['enabled'] === true;
        };

        return array_filter( $languages, $filter_by_enabled );
    }

    /**
     * Get the direction of the language
     *
     * @param string $lang
     * @return string
     */
    public function get_language_direction( string $lang ) {
        $languages = $this->get_enabled_languages();
        if ( $this->is_language_in_list( $lang, $languages ) ) {
            $language = $languages[$lang];

            if ( isset( $language['rtl'] ) && $language['rtl'] === true ) {
                return 'rtl';
            }
        }

         return 'ltr';
    }

    /**
     * Update a language
     *
     * @param string $code
     * @param array $updates
     */
    public function update( string $code, array $updates ) {
        return $this->apply_updates( $code, $updates );
    }

    /**
     * Disable language
     *
     * @param string $code
     */
    public function disable( string $code ) {
        $updates = [
            'enabled' => false,
        ];

        return $this->apply_updates( $code, $updates );
    }

    /**
     * Enable language
     *
     * @param string $code
     */
    public function enable( string $code ) {
        $updates = [
            'enabled' => true,
        ];

        return $this->apply_updates( $code, $updates );
    }

    /**
     * Remove language
     *
     * @param string $code
     */
    public function remove( string $code ) {
        $languages = $this->get();

        if ( !$this->is_language_in_list( $code, $languages ) ) {
            return;
        }

        if ( isset( $languages[$code]['default'] ) ) {
            return;
        }
        $custom_options = get_option( $this->option_name, [] );
        if ( !isset( $custom_options[$code] ) ) {
            return;
        }
        unset( $custom_options[$code] );
        update_option( $this->option_name, $custom_options );


        return $this->get( true );
    }

    /**
     * Get the languages from the options or create it from the already translated languages
     *
     * @return array
     */
    private function get_from_options_or_defaults() {
        $custom_languages = get_option( $this->option_name, [] );

        $available_languages = $this->get_from_defaults();

        return dt_array_merge_recursive_distinct( $available_languages, $custom_languages );
    }

    /**
     * Get the languages from the already translated languages
     */
    private function get_from_defaults() {
//        @todo this should not be called on every request.
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

        array_unshift( $available_language_codes, $this->default_language );

        $remove_plugin_name = function ( $code ) {
            return str_replace( 'disciple-tools-prayer-campaigns-', '', $code );
        };

        $available_language_codes = array_map( $remove_plugin_name, $available_language_codes );

        $available_languages = dt_get_available_languages( true, false, $available_language_codes );

        foreach ( $available_languages as $code => $language_info ) {
            $available_languages[$code]['enabled'] = $code === 'en_US';
            $available_languages[$code]['default'] = true;
        }

        return $available_languages;
    }

    /**
     * Get the languages from the cache
     */
    private function get_from_cache() {
        $cache = wp_cache_get( $this->option_name );

        return !$cache ? [] : $cache;
    }

    /**
     * Check that the code is in the list of languages
     *
     * @param string $code
     * @param array $languages
     */
    private function is_language_in_list( string $code, array $languages ) {
        return isset( $languages[$code] );
    }

    /**
     * cache the languages
     *
     * @param array $languages
     */
    private function cache( array $languages ) {
        wp_cache_set( $this->option_name, $languages, '', self::CACHE_EXPIRY );
    }

    /**
     * Enable language
     *
     * @param string $code
     */
    public function apply_updates( string $code, array $updates ) {
        $languages = $this->get();

        if ( !$this->is_language_in_list( $code, $languages ) ) {
            return;
        }


        $custom_options = get_option( $this->option_name, [] );
        if ( !isset( $custom_options[$code] ) ) {
            $custom_options[$code] = [];
        }
        $custom_options[$code] = array_merge( $custom_options[$code], $updates );
        update_option( $this->option_name, $custom_options );

        return $this->get( true );
    }


    /**
     *  Merge the updates into the languages array
     *
     * @param array $languages
     * @param string $code
     * @param array $updates
     */
    private function merge_updates( $languages, $code, $updates ) {
        foreach ( $updates as $key => $value ) {
            if ( !in_array( $key, $this->allowed_fields, true ) ) {
                continue;
            }

            $languages[$code][$key] = $value;
        }

        return $languages;
    }

    /**
     * The master list of ready made languages
     *
     * @return array
     */
    public function language_list() {
        return dt_get_available_languages( false, true );
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
