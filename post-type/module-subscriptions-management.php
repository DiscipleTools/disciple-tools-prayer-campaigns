<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Subscriptions_Management extends DT_Module_Base
{
    public $module = "subscriptions_management";
    public $post_type = 'subscriptions';

    public $magic = false;
    public $parts = false;
    public $root = "subscriptions_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'subscriptions'; // define the type


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
        // register tiles if on details page
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 5, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'tile_scripts' ], 100 );

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
        if ( $this->magic->is_valid_key_url( $this->type ) && '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'manage_body' ] );
        } else {
            // fail if no valid action url found
            add_action( 'dt_blank_body', [ $this, 'register_body' ] );
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

    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === 'subscriptions' ){
            $tiles["subscriptions"] = [ "label" => __( "Subscriptions", 'disciple-tools-subscriptions' ) ];
        }
        return $tiles;
    }


    public function dt_details_additional_section( $section, $post_type ) {
        // test if subscriptions post type and subscriptions_app_module enabled
        if ( $post_type === $this->post_type ) {

            if ( 'subscriptions' === $section ) {
                $record = DT_Posts::get_post( $post_type, get_the_ID() );

                if ( isset( $record['public_key'])) {
                    $key = $record['public_key'];
                } else {
                    $key = DT_Subscriptions_Base::instance()->create_unique_key();
                    update_post_meta( get_the_ID(), 'public_key', $key );
                }
                $link = trailingslashit( site_url() ) . $this->root . '/' . $this->type . '/' . $key;
                ?>
                <div class="cell">
                    <div class="section-subheader">
                        Subscriptions Management
                    </div>
                    <a class="button hollow small" onclick="copyToClipboard('<?php echo esc_url( $link ) ?>')">Copy Link</a>
                    <a class="button hollow small" href="<?php echo esc_url( $link ) ?>" target="_blank">Go To</a>
                </div>

                <script>
                    const copyToClipboard = str => {
                        const el = document.createElement('textarea');
                        el.value = str;
                        el.setAttribute('readonly', '');
                        el.style.position = 'absolute';
                        el.style.left = '-9999px';
                        document.body.appendChild(el);
                        const selected =
                            document.getSelection().rangeCount > 0
                                ? document.getSelection().getRangeAt(0)
                                : false;
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        if (selected) {
                            document.getSelection().removeAllRanges();
                            document.getSelection().addRange(selected);
                        }
                        alert('Copied')
                    };
                </script>
                <?php
            }


        } // end if subscriptions and enabled
    }


    public function tile_scripts(){
        if ( is_singular( "subscriptions" ) ){
            $magic = new DT_Magic_URL( 'subscriptions_app' );
            $types = $magic->list_types();
            $subscriptions = $types['subscriptions'] ?? [];
            $subscriptions['new_key'] = $magic->create_unique_key();

            wp_localize_script( // add object to subscriptions-post-type.js
                'dt_subscriptions', 'subscriptions_subscriptions_module', [
                    'subscriptions' => $subscriptions,
                ]
            );
        }
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
        dt_write_log($wp_scripts->queue);
        dt_write_log($wp_scripts);
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

    public function subscriptions_styles_header(){
        ?>
        <style>
            #title {
                font-size:1.7rem;
                font-weight: 100;
            }
            #top-bar {
                position:relative;
                padding-bottom:1em;
            }
            #add-new {
                padding-top:1em;
            }
            #top-loader {
                position:absolute;
                right:5px;
                top: 5px;
            }
            #wrapper {
                max-width:500px;
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
            .stat-heading {
                font-size: 2rem;
            }
            .stat-number {
                font-size: 3.5rem;
            }
            .stat-year {
                font-size: 2rem;
                color: darkgrey;
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
            .select-input {
                border-top: 0;
                border-left: 0;
                border-right: 0;
                border-bottom: 1px solid gray;
                box-shadow: none;
                background: white;
                text-align:center;
            }
            select::-ms-expand {
                display: none;
            }
            .input-group-field {
                border-top: 0;
                border-left: 0;
                border-right: 0;
                padding:0;
                border-bottom: 1px solid gray;
                box-shadow: none;
                background: white;
            }
            .title-year {
                font-size:3em;
                font-weight: 100;
                color: #0a0a0a;
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
        ?>
        <script>
            var postSubscriptions = [<?php echo json_encode([
                'map_key' => DT_Mapbox_API::get_key(),
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'name' => get_the_title( $this->parts['post_id']),
                'translations' => [
                    'add' => __( 'Add Report', 'disciple-tools-subscriptions' ),
                    'search_location' => 'Search for Location'
                ],
            ]) ?>][0]

            jQuery(document).ready(function($){
                clearInterval(window.fiveMinuteTimer)


                /* LOAD */
                window.spinner = $('#spinner-section')
                let spinner = $('.loading-spinner')
                let title = $('#title')
                let content = $('#content')

                /* set title */
                title.html( _.escape( postSubscriptions.name ) )

                /* FUNCTIONS */


                window.create_user = ( name, email ) => {
                    $.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'create_user', parts: postSubscriptions.parts, name: name, email: email }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postSubscriptions.root + postSubscriptions.parts.root + '/v1/' + postSubscriptions.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postSubscriptions.nonce )
                        }
                    })
                        .done(function(data){
                            console.log(data)
                            window.location = window.location.href + data
                            jQuery('#spinner-section').hide()
                        })
                        .fail(function(e) {
                            console.log(e)
                            $('#error').html(e)
                        })
                }

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
                    window.current_subscriptions = data

                    content.empty()
                    content.append(`
                                 <div><h3>Subscriptions</h3></div>
                                 <table class="hover"><tbody id="subscriptions-list"></tbody></table>
                             `)
                    let list = $('#subscriptions-list')

                    if ( data.subscriptions.length > 0 ) {
                        $.each(data.subscriptions, function(i,v){
                            list.append(`
                                <tr><td>${_.escape( v.label )}</td><td style="vertical-align: middle;"><button type="button" class="button small alert delete-subscriptions" data-id="${_.escape( v.grid_meta_id )}" style="margin: 0;float:right;">&times;</button></td></tr>
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
                        data: JSON.stringify({ action: 'delete', parts: postSubscriptions.parts, subscriptions_id: id }),
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

                window.insert_subscriptions = () => {
                    spinner.addClass('active')

                    let year = $('#year').val()
                    let value = $('#value').val()
                    let type = $('#type').val()

                    let subscriptions = {
                        action: 'insert',
                        parts: postSubscriptions.parts,
                        type: type,
                        subtype: type,
                        value: value,
                        time_end: year
                    }

                    if ( typeof window.selected_location_grid_meta !== 'undefined' && ( typeof window.selected_location_grid_meta.location_grid_meta !== 'undefined' || window.selected_location_grid_meta.location_grid_meta !== '' ) ) {
                        subscriptions.location_grid_meta = window.selected_location_grid_meta.location_grid_meta
                    }
                    else if ( $('#new_contact_address').val() ) {
                        subscriptions.address = $('#new_contact_address').val()
                    }

                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(subscriptions),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postSubscriptions.root + postSubscriptions.parts.root + '/v1/' + postSubscriptions.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postSubscriptions.nonce )
                        }
                    })
                        .done(function(data){
                            window.load_subscriptions( data )
                        })
                        .fail(function(e) {
                            console.log(e)
                            jQuery('#error').html(e)
                        })
                }

                window.add_new_listener = () => {
                    $('#add-subscriptions-button').on('click', function(e){
                        $('#add-subscriptions-button').hide()
                        $('#add-form-wrapper').empty().append(`
                            <div class="grid-x grid-x-padding" id="new-subscriptions-form">
                                <div class="cell center">
                                    There are <input type="number" id="value" class="number-input" placeholder="#" value="1" />&nbsp;
                                    total&nbsp;
                                    <select id="type" class="select-input">
                                        <option value="groups">groups</option>
                                        <option value="baptisms">baptisms</option>
                                    </select>
                                    in
                                </div>
                                <div class="cell">
                                    <div id="mapbox-wrapper">
                                        <div id="mapbox-autocomplete" class="mapbox-autocomplete input-group" data-autosubmit="false" data-add-address="true">
                                            <input id="mapbox-search" type="text" name="mapbox_search" class="input-group-field" autocomplete="off" placeholder="Search Location" />
                                            <div class="input-group-button">
                                                <button id="mapbox-spinner-button" class="button hollow" style="display:none;border-color:lightgrey;">
                                                    <span class="" style="border-radius: 50%;width: 24px;height: 24px;border: 0.25rem solid lightgrey;border-top-color: black;animation: spin 1s infinite linear;display: inline-block;"></span>
                                                </button>
                                                <button id="mapbox-clear-autocomplete" class="button alert input-height delete-button-style mapbox-delete-button" type="button" title="Delete Location" style="display:none;">&times;</button>
                                            </div>
                                            <div id="mapbox-autocomplete-list" class="mapbox-autocomplete-items"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="cell center">at the end of&nbsp;
                                    <select id="year" class="select-input">
                                    </select>
                                </div>
                                <div class="cell center" >
                                    <button class="button large save-subscriptions" type="button" id="save_new_subscriptions" disabled="disabled">Save</button>
                                    <button class="button large save-subscriptions" type="button" id="save_and_add_new_subscriptions" disabled="disabled">Save and Add</button>
                                    <button class="button large alert" type="button" id="cancel_new_subscriptions">&times;</button>
                                </div>
                            </div>
                        `)

                        window.write_input_widget()

                        // $('.number-input').focus(function(e){
                        //     window.currentEvent = e
                        //     if ( e.currentTarget.value === '1' ){
                        //         e.currentTarget.value = ''
                        //     }
                        // })

                        $('#save_new_subscriptions').on('click', function(){
                            window.insert_subscriptions()
                            $('#add-form-wrapper').empty()
                            $('#add-subscriptions-button').show()
                        })

                        $('#save_and_add_new_subscriptions').on('click', function(){
                            window.insert_subscriptions()
                            $('#add-form-wrapper').empty()
                            $('#add-subscriptions-button').click()
                            $('#value').focus()
                        })

                        $('#cancel_new_subscriptions').on('click', function(){
                            window.load_subscriptions()
                            $('#add-form-wrapper').empty()
                            $('#add-subscriptions-button').show()
                        })

                        $('#mapbox-search').on('change', function(e){
                            if ( typeof window.selected_location_grid_meta !== 'undefined' || window.selected_location_grid_meta !== '' ) {
                                $('.save-subscriptions').removeAttr('disabled')
                            }
                        })
                    })
                }

            })
        </script>
        <?php
    }

    public function register_body(){
        // FORM BODY
        ?>
        <style>
            #email {
                display:none;
            }
        </style>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="grid-x" id="add-new">
                <div class="cell center">
                    <h2>Subscribe to Pray</h2>
                </div>
            </div>
            <hr>

            <form data-abide>
            <div class="grid-x">
                <div class="cell">
                    <span id="name-error" class="form-error">
                        You're name is required.
                    </span>
                    <label for="name">Name<br>
                        <input type="text" name="name" id="name" placeholder="Name" required/>
                    </label>

                </div>
                <div class="cell">
                    <span id="email-error" class="form-error">
                        You're email is required.
                    </span>
                    <label for="email">Email<br>
                        <input type="email" name="email" id="email" placeholder="Email" />
                        <input type="email" name="e2" id="e2" placeholder="Email" required />
                    </label>
                </div>
                <div class="cell">
                    <button type="button" class="button expanded" value="submit" id="next_1">Next</button>
                </div>
            </div>
            </form>

            <div class="grid-x" id="spinner-section" style="display:none; height: inherit !important;">
                <div class="cell"><hr></div>
                <div class="cell center" id="bottom-spinner"><span class="loading-spinner active"></span></div>
                <div class="cell" id="content"><div class="center">... loading</div></div>
                <div class="cell grid" id="error"></div>
            </div>
        </div> <!-- form wrapper -->
        <script>
            jQuery(document).ready(function($){
                jQuery('#next_1').on( 'click', function(e){
                    window.spinner.show()

                    let honey = jQuery('#email').val()
                    if ( honey ) {
                        jQuery('#next_1').html('Shame, shame, shame. We know your name ... ROBOT!').prop('disabled', true )
                        window.spinner.hide()
                        return;
                    }

                    let name_input = jQuery('#name')
                    let name = name_input.val()
                    if ( ! name ) {
                        jQuery('#name-error').show()
                        window.spinner.hide()
                        name_input.focus(function(){
                            jQuery('#name-error').hide()
                        })
                        return;
                    }

                    let email_input = jQuery('#e2')
                    let email = email_input.val()
                    if ( ! email ) {
                        jQuery('#email-error').show()
                        window.spinner.hide()
                        email_input.focus(function(){
                            jQuery('#email-error').hide()
                        })
                        return;
                    }

                    window.create_user( name, email )
                })
            })
        </script>
        <?php
    }

    public function manage_body(){
//        $actions = $this->magic->list_actions( $this->type );

        // FORM BODY
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
            <div class="grid-x" id="add-new">
                <div class="cell center"><button type="button" id="add-subscriptions-button" class="button large" style="min-width:200px;">Add Subscriptions</button></div>
                <div id="add-form-wrapper"></div>
            </div>

        </div> <!-- form wrapper -->
        <script>
            jQuery(document).ready(function($){
                window.get_subscriptions()
                window.add_new_listener()
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

        // create
        if ( 'create_user' === $action ) {
            return $this->create_user( $params );
        }

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
                return $this->delete_subscriptions( $params, $post_id );
            case 'insert':
//                return $this->insert_subscriptions( $params, $post_id );
            default:
                return new WP_Error( __METHOD__, "Missing valid action", [ 'status' => 400 ] );
        }
    }

    public function create_user( $params ) {

        if ( ! isset( $params['email'] ) || empty( $params['email'] ) ) {
            return new WP_Error( __METHOD__, "Missing params", [ 'status' => 400 ] );
        }

        $user = wp_get_current_user();
        $user->add_cap( 'create_subscriptions' );

        $params = dt_recursive_sanitize_array( $params );
        $hash = $this->magic->create_unique_key();

        $fields = [
            'title' => sanitize_text_field( wp_unslash( $params['name'] ) ),
            "contact_email" => [
                ["value" => sanitize_text_field( wp_unslash( $params['email'] ) ) ], //create
            ],
            $this->root . '_' . $this->type . '_public_key' => $hash,
            $this->root . '_' . $this->type . '_last_modified' => time(),
        ];

        $new_id = DT_Posts::create_post( 'subscriptions', $fields, true );

        if ( is_wp_error( $new_id ) ) {
            return $new_id;
        }
        if ( isset( $new_id['ID'] ) ){
            return $hash;
        }
        else {
            return new WP_Error( __METHOD__, "Failed to create subscriptions.", [ 'status' => 400, 'error' ] );
        }

    }

    public function get_subscriptions( $post_id ) {
        $user = wp_get_current_user();
        $user->add_cap( 'create_subscriptions' );

        $record = DT_Posts::get_post( $this->post_type, $post_id, false, false );
        $data = [
            'subscriptions' => [],
            'type' => ''
        ];

        if ( isset( $record['location_grid_meta'] ) ) {
            $data['subscriptions'] = $record['location_grid_meta'];
            $data['type'] = 'lgm';
        }
        else if ( isset( $record['location_grid'] ) ) {
            $data['subscriptions'] = $record['location_grid'];
            $data['type'] = 'lg';
        }

        return $data;

    }

    public function delete_subscriptions( $params, $post_id ) {

        Location_Grid_Meta::delete_location_grid_meta( $post_id, 'grid_meta_id', $params['subscriptions_id'] );


        return $this->get_subscriptions( $post_id );
    }
}
