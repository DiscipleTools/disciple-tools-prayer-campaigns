<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Subscriptions_Management extends DT_Module_Base
{
    public $module = "subscriptions_management";
    public $post_type = 'subscriptions';

    public $magic = false;
    public $parts = false;
    public $root = "subscriptions_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'manage'; // define the type


    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();
        if ( !self::check_enabled_and_prerequisites() ){
            return;
        }

        // register type
        $this->magic = new DT_Magic_URL( $this->root );
        add_filter( 'dt_magic_url_register_types', [ $this, 'register_type' ], 10, 1 );

        // register REST and REST access
        add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

        // fail if not valid url
        $this->parts = $this->magic->parse_url_parts();
        if ( ! $this->parts ){
            return;
        }

        // fail if does not match type
        if ( $this->type !== $this->parts['type'] ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        add_action( 'dt_blank_footer', [ $this, 'form_footer' ] );
        if ( $this->magic->is_valid_key_url( $this->type ) && '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'manage_body' ] );
        } else {
            // fail if no valid action url found
            return;
        }


        // load page elements
        add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 );
        add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 );

        // register url and access
        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ], 199, 1 );
        add_filter( 'dt_blank_access', [ $this, '_has_access' ] );
        add_filter( 'dt_allow_non_login_access', function(){ return true;
        }, 100, 1 );
    }


    public function register_type( array $types ) : array {
        if ( ! isset( $types[$this->root] ) ) {
            $types[$this->root] = [];
        }
        $types[$this->root][$this->type] = [
            'name' => 'Subscriptions',
            'root' => $this->root,
            'type' => $this->type,
            'meta_key' => 'public_key', // coaching-magic_c_key
            'actions' => [
                '' => 'Manage',
            ],
            'post_type' => $this->post_type,
        ];
        return $types;
    }

    public function register_url( $template_for_url ){
        $parts = $this->parts;

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
        $actions = $this->magic->list_actions( $parts['type'] );
        if ( isset( $actions[ $parts['action'] ] ) ){
            $template_for_url[ $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $parts['action'] ] = 'template-blank.php';
        }

        return $template_for_url;
    }

    public function _has_access() : bool {
        $parts = $this->parts;

        // test 1 : correct url root and type
        if ( $parts ){ // parts returns false
            return true;
        }

        return false;
    }

    public function print_scripts(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_js = [
            'jquery',
            'lodash',
            'moment',
            'datepicker',
            'site-js',
            'shared-functions',
            'mapbox-gl',
            'mapbox-cookie',
            'mapbox-search-widget',
            'google-search-widget',
            'jquery-cookie',
        ];

        global $wp_scripts;

        if ( isset( $wp_scripts ) ){
            foreach ( $wp_scripts->queue as $key => $item ){
                if ( ! in_array( $item, $allowed_js ) ){
                    unset( $wp_scripts->queue[$key] );
                }
            }
        }
        unset( $wp_scripts->registered['mapbox-search-widget']->extra['group'] );
    }

    public function print_styles(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_css = [
            'foundation-css',
            'jquery-ui-site-css',
            'site-css',
            'datepicker-css',
            'mapbox-gl-css'
        ];

        global $wp_styles;
        if ( isset( $wp_styles ) ) {
            foreach ($wp_styles->queue as $key => $item) {
                if ( !in_array( $item, $allowed_css )) {
                    unset( $wp_styles->queue[$key] );
                }
            }
        }
    }

    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        $this->subscriptions_styles_header();
        $this->subscriptions_javascript_header();
    }
    public function form_footer(){
        wp_footer(); // styles controlled by wp_print_styles and wp_print_scripts actions
    }

    public function subscriptions_styles_header(){
        ?>
        <style>
            body {
                background-color: white;
            }
            #content {
                max-width:100%;
            }
            #title {
                font-size:1.7rem;
                font-weight: 100;
            }
            #add-new {
                padding-top:1em;
            }
            #wrapper {
                max-width:1000px;
                margin:0 auto;
                padding: .5em;
                background-color: white;
            }
            #value {
                width:50px;
                display:inline;
            }
            #type {
                width:75px;
                padding:5px 10px;
                display:inline;
            }
            #mapbox-search {
                padding:5px 10px;
                border-bottom-color: rgb(138, 138, 138);
            }
            #year {
                width:75px;
                display:inline;
            }
            #new-subscriptions-form {
                padding: 1em .5em;
                background-color: #f4f4f4;;
                border: 1px solid #3f729b;
                font-weight: bold;
            }
            .number-input {
                border-top: 0;
                border-left: 0;
                border-right: 0;
                border-bottom: 1px solid gray;
                box-shadow: none;
                background: white;
                text-align:center;
            }
            /* Chrome, Safari, Edge, Opera */
            input::-webkit-outer-spin-button,
            input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
            /* Firefox */
            input[type=number] {
                -moz-appearance: textfield;
            }
            select::-ms-expand {
                display: none;
            }

            /* size specific style section */
            @media screen and (max-width: 991px) {
                /* start of large tablet styles */

            }
            @media screen and (max-width: 767px) {
                /* start of medium tablet styles */

            }
            @media screen and (max-width: 479px) {
                /* start of phone styles */
                body {
                    background-color: white;
                }
            }
        </style>
        <?php
    }

    public function subscriptions_javascript_header(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        ?>
        <script>
            var postSubscriptions = [<?php echo json_encode([
                'map_key' => DT_Mapbox_API::get_key(),
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'post' => $post,
                'name' => get_the_title( $this->parts['post_id'] ),
                'translations' => [
                    'add' => __( 'Add Report', 'disciple-tools-subscriptions' ),
                    'search_location' => 'Search for Location'
                ],
            ]) ?>][0]

            jQuery(document).ready(function($){
                clearInterval(window.fiveMinuteTimer)

                /* LOAD */
                let spinner = $('.loading-spinner')
                let title = $('#title')
                let content = $('#content')

                /* set title */
                title.html( window.lodash.escape( postSubscriptions.post.title ) )

                /* FUNCTIONS */
                window.get_subscriptions = () => {
                    $.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'get', parts: postSubscriptions.parts }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postSubscriptions.root + postSubscriptions.parts.root + '/v1/' + postSubscriptions.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postSubscriptions.nonce )
                        }
                    })
                        .done(function(data){
                            window.load_subscriptions( data )
                            spinner.removeClass('active')
                        })
                        .fail(function(e) {
                            console.log(e)
                            $('#error').html(e)
                        })
                }

                window.load_subscriptions = ( data ) => {
                    spinner.addClass('active')

                    window.current_subscriptions = data

                    content.empty()
                    content.append(`<div><h3>Subscriptions</h3></div>
                                 <table class="hover"><tbody id="subscriptions-list"></tbody></table>`)

                    let list = $('#subscriptions-list')

                    if ( data.length > 0 ) {
                        $.each(data, function(i,v){
                            let verified = ''
                            if ( v.verified ) {
                                verified = ' <span style="color:green; font-weight:bold;"><i class="fi-check"></i> Verified!</span>'
                            }
                            list.append(`
                                <tr><td>${window.lodash.escape( v.formatted_time )} ${verified}</td><td style="vertical-align: middle;"><button type="button" class="button small alert delete-subscriptions" data-id="${window.lodash.escape( v.id )}" style="margin: 0;float:right;">&times;</button></td></tr>
                            `)
                        })

                        $('.delete-subscriptions').on('click', function(e){
                            let id = $(this).data('id')
                            $(this).attr('disabled', 'disabled')
                            window.delete_subscriptions( id )
                        })
                    } else {
                        list.append(`
                        <tr><td>No subscriptions found.</td></tr>
                        `)
                    }


                    spinner.removeClass('active')
                }

                window.delete_subscriptions = ( id ) => {
                    spinner.addClass('active')

                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'delete', parts: postSubscriptions.parts, report_id: id }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postSubscriptions.root + postSubscriptions.parts.root + '/v1/' + postSubscriptions.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postSubscriptions.nonce )
                        }
                    })
                        .done(function(data){
                            window.load_subscriptions( data )
                            spinner.removeClass('active')
                        })
                        .fail(function(e) {
                            console.log(e)
                            jQuery('#error').html(e)
                        })
                }
            })
        </script>
        <?php
        return true;
    }

    public function manage_body(){
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title"></h2>
                </div>
            </div>
            <hr>
            <div class="grid-x" style=" height: inherit !important;">
                <div class="cell center" id="bottom-spinner"><span class="loading-spinner active"></span></div>
                <div class="cell" id="content"><div class="center">... loading</div></div>
                <div class="cell grid" id="error"></div>
            </div>
        </div> <!-- form wrapper -->
        <script>
            jQuery(document).ready(function($){
                window.get_subscriptions()
            })
        </script>
        <?php
    }

    /**
     * Open default restrictions for access to registered endpoints
     * @param $authorized
     * @return bool
     */
    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->root . '/v1/'.$this->type ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_api_routes() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type, [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'endpoint' ],
                ],
            ]
        );
    }

    public function endpoint( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        // manage
        $magic = $this->magic;
        $post_id = $magic->get_post_id( $params['parts']['meta_key'], $params['parts']['public_key'] );

        if ( ! $post_id ){
            return new WP_Error( __METHOD__, "Missing post record", [ 'status' => 400 ] );
        }

        switch ( $action ) {
            case 'get':
                return $this->get_subscriptions( $post_id );
            case 'delete':
                return $this->delete_subscriptions( $params );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function get_subscriptions( $post_id ) {
        $subs = Disciple_Tools_Reports::get( $post_id, 'post_id' );

        if ( ! empty( $subs ) ){
            foreach ( $subs as $index => $sub ) {
                // verification step
                if ( $sub['value'] < 1 ) {
                    Disciple_Tools_Reports::update( [
                        'id' => $sub['id'],
                        'value' => 1
                    ] );
                    $subs[$index]['value'] = 1;
                    $subs[$index]['verified'] = true;
                }

                $subs[$index]['formatted_time'] = gmdate( 'F d, Y @ H:i a', $sub['time_begin'] ) . ' for ' . $sub['label'];
            }
        }

        return $subs;
    }

    public function delete_subscriptions( $params ) {
        Disciple_Tools_Reports::delete( $params['report_id'] );
        return $this->get_subscriptions( $params['parts']['post_id'] );
    }
}
