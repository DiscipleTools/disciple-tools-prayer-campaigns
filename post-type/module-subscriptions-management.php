<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Subscriptions_Management extends DT_Module_Base {
    public $module = "subscriptions_management";

    public $magic_link_root = "subscriptions_app"; // define the root of the url {yoursite}/root/type/key/action
    public $magic_link_type = 'manage'; // define the type
    public $post_type = 'subscriptions';

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
        new DT_Prayer_Subscription_Management_Magic_Link();
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_api_routes() {
        $namespace = $this->magic_link_root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->magic_link_type, [
                [
                    'methods'  => "POST",
                    'callback' => [ $this, 'manage_profile' ],
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->magic_link_type . '/delete_profile', [
                [
                    'methods'  => "DELETE",
                    'callback' => [ $this, 'delete_profile' ],
                ],
            ]
        );
    }

    public function verify_magic_link_and_get_post_id( $meta_key, $public_key ){
        $magic = new DT_Magic_URL( $this->magic_link_root );
        $parts = $magic->parse_wp_rest_url_parts( $public_key );
        if ( !$parts || $this->magic_link_type != $parts["type"] ){
            return false;
        }
        if ( isset( $parts["post_id"] ) && !empty( $parts["post_id"] ) ){
            return $parts["post_id"];
        }
        return false;
    }

    public function delete_profile( WP_REST_Request $request ){
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }
        $params = dt_recursive_sanitize_array( $params );

        $post_id = $this->verify_magic_link_and_get_post_id( $params['parts']['meta_key'], $params['parts']['public_key'] );
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, "Missing post record", [ 'status' => 400 ] );
        }

        $deleted = DT_Posts::delete_post( "subscriptions", $post_id, false );
        if ( is_wp_error( $deleted )){
            return new WP_Error( __METHOD__, "Could not delete profile", [ 'status' => 500 ] );
        }
        global $wpdb;
        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->dt_reports WHERE post_id = %s", $post_id ) );

        return true;
    }

    public function manage_profile( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, "Missing parameters", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $action = sanitize_text_field( wp_unslash( $params['action'] ) );

        // manage
        $post_id = $this->verify_magic_link_and_get_post_id( $params['parts']['meta_key'], $params['parts']['public_key'] );
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
        $campaign_grid_id = isset( $post["location_grid"][0]['id'] ) ? $post["location_grid"][0]['id'] : null;


        $campaign_instance = DT_Campaign_24Hour_Prayer::instance();
        foreach ( $params['selected_times'] as $time ){
            if ( !isset( $time["time"] ) ){
                continue;
            }
            $location_id = isset( $time['grid_id'] ) ? $time['grid_id'] : $campaign_grid_id;
            $args = [
                'parent_id' => $campaign_id,
                'post_id' => $post_id,
                'post_type' => 'subscriptions',
                'type' => $campaign_instance->root,
                'subtype' => $campaign_instance->type,
                'payload' => null,
                'value' => 1,
                'lng' => null,
                'lat' => null,
                'level' => null,
                'label' => null,
                'grid_id' => $time['grid_id'],
                'time_begin' => $time['time'],
                'time_end' => $time['time'] + 900,
            ];

            $grid_row = Disciple_Tools_Mapping_Queries::get_by_grid_id( $location_id );
            if ( ! empty( $grid_row ) ){
                $full_name = Disciple_Tools_Mapping_Queries::get_full_name_by_grid_id( $location_id );
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

class DT_Prayer_Subscription_Management_Magic_Link extends DT_Magic_Url_Base {

    public $post_type = 'subscriptions';
    public $page_title = "My Prayer Times";

    public $magic = false;
    public $parts = [];
    public $root = "subscriptions_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'manage'; // define the type
    public $type_name = 'Subscriptions';

    public $allowed_scripts = [
        'dt_campaign_core'
    ];

    public function __construct(){
        parent::__construct();
        if ( !$this->check_parts_match()){
            return;
        }
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        add_action( 'dt_blank_footer', [ $this, 'form_footer' ] );

        // load if valid url
        if ( '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'manage_body' ] );
        } else {
            return; // fail if no valid action url found
        }

        // load page elements
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
    }

    public function wp_enqueue_scripts(){
        wp_enqueue_script( 'dt_campaign_core', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'campaign_core.js', [
            'jquery',
            'lodash'
        ], filemtime( plugin_dir_path( __FILE__ ) . 'campaign_core.js' ), true );

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

            #email {
                display:none;
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
            .remove-my-prayer-time {
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
        $my_commitments_reports = DT_Subscriptions_Management::instance()->get_subscriptions( $this->parts['post_id'] );
        $my_commitments = [];
        foreach ( $my_commitments_reports as $commitments_report ){
            $my_commitments[] = [
                "time_begin" => $commitments_report["time_begin"],
                "value" => $commitments_report["value"],
                "report_id" => $commitments_report["id"],
                "verified" => $commitments_report["verified"] ?? false,

            ];
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
                'my_commitments' => $my_commitments,
            ]) ?>][0]

            jQuery(document).ready(function($){
                clearInterval(window.fiveMinuteTimer)
                $( document ).on( 'calendar_drawn', function (e){
                    postSubscriptions.my_commitments.forEach(c=>{
                        let time = c.time_begin;
                        let day_timestamp = day_start_timestamp_utc(c.time_begin)
                        let time_label = timestamp_to_time(c.time_begin)

                        $(`#calendar-extra-${day_timestamp}`).append(`
                            <div id="selected-${window.lodash.escape(time)}"
                                data-time="${window.lodash.escape(time)}">
                                ${window.lodash.escape(time_label)}
                                <i class="fi-x remove-selection remove-my-prayer-time" data-report=${window.lodash.escape(c.report_id)} data-time="${window.lodash.escape(time)}" data-day="${window.lodash.escape(day_timestamp)}"></i>
                                ${c.verified ? ' <span class="verified"><i class="fi-check"></i> Verified!</span>' : ''}
                            </div>
                        `)

                    })
                })


                $(document).on("click", '.remove-my-prayer-time', function (){
                    let id = $(this).data("report")
                    let spinner = $('.loading-spinner')
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
                        spinner.removeClass('active')
                    })
                    .fail(function(e) {
                        console.log(e)
                        jQuery('#error').html(e)
                    })
                })

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

                $('#confirm-delete-profile').on('click', function (){
                    $(this).toggleClass('loading')
                    let wrapper = jQuery('#wrapper')
                    jQuery.ajax({
                        type: "DELETE",
                        data: JSON.stringify({parts: postSubscriptions.parts}),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postSubscriptions.root + postSubscriptions.parts.root + '/v1/' + postSubscriptions.parts.type + '/delete_profile',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postSubscriptions.nonce )
                        }
                    }).done(function(data){
                        wrapper.empty().html(`
                            <div class="center">
                            <h1>Your profile has been deleted!</h1>
                            <p>Thank you for praying with us.<p>
                            </div>
                        `)
                        spinner.removeClass('active')
                        $(`#delete-profile-modal`).foundation('close')
                    })
                    .fail(function(e) {
                        console.log(e)
                        $('#confirm-delete-profile').toggleClass('loading')
                        $('#delete-account-errors').empty().html(`<div class="grid-x"><div class="cell center">
                        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>

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

            <h2 class=""><?php esc_html_e( 'My Prayer Times', 'disciple_tools' ); ?></h2>
            <?php
            $grid_id = 1;
            if ( isset( $campaign['location_grid'] ) && ! empty( $campaign['location_grid'] ) ) {
                $grid_id = $campaign['location_grid'][0]['id'];
            }
            $current_commitments = DT_Time_Utilities::subscribed_times_list( $campaign_id );

            DT_Campaign_24Hour_Prayer::calendar_subscribe( $campaign_id, $grid_id, $current_commitments, DT_Time_Utilities::start_of_campaign_with_timezone( $campaign_id ), DT_Time_Utilities::end_of_campaign_with_timezone( $campaign_id ) );
            ?><div class=""><h2>Sign up for more</h2></div>
            <?php
            DT_Campaign_24Hour_Prayer::select_times()
            ?>

            <br>
            <div class="grid-x grid-padding-x grid-padding-y">
                <div class="cell center">
                    <button class="button large" id="submit-form"><?php esc_html_e( 'Confirm new prayer times', 'disciple_tools' ); ?></button>
                </div>
            </div>


            <div style="margin-top: 50px">
                <h2>Danger Zone</h2>
                <label>
                    Delete this profile and all the scheduled prayer times?
                    <button class="button alert" data-open="delete-profile-modal">Delete</button>
                </label>
                <!-- Reveal Modal Daily time slot-->
                <div id="delete-profile-modal" class="reveal tiny" data-reveal>
                    <h2>Are you sure you want to delete your profile?</h2>
                    <p>
                        <?php esc_html_e( 'This can not be undone.', 'disciple_tools' ); ?>
                    </p>
                    <p id="delete-account-errors"></p>


                    <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                    </button>
                    <button class="button loader alert" type="button" id="confirm-delete-profile">
                        <?php echo esc_html__( 'DELETE', 'disciple_tools' )?>
                    </button>

                    <button class="close-button" data-close aria-label="Close modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}
