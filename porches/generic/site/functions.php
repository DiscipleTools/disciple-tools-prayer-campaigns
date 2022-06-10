<?php


function dt_ramadan_list_languages(){
    $available_language_codes = get_available_languages( plugin_dir_path( __DIR__ ) .'/support/languages' );
    array_unshift( $available_language_codes, 'en_US' );

    $available_translations = [];

    //flags from https://www.alt-codes.net/flags
    $translations = [
        'en_US' => [
            'language' => 'en_US',
            'english_name' => 'English (United States)',
            'native_name' => 'English',
            'flag' => '🇺🇸',
            'prayer_fuel' => true
        ],
        'es_ES' => [
            'language' => 'es_ES',
            'english_name' => 'Spanish (Spain)',
            'native_name' => 'Español',
            'flag' => '🇪🇸',
            'prayer_fuel' => true
        ],
        'fr_FR' => [
            'language' => 'fr_FR',
            'english_name' => 'French (France)',
            'native_name' => 'Français',
            'flag' => '🇫🇷',
            'prayer_fuel' => true
        ],
        'pt_PT' => [
            'language' => 'pt_PT',
            'english_name' => 'Portuguese',
            'native_name' => 'Português',
            'flag' => '🇵🇹',
            'prayer_fuel' => true
        ],
        'id_ID' => [
            'language' => "id_ID",
            'english_name' => 'Indonesian',
            'native_name' => 'Bahasa Indonesia',
            'flag' => '🇮🇩',
            'prayer_fuel' => true
        ],
        'nl_NL' => [
            'language' => "nl_NL",
            'english_name' => 'Dutch',
            'native_name' => 'Nederlands',
            'flag' => '🇳🇱',
        ],
        'ar_EG' => [
            'language' => 'ar_EG',
            'english_name' => 'Arabic',
            'native_name' => 'العربية',
            'flag' => '🇪🇬',
            'prayer_fuel' => true,
            'dir' => 'rtl'
        ],
        'ru_RU' => [
            'language' => 'ru_RU',
            'english_name' => 'Russian',
            'native_name' => 'русский',
            'flag' => '🇷🇺',
            'prayer_fuel' => true,
        ],
//        'bn_BD' => [
//            'language' => 'bn_BD',
//            'english_name' => 'Bengali',
//            'native_name' => 'বাংলা',
//            'flag' => '🇧🇩',
//            'prayer_fuel' => true,
//        ],
    ];

    foreach ( $available_language_codes as $code ){
        $code = str_replace( "pray4ramadan-porch-", "", $code );
        if ( isset( $translations[$code] ) ){
            $available_translations[$code] = $translations[$code];
        }
    }
    return apply_filters( 'dt_ramadan_list_languages', $available_translations );
}

function get_field_translation( $field, $code ){
    if ( empty( $field ) ){
        return "";
    }
    if ( isset( $field["translations"][$code] ) && !empty( $field["translations"][$code] ) ){
        return $field["translations"][$code];
    }
    return $field["value"] ?: ( $field["default"] ?? "" );
}

function dt_ramadan_get_current_lang(){
    $lang = "en_US";
    if ( defined( "PORCH_DEFAULT_LANGUAGE" ) ){
        $lang = PORCH_DEFAULT_LANGUAGE;
    }
    if ( isset( $_GET["lang"] ) && !empty( $_GET["lang"] ) ){
        $lang = sanitize_text_field( wp_unslash( $_GET["lang"] ) );
    } elseif ( isset( $_COOKIE["dt-magic-link-lang"] ) && !empty( $_COOKIE["dt-magic-link-lang"] ) ){
        $lang = sanitize_text_field( wp_unslash( $_COOKIE["dt-magic-link-lang"] ) );
    }
    return $lang;
}

function dt_ramadan_set_translation( $lang ){
    if ( $lang !== "en_US" ){
        add_filter( 'determine_locale', function ( $locale ) use ( $lang ){
            if ( !empty( $lang ) ){
                return $lang;
            }
            return $locale;
        }, 1000, 1 );
        load_plugin_textdomain( 'pray4ramadan-porch', false, trailingslashit( dirname( plugin_basename( __FILE__ ), 2 ) ). 'support/languages' );
    }
}

function ramadan_custom_dir_attr( $lang ){
    if ( is_admin() ) {
        return $lang;
    }

    $lang = dt_ramadan_get_current_lang();

    $translations = dt_ramadan_list_languages();
    $dir = "ltr";
    if ( isset( $translations[$lang]["dir"] ) && $translations[$lang]['dir'] === 'rtl' ){
        $dir = "rtl";
    }

    $dir_attr = 'dir="' . $dir . '"';

    return 'lang="' . $lang .'" ' .$dir_attr;
}


/**
 * Add iFrame to allowed wp_kses_post tags
 *
 * @param array  $tags Allowed tags, attributes, and/or entities.
 * @param string $context Context to judge allowed tags by. Allowed values are 'post'.
 *
 * @return array
 */
function custom_wpkses_post_tags( $tags, $context ){
    if ( 'post' === $context ){
        $tags['iframe'] = [
            'src' => true,
            'height' => true,
            'width' => true,
            'frameborder' => true,
            'allowfullscreen' => true,
        ];
    }

    return $tags;
}
add_filter( 'wp_kses_allowed_html', 'custom_wpkses_post_tags', 10, 2 );
