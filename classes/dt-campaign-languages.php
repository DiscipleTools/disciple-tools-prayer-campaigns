<?php

class DT_Campaign_Languages {

    const CACHE_EXPIRY = 3600000;
    private $option_name = "dt_campaign_languages";
    private $allowed_fields = [
        'language',
        'label',
        'native_name',
        'flag',
        'enabled',
    ];
    private $default_language = "en_US";

    /**
     * Add new langu/age
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
            'label' => $language["label"],
            'english_name' => $language["label"],
            'native_name' => $language["native_name"],
            'flag' => $language["flag"],
            'rtl' => $language["rtl"],
            'enabled' => true,
        ];

        $languages[$code] = $new_language;

        $this->cache( $languages );

        return $languages;
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
    public function get() {
        $languages = $this->get_from_cache();

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
            return isset( $language['enabled'] ) && $language["enabled"] === true;
        };

        return array_filter( $languages, $filter_by_enabled );
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
        /* remove the element relating to $code from the array */
        unset( $languages[$code] );

        $this->cache( $languages );

        return $languages;
    }

    /**
     * Get the languages from the options or create it from the already translated languages
     *
     * @return array
     */
    private function get_from_options_or_defaults() {
        $languages = get_option( $this->option_name, [] );

        $available_languages = $this->get_from_defaults();

        return array_merge( $available_languages, $languages );
    }

    /**
     * Get the languages from the already translated languages
     */
    private function get_from_defaults() {
        $available_language_codes = get_available_languages( untrailingslashit( plugin_dir_path( __DIR__ ) ) .'/languages' );
        array_unshift( $available_language_codes, $this->default_language );

        $remove_plugin_name = function ( $code ) {
            return str_replace( "disciple-tools-prayer-campaigns-", "", $code );
        };

        $available_language_codes = array_map( $remove_plugin_name, $available_language_codes );

        $available_languages = dt_get_available_languages( true, false, $available_language_codes );

        foreach ( $available_languages as $code => $language_info ) {
            $available_languages[$code]['enabled'] = true;
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
        update_option( $this->option_name, $languages );

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

        $languages = $this->merge_updates( $languages, $code, $updates );

        $this->cache( $languages );

        return $languages;
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
}
