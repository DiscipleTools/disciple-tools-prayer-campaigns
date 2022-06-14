<?php

function recursive_parse_args( $args, $defaults ) {
    $new_args = (array) $defaults;

    foreach ( $args ?: [] as $key => $value ) {
        if ( is_array( $value ) && isset( $new_args[ $key ] ) ) {
            $new_args[ $key ] = recursive_parse_args( $value, $new_args[ $key ] );
        }
        elseif ( $key !== "default" ){
            $new_args[ $key ] = $value;
        }
    }

    return $new_args;
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
