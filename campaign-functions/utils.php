<?php

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
function recursive_parse_args( $args, $defaults ) {
    $new_args = (array) $defaults;

    foreach ( $args ?: [] as $key => $value ) {
        if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
            $new_args[ $key ] = recursive_parse_args( $value, $new_args[ $key ] );
        }
        elseif ( $key !== "default" && isset( $new_args[$key] ) ){
            $new_args[ $key ] = $value;
        }
    }

    return $new_args;
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
function dt_merge_settings( $settings, $defaults, $value_key = "value" ) {
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
