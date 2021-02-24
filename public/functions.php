<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/********************************************************
 * BUILD VARs
 ********************************************************/
function dt_prayer_root(){
    return 'prayer';
}

function dt_prayer_make_public_id() : string {
    try {
        $hash = hash( 'sha256', bin2hex( random_bytes( 64 ) ) );
    } catch ( Exception $exception ) {
        $hash = hash( 'sha256', bin2hex( rand( 0, 1234567891234567890 ) . microtime() ) );
    }
    return $hash;
}

function dt_prayer_registered_types(){
    return apply_filters( 'dt_prayer_register_type', $types = [] );
}

function dt_prayer_types() : array {
    return array_keys( dt_prayer_registered_types() );
}

function dt_prayer_actions( $type ) : array {
    $types = dt_prayer_registered_types();
    if ( isset( $types[$type]['actions'] ) && ! empty( $types[$type]['actions'] ) && is_array( $types[$type]['actions'] ) ) {
        return $types[$type]['actions'];
    } else {
        return [];
    }
}

function dt_prayer_url_bases() : array{
    $url = [];
    $root = dt_prayer_root();
    $types = dt_prayer_types();
    foreach ( $types as $type){
        $url[] = $root . '/'. $type;
    }
    return $url;
}

function dt_prayer_is_form_url( string $type ) {
    $parts = dt_prayer_parse_url_parts();
    if ( empty( $parts ) ){ // fail if not prayer url
        return false;
    }
    if ( $type !== $parts['type'] ){ // fail if not saturation type
        return false;
    }
    if ( ! empty( $parts['public_key'] ) ) { // fail if not specific contact
        return false;
    }
    return $parts;
}
function dt_prayer_is_contact_url( string $type ) {
    $parts = dt_prayer_parse_url_parts();
    if ( empty( $parts ) ){ // fail if not prayer url
        return false;
    }
    if ( $type !== $parts['type'] ){ // fail if not saturation type
        return false;
    }
    if ( empty( $parts['public_key'] ) ) { // fail if not specific contact
        return false;
    }
    if ( empty( $parts['post_id'] )) { // fail if no post id
        return false;
    }
    return $parts;
}



/********************************************************
 * PARSE URL UTILITIES
 ********************************************************/
/**
 * Parse URL into expected parts
 * @return false|string[]
 */
function dt_prayer_parse_url_parts(){
    // get required url elements
    $root = dt_prayer_root();
    $types = dt_prayer_types();

    // get url, create parts array and sanitize
    $url_path = dt_get_url_path();
    $parts = explode( '/', $url_path );
    $parts = array_map( 'sanitize_key', wp_unslash( $parts ) );

    // test :
    // correct root
    // approved type
    if ( isset( $parts[0] ) && $root === $parts[0] && isset( $parts[1] ) && in_array( $parts[1], $types ) ){
        $elements = [
            'root' => '',
            'type' => '',
            'public_key' => '',
            'action' => '',
            'post_id' => '',
        ];
        if ( isset( $parts[0] ) && ! empty( $parts[0] ) ){
            $elements['root'] = $parts[0];
        }
        if ( isset( $parts[1] ) && ! empty( $parts[1] ) ){
            $elements['type'] = $parts[1];
        }
        if ( isset( $parts[2] ) && ! empty( $parts[2] ) ){
            $elements['public_key'] = $parts[2];

            // also add post_id
            $post_id = dt_prayer_post_id( $elements['type'], $parts[2] );
            if ( ! $post_id ){ // fail if no post id for public key
                return false;
            } else {
                $elements['post_id'] = $post_id;
            }
        }
        if ( isset( $parts[3] ) && ! empty( $parts[3] ) ){
            $elements['action'] = $parts[3];
        }
        return $elements;
    }
    return false;
}

/**
 * Gets the prayer key from the URL if exists
 * @return false|string
 */
function get_dt_prayer_public_key(){
    $parts = dt_prayer_parse_url_parts();
    if ( isset( $parts['public_key'] ) && ! empty( $parts['public_key'] ) ) {
        return $parts['public_key'];
    }
    return false;
}

/**
 * @return false|string
 */
function get_dt_prayer_action(){
    $parts = dt_prayer_parse_url_parts();
    if ( isset( $parts['action'] ) && ! empty( $parts['action'] ) ) {
        return (string) $parts['action'];
    }
    return false;
}
/**
 * Get post id from public key
 * @param $public_key
 * @return false|string
 */
function dt_prayer_post_id( $type, $public_key ){
    global $wpdb;
    $meta_key = 'public_key_prayer_'.$type;
    $result = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $public_key ) );
    if ( ! empty( $result ) && ! is_wp_error( $result ) ){
        return $result;
    }
    return false;
}

function dt_prayer_confirm_subscriptions( $post_id, $type ){
    if ( get_post_meta( $post_id, 'unconfirmed_prayer_'.$type, true ) ) {
        delete_post_meta( $post_id, 'unconfirmed_prayer_'.$type, true );
        $fields = [
            "overall_status" => "active"
        ];
        DT_Posts::update_post( 'contacts', (int) $post_id, $fields, false, false );
    }
}



/********************************************************
 * REGISTER VALID URL
 ********************************************************/
add_filter( 'dt_templates_for_urls', function( $template_for_url) : array {
    $parts = dt_prayer_parse_url_parts();

    // test 1 : correct url root and type
    if ( ! $parts ){ // parts returns false
        return $template_for_url;
    }

    // test 2 : only base url requested
    if ( empty( $parts['public_key'] ) ){ // no public key present
        $template_for_url[ $parts['root'] . '/'. $parts['type'] ] = 'template-blank.php';
        return $template_for_url;
    }

    // test 3 : no specific action requested
    if ( empty( $parts['action'] ) ){ // only root public key requested
        $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ] = 'template-blank.php';
        return $template_for_url;
    }

    // test 4 : valid action requested
    $actions = dt_prayer_actions( $parts['type'] );
    if ( in_array( $parts['action'], $actions ) ){
        $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $parts['action'] ] = 'template-blank.php';
    }

    return $template_for_url;
}, 199 );


/********************************************************
 * SETUP
 ********************************************************/
add_filter( 'dt_blank_access', 'dt_prayer_has_access' );
function dt_prayer_has_access() : bool {
    $parts = dt_prayer_parse_url_parts();

    // test 1 : correct url root and type
    if ( $parts ){ // parts returns false
        return true;
    }

    return false;
}
