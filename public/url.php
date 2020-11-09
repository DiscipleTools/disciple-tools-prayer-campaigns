<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/********************************************************
 * BUILD VARs
 ********************************************************/
function dt_prayer_root(){
    return 'prayer';
}

function dt_prayer_registered_types(){
    return apply_filters( 'dt_prayer_register_type', $types = [] );
}

add_filter( 'dt_prayer_register_type', function( array $types ) : array {
    $types['saturation'] = [
        'type' => 'saturation',
        'actions' => [
            'edit' => 'edit',
            'instructions' => 'instructions',
            'list' => 'list',
        ]
    ];
    return $types;
});

add_filter( 'dt_prayer_register_type', function( array $types ) : array {
    $types['twentyfourseven'] = [
        'type' => 'twentyfourseven',
        'actions' => [
            'edit' => 'edit',
            'instructions' => 'instructions',
            'list' => 'list',
        ]
    ];
    return $types;
});

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
    foreach( $types as $type){
        $url[] = $root . '/'. $type ;
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
    $parts = explode('/', $url_path );
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
            $post_id = dt_prayer_post_id( $parts[2] );
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
function dt_prayer_post_id( $public_key ){
    global $wpdb;
    $result = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'contact_public_key' AND meta_value = %s", $public_key ) );
    if ( ! empty( $result ) && ! is_wp_error( $result ) ){
        return $result;
    }
    return false;
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
add_filter( 'dt_blank_access', 'dt_prayer_has_access');
function dt_prayer_has_access() : bool {
    $parts = dt_prayer_parse_url_parts();

    // test 1 : correct url root and type
    if ( $parts ){ // parts returns false
        return true;
    }

    return false;
}
add_action( 'wp_print_scripts', function(){
    // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
    $allowed_js = [
        'jquery',
        'jquery-ui',
        'site-js',
        'mapbox-gl',
        'mapbox-cookie',
        'jquery-cookie',
        'datepicker',
    ];

    global $wp_scripts;
    if ( isset( $wp_scripts ) ){
        foreach( $wp_scripts->queue as $key => $item ){
            if ( ! in_array( $item, $allowed_js ) ){
                unset( $wp_scripts->queue[$key] );
            }
        }
    }

}, 1500 );
add_action( 'wp_print_styles', function(){
    // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
    $allowed_css = [
        'foundation-css',
        'jquery-ui-site-css',
        'site-css',
        'datepicker-css'
    ];

    global $wp_styles;
    if ( isset( $wp_styles ) ) {
        foreach ($wp_styles->queue as $key => $item) {
            if (!in_array($item, $allowed_css)) {
                unset($wp_styles->queue[$key]);
            }
        }
    }
}, 1500 );
add_action('dt_blank_head', function(){
    if ( ! dt_prayer_has_access() ){
        return;
    }
    wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
    ?>
    <style>
        body {
            background-color: inherit;
        }
    </style>
    <?php
});


/********************************************************
 * FORM
 ********************************************************/
// TITLE
add_action('dt_blank_title', function(){
    $parts = dt_prayer_is_form_url( 'saturation' );
    if ( ! $parts ){
        return;
    }
    ?>
    Blank Form
    <?php
});
// BODY
add_action('dt_blank_body', function(){
    $parts = dt_prayer_is_form_url( 'saturation' );
    if ( ! $parts ){
        return;
    }

    // FORM BODY
    ?>
    <div style="max-width:1200px;margin:1em auto;">
        <div class="grid-x grid-padding-x">
            <div class="cell">
                <h2>Form</h2>
                <input type="text" placeholder="Name" name="title" id="title" /><br>
                <button id="new_contact_submit" type="button" class="button small">Submit</button>
            </div>
        </div>
        <hr>
        <div class="grid-x grid-padding-x">
            <div class="cell" id="link">
                <a href="/prayer/saturation/e49d970b1cadef1523313e384572ae454293264923b93d307777e389820cae8f">Sample Contact</a>
            </div>
        </div>
    </div> <!-- wrapper -->
    <script>
        jQuery(document).ready(function(){
            jQuery('#new_contact_submit').on('click', function(){
                let title = jQuery('#title').val()
                console.log(title)

                jQuery.ajax({
                    type: "POST",
                    data: JSON.stringify({
                        title: title
                    }),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: `<?php echo esc_url_raw(rest_url()) ?>dt-public/v1/prayer/create`,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest') ?>');
                    }
                })
                    .done(function(data){
                        if ( ! data ) {
                            return
                        }
                        window.location.href = '<?php echo esc_url_raw(site_url()) ?>/prayer/saturation/' + data
                    })

            })
        })
    </script>
    <?php
});
// FOOTER
add_action('dt_blank_footer', function(){
    $parts = dt_prayer_is_form_url( 'saturation' );
    if ( ! $parts ){
        return;
    }
    ?>
    <script>console.log('dt_blank_footer for form')</script>
    <?php
});





/********************************************************
 * CONTACT PAGE
 ********************************************************/
// TITLE
add_action('dt_blank_title', function(){
    $parts = dt_prayer_is_contact_url('saturation');
    if ( ! $parts ){
        return;
    }
    ?>
    Blank Form
    <?php
});
// BODY
add_action('dt_blank_body', function(){
    $parts = dt_prayer_is_contact_url('saturation');
    if ( ! $parts ){
        return;
    }

    // catch contact response
    $actions = dt_prayer_actions( $parts['type'] );
    ?>
    <div style="max-width:1200px;margin:1em auto;">
        <div class="cell center">
            <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' ?>">Form</a>
            | <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ?>">Home</a>
            <?php foreach( $actions as $action ) { ?>
                | <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $action  ?>"><?php echo ucfirst( $action ) ?></a>
            <?php } ?>
        </div>
        <hr>
        <div class="cell">
            <h2><?php echo empty( $parts['action'] ) ? 'Home' : ucfirst( $parts['action'] ) ?></h2>

            <div class="grid-x grid-padding-x">
                <div class="medium-6 cell">
                    Title : <?php echo get_the_title( $parts['post_id'] ) ?>
                </div>
                <div class="medium-6 cell">
                    Test
                </div>
            </div>
        </div>
    </div>
    <?php
});
// FOOTER
add_action('dt_blank_footer', function(){
    $parts = dt_prayer_is_contact_url('saturation');
    if ( ! $parts ){
        return;
    }
    ?>
   <script>console.log('dt_blank_footer for contact')</script>
    <?php
});

