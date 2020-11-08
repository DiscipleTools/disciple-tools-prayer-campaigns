<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Establishes the base url rewrite
 * @return string
 */
function get_dt_prayer_url_base_token(){
    return 'prayer/subscription';
}
function get_dt_prayer_url_parts(){
    $url_path = dt_get_url_path();
    $url_base_token = get_dt_prayer_url_base_token();
    if ( strpos( $url_path, $url_base_token ) !== false || strpos( $url_path, $url_base_token ) !== false ) {
        $parts = explode('/', $url_path );
        if ( ! empty( $parts ) ) {
            $parts = array_map( 'sanitize_key', wp_unslash( $parts ) );
            $elements = [
                'public_key' => '',
                'action' => ''
            ];
            if ( isset( $parts[2] ) && ! empty( $parts[2] ) ){
                $elements['public_key'] = $parts[2];
            }
            if ( isset( $parts[3] ) && ! empty( $parts[3] ) ){
                $elements['action'] = $parts[3];
            }
            return $elements;
        }
    }
    return false;
}
/**
 * Gets the prayer key from the URL if exists
 * @return false|string
 */
function get_dt_prayer_public_key(){
    $parts = get_dt_prayer_url_parts();
    if ( isset( $parts['public_key'] ) && ! empty( $parts['public_key'] ) ) {
        return $parts['public_key'];
    }
    return false;
}

/**
 * @return false|string
 */
function get_dt_prayer_action(){
    $parts = get_dt_prayer_url_parts();
    if ( isset( $parts['action'] ) && ! empty( $parts['action'] ) ) {
        return (string) $parts['action'];
    }
    return false;
}

function get_dt_prayer_post_id( $public_key ){
    global $wpdb;
    $result = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'contact_public_key' AND meta_value = %s", $public_key ) );
    if ( ! empty( $result ) && ! is_wp_error( $result ) ){
        return $result;
    }
    return false;
}

/**
 * Catch and register url with key and load blank template
 */
add_filter( 'dt_templates_for_urls', 'dt_prayer_add_url', 199 );
function dt_prayer_add_url( $template_for_url) {
    $key = get_dt_prayer_public_key();
    if ( ! empty( $key ) ){
        $url_base_token = get_dt_prayer_url_base_token();
        $template_for_url[$url_base_token . '/'.$key ] = 'template-blank.php';
    }
    return $template_for_url;
}

/**
 *
 */
add_filter( 'dt_blank_access', function() : bool {
    dt_write_log(get_dt_prayer_public_key());
    dt_write_log(get_dt_prayer_post_id( get_dt_prayer_public_key() ));
    $found_post_id = get_dt_prayer_post_id( get_dt_prayer_public_key() );
    if ( ! empty( $found_post_id ) ){
        return true;
    }
    return false;
});

add_action('dt_blank_title', function(){

    ?>
    <script>console.log('here i am')</script>
    <?php
});

add_action('dt_blank_head', function(){

    ?>
    <script>console.log('here i am')</script>
    <?php
});

add_action('dt_blank_body', function(){
    $link = site_url() . '/'. get_dt_prayer_url_base_token() . '/' . hash('sha256', 'thissite');
    ?>
    <a href="<?php echo $link ?>"><?php echo $link ?></a>
    <?php
});

add_action('dt_blank_footer', function(){
    ?>
   <script>console.log('here i am in the footer')</script>
    <?php
});







// @link https://www.pmg.com/blog/a-mostly-complete-guide-to-the-wordpress-rewrite-api/
//add_action( 'init', 'dt_prayer_add_rewrite_rules' );
//function dt_prayer_add_rewrite_rules()
//{
//    add_rewrite_rule(
//        '^prayer/subscription/(d+)/?$', // p followed by a slash, a series of one or more digits and maybe another slash
//        'template-blank.php?prayer_subscription=$matches[1]',
//        'top'
//    );
//
//}

//add_filter( 'query_vars', 'dt_prayer_rewrite_add_var' );
//function dt_prayer_rewrite_add_var( $vars )
//{
//    $vars[] = 'prayer_subscription';
//    dt_write_log($vars);
//    return $vars;
//}

//add_action('template_redirect', 'dt_prayer_do_something' );
//function dt_prayer_do_something(){
//     dt_write_log(__METHOD__);
//    if( get_query_var( 'prayer_subscription' ) )
//    {
//        dt_write_log(get_query_var( 'prayer_subscription' ));
//    }
//}
//register_activation_hook(__FILE__, 'dt_prayer_rewrite_activation');
//function dt_prayer_rewrite_activation()
//{
////    dt_prayer_add_rewrite_rules();
//    flush_rewrite_rules();
//}
