<?php

class DT_Campaign_Languages {

    const CACHE_EXPIRY = 3600000;
    private $option_name = "dt_campaign_languages";
    private $allowed_fields = [
        'language',
        'english_name',
        'native_name',
        'flag',
        'enabled',
    ];
    private $default_language = "en_US";

    /**
     * Add new langu/age
     *
     * @param string $name
     * @param string $code
     * @param string $native_name
     * @param string $flag
     */
    public function add( string $name, string $code, string $native_name, string $flag ) {
        $languages = $this->get();

        if ( $this->is_language_in_list( $code, $languages ) ) {
            return;
        }

        $new_language = [
            'language' => $code,
            'english_name' => $name,
            'native_name' => $native_name,
            'flag' => $flag,
            'enabled' => true,
        ];

        $languages[$code] = $new_language;

        $this->cache( $languages );

        return $languages;
    }

    public function add_from_code( $code ) {
        $available_languages = $this->language_list();

        if ( !array_key_exists( $code, $available_languages ) ) {
            return;
        }

        $new_language = $available_languages[$code];

        $this->add( $new_language["english_name"], $new_language["language"], $new_language["native_name"], $new_language["flag"] );
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

        $available_language_codes = get_available_languages( plugin_dir_path( __DIR__ ) .'../languages' );
        array_unshift( $available_language_codes, $this->default_language );

        $available_languages = [];
        $language_info = $this->language_list();
        foreach ( $available_language_codes as $code ) {
            $code = str_replace( "disciple-tools-prayer-campaigns-", "", $code );
            if ( isset( $language_info[$code] ) ) {
                $available_languages[$code] = array_merge( $language_info[$code], [
                    'enabled' => true,
                    'default' => true,
                ] );
            }
        }

        return array_merge( $available_languages, $languages );
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
        return [
                'en_US' => [
                    'language' => 'en_US',
                    'english_name' => 'English (United States)',
                    'native_name' => 'English (United States)',
                    'flag' => '🇺🇸'
                ],
                'am_ET' => [
                    'language' => 'am_ET',
                    'native_name' => 'Amharic (Ethiopia)',
                    'english_name' => 'Amharic (Ethiopia)',
                    'flag' => '🇪🇹'
                ],
                'ar' => [
                    'language' => 'ar',
                    'english_name' => 'Arabic',
                    'native_name' => 'العربية',
                    'flag' => '🇦🇪'
                ],
                'ar_MA' => [
                    'language' => 'ar_MA',
                    'native_name' => 'العربية (المغرب)',
                    'english_name' => 'Arabic (Morocco)',
                    'flag' => '🇲🇦'
                ],
                'bg_BG' => [
                    'language' => 'bg_BG',
                    'english_name' => 'Bulgarian',
                    'native_name' => 'Български',
                    'flag' => '🇧🇬'
                ],
                'bn_BD' => [
                    'language' => 'bn_BD',
                    'english_name' => 'Bengali (Bangladesh)',
                    'native_name' => 'বাংলা',
                    'flag' => '🇧🇩'
                ],
                'bs_BA' => [
                    'language' => 'bs_BA',
                    'english_name' => 'Bosnian',
                    'native_name' => 'Bosanski',
                    'flag' => '🇧🇦'
                ],
                'de_DE' => [
                    'language' => 'de_DE',
                    'english_name' => 'German',
                    'native_name' => 'Deutsch',
                    'flag' => '🇩🇪'
                ],
                'es_419' => [
                    'language' => 'es_419',
                    'native_name' => 'Español (Latinoamérica) ',
                    'english_name' => 'Spanish (Latin America)',
                    'flag' => '🇦🇷'
                ],
                'es_ES' => [
                    'language' => 'es_ES',
                    'english_name' => 'Spanish (Spain)',
                    'native_name' => 'Español',
                    'flag' => '🇪🇸'
                ],
                'fa_IR' => [
                    'language' => 'fa_IR',
                    'english_name' => 'Persian',
                    'native_name' => 'فارسی',
                    'flag' => '🇮🇷'
                ],
                'fr_FR' => [
                    'language' => 'fr_FR',
                    'english_name' => 'French (France)',
                    'native_name' => 'Français',
                    'flag' => '🇫🇷'
                ],
                'hi_IN' => [
                    'language' => 'hi_IN',
                    'english_name' => 'Hindi',
                    'native_name' => 'हिन्दी',
                    'flag' => '🇮🇳'
                ],
                'hr' => [
                    'language' => 'hr',
                    'english_name' => 'Croatian',
                    'native_name' => 'Hrvatski',
                    'flag' => '🇭🇷'
                ],
                'hu_HU' => [
                    'language' => 'hu_HU',
                    'english_name' => 'Hungarian',
                    'native_name' => 'Magyar',
                    'flag' => '🇭🇺'
                ],
                'id_ID' => [
                    'language' => 'id_ID',
                    'english_name' => 'Indonesian',
                    'native_name' => 'Bahasa Indonesia',
                    'flag' => '🇮🇩'
                ],
                'it_IT' => [
                    'language' => 'it_IT',
                    'english_name' => 'Italian',
                    'native_name' => 'Italiano',
                    'flag' => '🇮🇹'
                ],
                'ja' => [
                    'language' => 'ja',
                    'english_name' => 'Japanese',
                    'native_name' => '日本語',
                    'flag' => '🇯🇵'
                ],
                'ko_KR' => [
                    'language' => 'ko_KR',
                    'english_name' => 'Korean',
                    'native_name' => '한국어',
                    'flag' => '🇰🇷'
                ],
                'mk_MK' => [
                    'language' => 'mk_MK',
                    'english_name' => 'Macedonian',
                    'native_name' => 'Македонски јазик',
                    'flag' => '🇲🇰'
                ],
                'mr' => [
                    'language' => 'mr',
                    'english_name' => 'Marathi',
                    'native_name' => 'मराठी',
                    'flag' => '🇮🇳'
                ],
                'my_MM' => [
                    'language' => 'my_MM',
                    'english_name' => 'Myanmar (Burmese)',
                    'native_name' => 'ဗမာစာ',
                    'flag' => '🇲🇲'
                ],
                'ne_NP' => [
                    'language' => 'ne_NP',
                    'english_name' => 'Nepali',
                    'native_name' => 'नेपाली',
                    'flag' => '🇳🇵'
                ],
                'nl_NL' => [
                    'language' => 'nl_NL',
                    'english_name' => 'Dutch',
                    'native_name' => 'Nederlands',
                    'flag' => '🇳🇱'
                ],
                'pa_IN' => [
                    'language' => 'pa_IN',
                    'english_name' => 'Punjabi',
                    'native_name' => 'ਪੰਜਾਬੀ',
                    'flag' => '🇮🇳'
                ],
                'pt_BR' => [
                    'language' => 'pt_BR',
                    'english_name' => 'Portuguese (Brazil)',
                    'native_name' => 'Português do Brasil',
                    'flag' => '🇧🇷'
                ],
                'ro_RO' => [
                    'language' => 'ro_RO',
                    'english_name' => 'Romanian',
                    'native_name' => 'Română',
                    'flag' => '🇷🇴'
                ],
                'ru_RU' => [
                    'language' => 'ru_RU',
                    'english_name' => 'Russian',
                    'native_name' => 'Русский',
                    'flag' => '🇷🇺'
                ],
                'sl_SI' => [
                    'language' => 'sl_SI',
                    'english_name' => 'Slovenian',
                    'native_name' => 'Slovenščina',
                    'flag' => '🇸🇮'
                ],
                'sr_BA' => [
                    'language' => 'sr_BA',
                    'native_name' => 'српски',
                    'english_name' => 'Serbian',
                    'flag' => '🇷🇸'
                ],
                'sw' => [
                    'language' => 'sw',
                    'native_name' => 'Kiswahili',
                    'english_name' => 'Swahili',
                    'flag' => '🇹🇿'
                ],
                'th' => [
                    'language' => 'th',
                    'english_name' => 'Thai',
                    'native_name' => 'ไทย',
                    'flag' => '🇹🇭'
                ],
                'tl' => [
                    'language' => 'tl',
                    'english_name' => 'Tagalog',
                    'native_name' => 'Tagalog',
                    'flag' => '🇵🇭'
                ],
                'tr_TR' => [
                    'language' => 'tr_TR',
                    'english_name' => 'Turkish',
                    'native_name' => 'Türkçe',
                    'flag' => '🇹🇷'
                ],
                'uk' => [
                    'language' => 'uk',
                    'english_name' => 'Ukrainian',
                    'native_name' => 'український',
                    'flag' => '🇺🇦'
                ],
                'vi' => [
                    'language' => 'vi',
                    'english_name' => 'Vietnamese',
                    'native_name' => 'Tiếng Việt',
                    'flag' => '🇻🇳'
                ],
                'zh_CN' => [
                    'language' => 'zh_CN',
                    'english_name' => 'Chinese (China)',
                    'native_name' => '简体中文',
                    'flag' => '🇨🇳'
                ],
                'zh_TW' => [
                    'language' => 'zh_TW',
                    'english_name' => 'Chinese (Taiwan)',
                    'native_name' => '繁體中文',
                    'flag' => '🇹🇼'
                ],
            ];
    }
}
