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
        add_filter( "dt_blank_title", [ $this, "page_tab_title" ] );


        // load page elements
        add_action( 'wp_print_scripts', [ $this, 'print_scripts' ], 1500 );
        add_action( 'wp_print_styles', [ $this, 'print_styles' ], 1500 );

        // register url and access
        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ], 199, 1 );
        add_filter( 'dt_blank_access', [ $this, '_has_access' ] );
        add_filter( 'dt_allow_non_login_access', function(){ return true;
        }, 100, 1 );
    }

    public function page_tab_title( $title ){
        return __( "My Prayer Times", 'disciple_tools' );
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
                min-height: 300px;
                margin-bottom: 200px;
            }
            #title {
                font-size:1.7rem;
                font-weight: 100;
            }
            #wrapper {
                max-width:1000px;
                margin:0 auto;
                padding: .5em;
                background-color: white;
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
            .day-cell {
                max-width: 25%;
                float:left;
                text-align: center;
                border: 1px solid grey;
                font-size:1.2em;
                padding: 10px 5px 5px 5px;
            }
            .day-cell:hover {
                background: lightblue;
                border: 1px solid darkslategrey;
                cursor:pointer;
            }
            .progress-bar {
                height:10px;
            }
            .selected-hour {
                max-width: 100%;
                padding-top: 10px;
                border: 1px solid grey;
                font-size:1.2em;
            }
            .no-selection {
                max-width: 100%;
                padding-top: 10px;
                border: 1px solid grey;
                font-size:1.2em;
            }
            .remove-selection {
                float: right;
                color: red;
                cursor:pointer;
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
            let postSubscriptions = [<?php echo json_encode([
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
                    data.sort((a,b) => {
                        return parseInt(a.time_begin) - parseInt(b.time_begin)
                    })

                    window.current_subscriptions = data

                    let list = $('#subscriptions-list')
                    list.empty();

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
                    $('#loading-message').hide()

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

                window.get_subscriptions()

                jQuery('#submit-form').on('click', function(){

                    let spinner = $('.loading-spinner')
                    spinner.addClass('active')
                    let submit_button = jQuery('#submit-form')
                    submit_button.prop('disabled', true)

                    let selected_times_divs = jQuery(`.selected-hour`)
                    let selected_times = []
                    if ( selected_times_divs.length === 0 ) {
                        jQuery('#selection-error').show()
                        spinner.hide()
                        jQuery('#selection-grid-wrapper').click(function(){
                            jQuery('#selection-error').hide()
                        })
                        submit_button.prop('disabled', false)
                        return;
                    } else {
                        jQuery.each(selected_times_divs, function(i,v){
                            selected_times.push({ time: jQuery(this).data('time'), grid_id: jQuery(this).data('location') } )
                        })
                    }

                    let wrapper = jQuery('#wrapper')
                    wrapper.empty().html(`<div class="grid-x"><div class="cell center"><span class="loading-spinner active"></span></div></div>`)

                    let alert = `
                        <div class="grid-x">
                            <div class="cell center"><h2><?php esc_html_e( 'Saved!', 'disciple_tools' ); ?></h2></div>
                        </div>
                    `
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'add', parts: postSubscriptions.parts, selected_times }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postSubscriptions.root + postSubscriptions.parts.root + '/v1/' + postSubscriptions.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postSubscriptions.nonce )
                        }
                    })
                    .done(function(data){
                        wrapper.empty().html(alert)
                        spinner.removeClass('active')
                    })
                    .fail(function(e) {
                        console.log(e)
                        wrapper.empty().html(`<div class="grid-x"><div class="cell center">
                        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
                        <a href="${window.location.href}">Try Again</a>
                        </div></div>`)
                        $('#error').html(e)
                        spinner.removeClass('active')
                    })
                })

            })
        </script>
        <?php
        return true;
    }

    public function manage_body(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( !isset( $post["campaigns"][0]["ID"] ) ){
            return false;
        }
        $campaign_id = $post["campaigns"][0]["ID"];
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        if ( is_wp_error( $campaign ) ) {
                return $campaign;
        }
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x">
                <div class="cell center">
                    <h2 id="title"><?php echo esc_html( $post["name"] ); ?></h2>
                </div>
            </div>
            <hr>
            <div class="grid-x" style=" height: inherit !important;">
                <div class="cell center" id="bottom-spinner"><span class="loading-spinner active"></span></div>
                <h3><?php esc_html_e( 'My Prayer Times', 'disciple_tools' ); ?></h3>
                <div class="cell" id="content">
                    <table class="hover">
                        <tbody id="subscriptions-list"></tbody>
                    </table>
                    <div id="loading-message" class="center">... <?php esc_html_e( 'loading', 'disciple_tools' ); ?></div>
                </div>
                <div class="cell grid" id="error"></div>
            </div>

            <h3><?php esc_html_e( 'Sign up for more', 'disciple_tools' ); ?></h3>
            <?php
            $grid_id = 1;
            if ( isset( $campaign['location_grid'] ) && ! empty( $campaign['location_grid'] ) ) {
                $grid_id = $campaign['location_grid'][0]['id'];
            }
            $campaign_times = DT_Time_Utilities::campaign_times_list( $campaign_id );
            DT_Campaign_24Hour_Prayer::calendar_subscribe( $campaign_id, $grid_id, $campaign_times );
            ?>

            <br>
            <div class="grid-x grid-padding-x grid-padding-y">
                <div class="cell center">
                    <button class="button large" id="submit-form"><?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple_tools' ); ?></button>
                </div>
            </div>
        </div> <!-- form wrapper -->
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
                return $this->delete_subscriptions( $post_id, $params );
            case 'add':
                return $this->add_subscriptions( $post_id, $params );
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

    private function delete_subscriptions( $post_id, $params ) {
        Disciple_Tools_Reports::delete( $params['report_id'] );
        return $this->get_subscriptions( $post_id );
    }

    private function add_subscriptions( $post_id, $params ){
        $post = DT_Posts::get_post( 'subscriptions', $post_id, true, false );
        if ( !isset( $post["campaigns"][0]["ID"] ) ){
            return false;
        }
        $campaign_id = $post["campaigns"][0]["ID"];


        $campaign_instance = DT_Campaign_24Hour_Prayer::instance();
        foreach ( $params['selected_times'] as $time ){
            $args = [
                'parent_id' => $campaign_id,
                'post_id' => $post_id,
                'post_type' => 'subscriptions',
                'type' => $campaign_instance->root,
                'subtype' => $campaign_instance->type,
                'payload' => null,
                'value' => 0,
                'lng' => null,
                'lat' => null,
                'level' => null,
                'label' => null,
                'grid_id' => $time['grid_id'],
                'time_begin' => $time['time'],
                'time_end' => $time['time'] + 900,
            ];

            $grid_row = Disciple_Tools_Mapping_Queries::get_by_grid_id( $time['grid_id'] );
            if ( ! empty( $grid_row ) ){
                $full_name = Disciple_Tools_Mapping_Queries::get_full_name_by_grid_id( $time['grid_id'] );
                $args['lng'] = $grid_row['longitude'];
                $args['lat'] = $grid_row['latitude'];
                $args['level'] = $grid_row['level_name'];
                $args['label'] = $full_name;
            }
            Disciple_Tools_Reports::insert( $args );
        }
        return $this->get_subscriptions( $params['parts']['post_id'] );
    }
}
