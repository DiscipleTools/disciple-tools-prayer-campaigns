<?php

/**
 * This may be obsolete if we are only saving the key value pairs for the
 * settings rather than the whole of the defaults array in the db.
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

function dt_merge_settings( $settings, $defaults, $value_key = "value" ) {
    $new_settings = (array) $defaults;

    foreach ( $settings as $key => $value ) {
        if ( isset( $defaults[$key] ) ) {
            $new_settings[$key][$value_key] = $value;
        }
    }

    return $new_settings;
}

function dt_validate_settings( $settings, $defaults ) {
    $keep_settings_in_defaults = function( $value, $key ) use ( $defaults ) {
        return isset( $defaults[$key] );
    };

    return array_filter( $settings, $keep_settings_in_defaults, ARRAY_FILTER_USE_BOTH );
}

function dt_campaign_get_current_lang(){
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
