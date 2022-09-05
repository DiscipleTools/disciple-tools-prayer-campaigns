<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Merges the $args and $defaults array recursively through sub arrays
 *
 * Both arrays are expected to have the same shape
 *
 * @param array $args
 * @param array $defaults
 *
 * @return array
 */
if ( !function_exists( 'recursive_parse_args' ) ){
    function recursive_parse_args( $args, $defaults ) {
        $new_args = (array) $defaults;

        foreach ( $args ?: [] as $key => $value ) {
            if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
                $new_args[ $key ] = recursive_parse_args( $value, $new_args[ $key ] );
            }
            elseif ( $key !== 'default' && isset( $new_args[$key] ) ){
                $new_args[ $key ] = $value;
            }
        }

        return $new_args;
    }
}

/**
 * Returns merged values stored in $settings into the $defaults array with the $value_key
 * as the key to store the $settings values in.
 *
 * @param array $settings contains the values to merge in
 * @param array $defaults e.g. contains the information for some fields
 * @param string $value_key stores the contents of $settings at this key in the $defaults array
 *
 * @return array
 */
function dt_merge_settings( $settings, $defaults, $value_key = 'value' ) {
    $new_settings = (array) $defaults;

    foreach ( $settings as $key => $value ) {
        if ( isset( $defaults[$key] ) ) {
            $new_settings[$key][$value_key] = $value;
        }
    }

    return $new_settings;
}

/**
 * Returns filtered array of settings whose keys are found in the defaults array
 *
 * @param array $settings
 * @param array $defaults
 *
 * @return array
 */
function dt_validate_settings( $settings, $defaults ) {
    $keep_settings_in_defaults = function( $value, $key ) use ( $defaults ) {
        return isset( $defaults[$key] );
    };

    return array_filter( $settings, $keep_settings_in_defaults, ARRAY_FILTER_USE_BOTH );
}

/**
 * Return the code of the language that the campaign is currently being viewed in.
 *
 * This could come from the GET request or be stored in the browser's cookies
 *
 * @return string
 */
function dt_campaign_get_current_lang(): string {
    $lang = 'en_US';
    if ( defined( 'PORCH_DEFAULT_LANGUAGE' ) ){
        $lang = PORCH_DEFAULT_LANGUAGE;
    }
    if ( isset( $_GET['lang'] ) && !empty( $_GET['lang'] ) ){
        $lang = sanitize_text_field( wp_unslash( $_GET['lang'] ) );
    } elseif ( isset( $_COOKIE['dt-magic-link-lang'] ) && !empty( $_COOKIE['dt-magic-link-lang'] ) ){
        $lang = sanitize_text_field( wp_unslash( $_COOKIE['dt-magic-link-lang'] ) );
    }
    return $lang;
}

function dt_campaign_reload_text_domain(){
    load_plugin_textdomain( 'disciple-tools-prayer-campaigns', false, trailingslashit( dirname( plugin_basename( __FILE__ ), 2 ) ). 'languages' );
}

/**
 * Set the magic link lang in the cookie
 *
 * @param string $lang
 */
function dt_campaign_add_lang_to_cookie( string $lang ) {
    if ( isset( $_GET['lang'] ) && !empty( $_GET['lang'] ) ){
        setcookie( 'dt-magic-link-lang', $lang, 0, '/' );
    }
}


/**
 * Split a sentence of $words into $parts and return the $part that you want.
 *
 * @param string $words The sentence or string of words
 * @param int $part The part of the split sentence to be returned
 * @param int $parts How many parts to split the sentence into
 *
 * @return string
 */
function dt_split_sentence( string $words, int $part, int $parts ) {
    $seperator = ' ';
    $split_words = explode( $seperator, $words );

    /* split the array into two halves with the smaller half last */
    $part_length = floor( count( $split_words ) / $parts );

    if ( $part === $parts ) {
        return implode( $seperator, array_slice( $split_words, ( $part - 1 ) * $part_length ) );
    } else {
        return implode( $seperator, array_slice( $split_words, ( $part - 1 ) * $part_length, $part_length ) );
    }
}

/**
 * Added to dummy out missing function as it was deprecated at PHP7 and is depended on by rss-importer plugin
 */
if ( !function_exists( 'set_magic_quotes_runtime' ) ) {
    function set_magic_quotes_runtime( $f = null ) {
        return true;
    }
}
