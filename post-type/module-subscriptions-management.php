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
        $sub = Disciple_Tools_Reports::get( $params["report_id"], 'id' );
        $time_in_mins = ( $sub["time_end"] - $sub["time_begin"] ) / 60;
        //@todo convert timezone?
        $label = "Commitment deleted: " . gmdate( 'F d, Y @ H:i a', $sub['time_begin'] ) . ' UTC for ' . $time_in_mins . ' minutes';
        Disciple_Tools_Reports::delete( $params['report_id'] );
        dt_activity_insert([
            'action' => 'delete_subscription',
            'object_type' => $this->post_type, // If this could be contacts/groups, that would be best
            'object_subtype' => 'report',
            'object_note' => $label,
            'object_id' => $post_id
        ] );
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
            $duration_mins = 15;
            if ( isset( $time["duration"] ) && is_numeric( $time["duration"] ) ){
                $duration_mins = $time["duration"];
            }
            $args = [
                'parent_id' => $campaign_id,
                'post_id' => $post_id,
                'post_type' => 'subscriptions',
                'type' => $campaign_instance->magic_link_root,
                'subtype' => $campaign_instance->magic_link_type,
                'payload' => null,
                'value' => 1,
                'lng' => null,
                'lat' => null,
                'level' => null,
                'label' => null,
                'grid_id' => $time['grid_id'],
                'time_begin' => $time['time'],
                'time_end' => $time['time'] + $duration_mins * 60,
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

            $time_in_mins = ( $args["time_end"] - $args["time_begin"] ) / 60;
            //@todo convert timezone?
            $label = "Commitment added: " . gmdate( 'F d, Y @ H:i a', $args['time_begin'] ) . ' UTC for ' . $time_in_mins . ' minutes';
            dt_activity_insert([
                'action' => 'add_subscription',
                'object_type' => $this->post_type, // If this could be contacts/groups, that would be best
                'object_subtype' => 'report',
                'object_note' => $label,
                'object_id' => $post_id
            ] );
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

    public function __construct(){
        parent::__construct();
        if ( !$this->check_parts_match()){
            return;
        }

        // add dt_campaign_core to allowed scripts
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        add_action( 'dt_blank_footer', [ $this, 'form_footer' ] );

        // load if valid url
        if ( '' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'manage_body' ] );
        } else {
            return; // fail if no valid action url found
        }

        // load page elements
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt_campaign_core';
        return $allowed_js;
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
            #calendar-content .day-cell {
                max-width: 25%;
                float:left;
                text-align: center;
                border: 1px solid grey;
            }
            .progress-bar {
                height:10px;
                background: dodgerblue;
                width:0;
            }
            .progress-bar-container {
                border: 1px solid #bfbfbf;
                margin-left: 5px;
                margin-right: 5px;
            }

            .remove-selection {
                /*float: right;*/
                color: red;
                cursor:pointer;
            }

            .day-extra {
                text-align: start;
                padding: 2px 5px;
            }

            .day-cell {
                /*flex-basis: 14%;*/
                text-align: center;
                flex-grow: 0;

            }
            .disabled-calendar-day {
                width:40px;
                height:40px;
                vertical-align: top;
                padding-top:10px;
                color: grey;
            }
            .calendar {
                display: flex;
                flex-wrap: wrap;
                width: 300px;
                margin: auto
            }
            .month-title {
                text-align: center;
                margin-bottom: 0;
            }
            .week-day {
                height: 20px;
                width:40px;
                color: grey;
            }
            #calendar-content h3 {
                margin-bottom: 0;
            }

            #list-day-times td, th {
                padding: 2px 6px
            }

            .day-in-select-calendar {
                color: black;
                display: inline-block;
                height: 40px;
                width: 40px;
                line-height: 0;
                vertical-align: middle;
                text-align: center;
                padding-top: 18px;
            }
            .selected-day {
                background-color: dodgerblue;
                color: white;
                border-radius: 50%;
                border: 2px solid;
            }

            #modal-calendar.small-view {
                display: flex;
                width: 250px;
                flex-wrap: wrap;
                margin: auto;
            }

            .small-view .calendar {
                width: 180px;
            }
            .small-view .month-title {
                width: 50px;
                padding: 0 10px;
                overflow: hidden;
                flex-grow: 1;
            }
            .small-view .calendar .week-day {
                height: 20px;
                width: 25px;
            }
            .small-view .calendar .day-in-select-calendar {
                height: 25px;
                width: 25px;
            }
            .small-view .calendar .disabled-calendar-day {
                width: 25px;
                height: 25px;
                padding-top: 5px;
                font-size: 11px;
            }

            .small-view .day-in-select-calendar {
                height: 25px;
                width: 25px;
                padding-top: 11px;
                font-size:8px;
            }

            .confirm-view {
                display: none;
            }
            .success-confirmation-section {
                display: none;
            }
            .deleted-time {
                color: grey;
                text-decoration: line-through;
            }

        </style>
        <?php
    }

    public function subscriptions_javascript_header(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $campaign_id = $post["campaigns"][0]["ID"];
        $current_commitments = DT_Time_Utilities::subscribed_times_list( $campaign_id );
        $my_commitments_reports = DT_Subscriptions_Management::instance()->get_subscriptions( $this->parts['post_id'] );
        $my_commitments = [];
        foreach ( $my_commitments_reports as $commitments_report ){
            $my_commitments[] = [
                "time_begin" => $commitments_report["time_begin"],
                "time_end" => $commitments_report["time_end"],
                "value" => $commitments_report["value"],
                "report_id" => $commitments_report["id"],
                "verified" => $commitments_report["verified"] ?? false,

            ];
        }
        ?>
        <script>
            let calendar_subscribe_object = [<?php echo json_encode([
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
                'campaign_id' => $campaign_id,
                'current_commitments' => $current_commitments,
                'start_timestamp' => (int) DT_Time_Utilities::start_of_campaign_with_timezone( $campaign_id ),
                'end_timestamp' => (int) DT_Time_Utilities::end_of_campaign_with_timezone( $campaign_id ) + 86400,
                'slot_length' => 15
            ]) ?>][0]

            let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
            const number_of_days = ( calendar_subscribe_object.end_timestamp - calendar_subscribe_object.start_timestamp ) / ( 24*3600)
            let time_slot_coverage = {}
            let selected_calendar_color = 'green'
            let verified = false

            jQuery(document).ready(function($){

                //set up array of days and time slots according to timezone
                let days = window.campaign_scripts.calculate_day_times();

                let update_timezone = function (){
                    $('.timezone-current').html(current_time_zone)
                    $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
                }
                update_timezone()

                // add selection function
                let add_selected = function (time, day, day_label, time_label, verified ){
                    $("#progress-section").hide()
                    $(`#calendar-extra-${day}`).append(`
                        <div id="selected-${window.lodash.escape(time)}" class="selected-hour"
                            data-time="${window.lodash.escape(time)}">
                            ${window.lodash.escape(time_label)}
                            <i class="fi-x remove-selection" data-time="${window.lodash.escape(time)}" data-day="${window.lodash.escape(day)}"></i>
                        </div>
                    `)
                    $('#confirmation-section').show()
                }

                let draw_calendar = ( id = 'calendar-content') => {
                    let content = $(`#${id}`)
                    content.empty()
                    let list = ''
                    let now = new Date().getTime()/1000
                    days.forEach(day=>{
                        let disabled = ( day.key + ( 24*3600 ) ) > now ?  '' : "disabled-day";
                        list += `<div class="cell day-cell ${disabled}">
                            <div class="${disabled ? '' : 'day-selector'}" data-time="${window.lodash.escape(day.key)}" data-day="${window.lodash.escape(day.key)}">
                                <div>${window.lodash.escape(day.formatted)} (${window.lodash.escape(parseInt(day.percent))}%)</div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" data-percent="${window.lodash.escape(day.percent)}" style="width:${window.lodash.escape(parseInt(day.percent))}%"></div>
                                </div>
                            </div>
                            <div class="day-extra" id=calendar-extra-${window.lodash.escape(day.key)}></div>
                        </div>`
                    })

                    content.html(`<div class="grid-x" id="selection-grid-wrapper">${list}</div>`)

                }
                draw_calendar()
                let add_my_commitments = ()=>{
                    $('.day-extra').empty()
                    calendar_subscribe_object.my_commitments.forEach(c=>{
                        let time = c.time_begin;
                        let now = new Date().getTime()/1000
                        if ( time >= now){
                            let day_timestamp = window.campaign_scripts.day_start(c.time_begin, current_time_zone)
                            let time_label = window.campaign_scripts.timestamp_to_time(c.time_begin, current_time_zone)
                            let time_end_label = window.campaign_scripts.timestamp_to_time(c.time_end, current_time_zone)
                            $(`#calendar-extra-${window.lodash.escape(day_timestamp)}`).append(`
                                <div id="selected-${window.lodash.escape(time)}"
                                    data-time="${window.lodash.escape(time)}">
                                    ${window.lodash.escape(time_label)} - ${window.lodash.escape(time_end_label)}
                                    <i class="fi-x remove-selection remove-my-prayer-time" data-report="${window.lodash.escape(c.report_id)}" data-time="${window.lodash.escape(time)}" data-day="${window.lodash.escape(day_timestamp)}"></i>
                                </div>
                            `)
                        }

                    })

                }
                add_my_commitments()
                if ( verified ){
                    $("#times-verified-notice").show()
                }

                //change timezone
                $('#confirm-timezone').on('click', function (){
                    current_time_zone = $("#timezone-select").val()
                    update_timezone()
                    days = window.campaign_scripts.calculate_day_times(current_time_zone)
                    draw_calendar()
                    add_my_commitments()
                    draw_modal_calendar()
                })

                $(document).on("click", '.remove-my-prayer-time', function (){
                    let x = $(this)
                    let id = x.data("report")
                    let time = x.data('time')
                    x.removeClass("fi-x").addClass("loading-spinner active");
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({ action: 'delete', parts: calendar_subscribe_object.parts, report_id: id }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
                        }
                    })
                    .done(function(data){
                        x.remove()
                        console.log("adding deleted time" + time);
                        $(`#selected-${time}`).addClass('deleted-time')
                    })
                    .fail(function(e) {
                        console.log(e)
                        jQuery('#error').html(e)
                    })
                })


                $('.day-selector').on( 'click', function (){
                    let day_timestamp = $(this).data('day')
                    $('#view-times-modal').foundation('open')
                    let list_title = jQuery('#list-modal-title')
                    let day=days.find(k=>k.key===day_timestamp)
                    console.log(day);
                    list_title.empty().html(`<h2 class="section_title">${window.lodash.escape(day.formatted)}</h2>`)
                    let day_times_content = $('#day-times-table-body')
                    let times_html = ``
                    let row_index = 0
                    day.slots.forEach(slot=>{
                        let background_color = 'white'
                        if ( slot.subscribers > 0) {
                            background_color = 'lightblue'
                        }
                        if ( row_index === 0 ){
                            times_html += `<tr><td>${window.lodash.escape(slot.formatted)}</td>`
                        }
                        times_html +=`<td style="background-color:${background_color}">
                            ${window.lodash.escape(slot.subscribers)} <i class="fi-torsos"></i>
                        </td>`
                        if ( times_html === 3 ){
                            times_html += `</tr>`
                        }
                        row_index = row_index === 3 ? 0 : row_index + 1;
                    })
                    day_times_content.empty().html(`<div class="grid-x"> ${times_html} </div>`)
                })


                /**
                 * add more times
                 */
                let headers = `
                    <div class="day-cell week-day">Su</div>
                    <div class="day-cell week-day">Mo</div>
                    <div class="day-cell week-day">Tu</div>
                    <div class="day-cell week-day">We</div>
                    <div class="day-cell week-day">Th</div>
                    <div class="day-cell week-day">Fr</div>
                    <div class="day-cell week-day">Sa</div>
                    `

                //build time select input
                let time_select = $('#daily_time_select')
                time_select.empty();
                time_select.html(window.campaign_scripts.get_time_select_html())

                //dawn calendar in date select modal
                let modal_calendar = $('#modal-calendar')
                let now = new Date().getTime()/1000
                let draw_modal_calendar = ()=> {
                    let last_month = "";
                    modal_calendar.empty()
                    let list = ''
                    days.forEach(day => {
                        if (day.month!==last_month) {
                            if (last_month) {
                                //add extra days at the month end
                                let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
                                for (let i = 1; i <= 7 - day_number; i++) {
                                    list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(i)}</div>`
                                }
                                list += `</div>`
                            }

                            list += `<h3 class="month-title">${window.lodash.escape(day.month)}</h3><div class="calendar">`
                            if (!last_month) {
                                list += headers
                            }

                            //add extra days at the month start
                            let day_number = new Date(window.campaign_scripts.day_start(day.key, current_time_zone) * 1000).getDay()
                            let start_of_week = new Date((day.key - day_number * 86400) * 1000)
                            for (let i = 0; i < day_number; i++) {
                                list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(start_of_week.getDate() + i)}</div>`
                            }
                            last_month = day.month
                        }
                        let disabled = (day.key + (24 * 3600)) < now;
                        list += `
                        <div class="day-cell ${disabled ? 'disabled-calendar-day':'selected-day day-in-select-calendar'}" data-day="${window.lodash.escape(day.key)}">
                            ${window.lodash.escape(day.day)}
                        </div>
                    `
                    })
                    modal_calendar.html(list)
                }
                draw_modal_calendar()

                let cal_select_all_div = $('#calendar-select-all-selected')
                let cal_select_help_text = $('#calendar-select-help')
                $(document).on('click', '.day-in-select-calendar', function (){
                    $(this).toggleClass('selected-day')
                    show_help_text()
                })

                $('#unselect-all-calendar-days').on( "click", function (){
                    $('.selected-day').toggleClass('selected-day')
                    show_help_text()
                })
                $('#select-all-calendar-days').on( "click", function (){
                    $('.day-in-select-calendar').addClass('selected-day')
                    show_help_text()
                })

                time_select.on( "change", function (){
                    disable_button( $('.selected-day').length )
                })

                let disable_button = ( num_selected ) => {
                    let select_val = $('#daily_time_select').val()
                    $('#confirm-daily-time').prop('disabled', num_selected === 0 || select_val === "false" )
                }
                let show_help_text = () =>{
                    let selected_days = $('.selected-day')
                    let number_of_days_selected = selected_days.length
                    if ( number_of_days_selected === days.length ){
                        cal_select_all_div.show()
                        cal_select_help_text.hide()
                    } else {
                        cal_select_all_div.hide()
                        cal_select_help_text.show()
                    }
                    $('#calendar-select-help-text').html(`${window.lodash.escape(number_of_days_selected)} of ${window.lodash.escape(days.length)} days selected`)
                    disable_button( number_of_days_selected )
                    let coverage = {}
                    let already_selected = parseInt(time_select.val())
                    selected_days.each((index, val)=> {
                        let day = $(val).data('day')
                        for ( const key in calendar_subscribe_object.current_commitments ){
                            if (!calendar_subscribe_object.current_commitments.hasOwnProperty(key)) {
                                continue;
                            }
                            if ( key >= day && key < day + 24 * 3600 ){
                                let mod_time = key % (24 * 60 * 60)
                                let time_formatted = '';
                                if ( window.campaign_scripts.processing_save[mod_time] ){
                                    time_formatted = window.campaign_scripts.processing_save[mod_time]
                                } else {
                                    time_formatted = window.campaign_scripts.timestamp_to_time( parseInt(key), current_time_zone )
                                    window.campaign_scripts.processing_save[mod_time] = time_formatted
                                }
                                if ( !coverage[time_formatted]){
                                    coverage[time_formatted] = 0;
                                }
                                coverage[time_formatted]++;
                            }
                        }
                    })
                    let select_html = `<option value="false">Select a time</option>`

                    let key = 0;
                    let start_of_today = new Date()
                    start_of_today.setHours(0,0,0,0)
                    let start_time_stamp = start_of_today.getTime()/1000
                    while ( key < 24 * 3600 ){
                        let time_formatted = window.campaign_scripts.timestamp_to_time(start_time_stamp+key)
                        let text = ''
                        let covered = coverage[time_formatted] ? coverage[time_formatted] === number_of_days_selected : false;
                        let fully_covered = window.campaign_scripts.time_slot_coverage[time_formatted] ? window.campaign_scripts.time_slot_coverage[time_formatted] === number_of_days : false;
                        if ( fully_covered ){
                            text = "(fully covered)"
                        } else if ( covered ){
                            text = "(covered for selected days)"
                        } else if ( coverage[time_formatted] > 0 ){
                            text = `(${coverage[time_formatted]} out of ${number_of_days_selected} selected days covered)`
                        }
                        select_html += `<option value="${window.lodash.escape(key)}" ${already_selected===key ? "selected" : ''}>
                            ${window.lodash.escape(time_formatted)} ${ window.lodash.escape(text) }
                        </option>`
                        key += calendar_subscribe_object.slot_length * 60
                    }
                    time_select.empty();
                    time_select.html(select_html)
                }

                $('#confirm-daily-time').on("click", function (){
                    // $('#confirm-times-modal').foundation('open');
                    $('#modal-calendar').toggleClass('small-view')
                    $('.select-view').hide()
                    $('.confirm-view').show()
                    $('#time-confirmation').html( $('#daily_time_select option:selected').text())
                    $('#time-duration-confirmation').html($('#prayer-time-duration-select option:selected').text())
                })

                $('#back-to-select').on('click', function (){
                    $('#modal-calendar').toggleClass('small-view')
                    $('.select-view').toggle()
                    $('.confirm-view').toggle()
                    show_help_text()
                })

                $('#submit-form').on('click', function (){
                    let submit_button = jQuery('#submit-form')
                    submit_button.addClass('loading')
                    submit_button.prop('disabled', true)

                    let selected_daily_time = time_select.val()
                    let selected_times = []
                    $('.selected-day').each((index, val)=>{
                        let day = $(val).data('day')
                        let time = parseInt(day) + parseInt(selected_daily_time)
                        let now = new Date().getTime()/1000
                        if ( time > now ){
                            let duration = parseInt($('#prayer-time-duration-select').val())
                            selected_times.push({time: time, duration: duration})
                        }
                    })


                    let data = {
                        action: 'add',
                        selected_times,
                        parts: calendar_subscribe_object.parts
                    }
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
                        }
                    })
                    .done(function(response){
                        submit_button.removeClass('loading')
                        $('#modal-calendar').hide()
                        $('.select-view').hide()
                        $('.confirm-view').hide()
                        $(`.success-confirmation-section`).show()
                        calendar_subscribe_object.my_commitments = response
                        add_my_commitments()
                        submit_button.prop('disabled', false)
                    })
                    .fail(function(e) {
                        console.log(e)
                        $('#selection-error').empty().html(`<div class="cell center">
                        So sorry. Something went wrong. Please, contact us to help you through it, or just try again.<br>
                        <a href="${window.lodash.escape(window.location.href)}">Try Again</a>
                        </div>`).show()
                        $('#error').html(e)
                        submit_button.removeClass('loading')
                    })
                })
                $('#close-ok-success').on("click", function (){
                    $('#modal-calendar').show()
                    $('.select-view').show()
                    $('.confirm-view').hide()
                    $(`.success-confirmation-section`).hide()
                })



                /**
                 * Delete profile
                 */
                $('#confirm-delete-profile').on('click', function (){
                    $(this).toggleClass('loading')
                    let wrapper = jQuery('#wrapper')
                    jQuery.ajax({
                        type: "DELETE",
                        data: JSON.stringify({parts: calendar_subscribe_object.parts}),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: calendar_subscribe_object.root + calendar_subscribe_object.parts.root + '/v1/' + calendar_subscribe_object.parts.type + '/delete_profile',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', calendar_subscribe_object.nonce )
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


            <div id="times-verified-notice" style="display:none; padding: 20px; background-color: lightgreen; border-radius: 5px; border: 1px green solid; margin-bottom: 20px;">
                Your prayer times have been verified!
            </div>

            <div class="center">
                <h2 class=""><?php esc_html_e( 'My Prayer Times', 'disciple_tools' ); ?></h2>
            </div>

            <div id="type-individual-section">
                <strong><?php esc_html_e( 'Showing times for:', 'disciple_tools' ); ?></strong> <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>

                <div class="grid-x" style=" height: inherit !important;">
                    <div class="cell center" id="bottom-spinner"><span class="loading-spinner"></span></div>
                    <div class="cell" id="calendar-content"><div class="center">... <?php esc_html_e( 'loading', 'disciple_tools' ); ?></div></div>
                    <div class="cell grid" id="error"></div>
                </div>
            </div>


            <!-- Reveal Modal Timezone Changer-->
            <div id="timezone-changer" class="reveal tiny" data-reveal>
                <h2>Change your timezone:</h2>
                <select id="timezone-select">
                    <?php
                    $selected_tz = 'America/Denver';
                    if ( !empty( $selected_tz ) ){
                        ?>
                        <option id="selected-time-zone" value="<?php echo esc_html( $selected_tz ) ?>" selected><?php echo esc_html( $selected_tz ) ?></option>
                        <option disabled>----</option>
                        <?php
                    }
                    $tzlist = DateTimeZone::listIdentifiers( DateTimeZone::ALL );
                    foreach ( $tzlist as $tz ){
                        ?><option value="<?php echo esc_html( $tz ) ?>"><?php echo esc_html( $tz ) ?></option><?php
                    }
                    ?>
                </select>
                <button class="button button-cancel clear" data-close aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                </button>
                <button class="button" type="button" id="confirm-timezone" data-close>
                    <?php echo esc_html__( 'Select', 'disciple_tools' )?>
                </button>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>


            <div class="center">
                <button class="button" data-open="select-times-modal" id="open-select-times-button" style="margin-top: 10px">
                    Choose New Prayer Times
                </button>
            </div>
            <div class="reveal" id="view-times-modal" data-reveal data-close-on-click="true">
                <h3 id="list-modal-title"></h3>

                <div id="list-day-times">
                    <table>
                        <thead>
                        <tr>
                            <th></th>
                            <th>:00</th>
                            <th>:15</th>
                            <th>:30</th>
                            <th>:45</th>
                        </tr>
                        </thead>
                        <tbody id="day-times-table-body">

                        </tbody>
                    </table>
                </div>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="center">
                    <button class="button" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Close', 'disciple_tools' )?>
                    </button>
                </div>
            </div>


            <div class="reveal" id="select-times-modal" data-reveal data-close-on-click="false" data-multiple-opened="true">

                <div id="modal-calendar">Modal Calendar</div>
                <p id="calendar-select-all-selected" class="select-view center" style="margin-top: 10px">
                    <strong>All days selected.</strong>
                    <a id="unselect-all-calendar-days">unselect all</a>
                </p>
                <p id="calendar-select-help" class="select-view center" style="margin-top: 10px; display: none">
                    <span id="calendar-select-help-text" style="font-weight: bold"></span>
                    <a id="select-all-calendar-days">select all</a>
                </p>

                <label class="select-view" >
                    <strong>Prayer Time</strong>
                    <select id="daily_time_select" class="select-view">
                        <option>Daily Time</option>
                    </select>
                </label>
                <label class="select-view">
                    <strong>For</strong>
                    <select id="prayer-time-duration-select" class="select-view">
                        <option value="15">15 Minutes</option>
                        <option value="30">30 Minutes</option>
                        <option value="45">45 Minutes</option>
                        <option value="60">1 Hour</option>
                        <option value="90">1 Hour 30 Minutes</option>
                        <option value="120">2 Hours</option>
                    </select>
                </label>
                <p class="select-view">
                    <strong>
                        <?php esc_html_e( 'Using timezone for:', 'disciple_tools' ); ?></strong>
                    <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                </p>

                <div id="confirmation-section" class="confirm-view" style="margin-top:15px">
                    <p>
                        You are signing up to praying for
                        <span id="time-duration-confirmation" style="font-weight: bold" ></span>
                        on each selected day at <span id="time-confirmation" style="font-weight: bold"></span>
                        in <span class="timezone-current"></span> timezone.
                    </p>


                    <div class="grid-x grid-padding-x grid-padding-y">
<!--                        <div class="cell center">-->
<!--                            <label for="receive_prayer_time_notifications">-->
<!--                                <input type="checkbox" id="receive_prayer_time_notifications" name="receive_prayer_time_notifications" checked />-->
<!--                                --><?php //esc_html_e( 'Receive Prayer Time Notifications (email verification needed).', 'disciple_tools' ); ?>
<!--                            </label>-->
<!--                        </div>-->
                        <div class="cell center">
                                <span id="selection-error" class="form-error">
                                    <?php esc_html_e( 'You must select at least one time slot above.', 'disciple_tools' ); ?>
                                </span>
                            <button class="button loader" id="submit-form"><?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple_tools' ); ?> <span class="loading-spinner"></span></button>
                        </div>
                    </div>
                </div>

                <div class="success-confirmation-section">
                    <div class="cell center">
                        <h2>Your new prayer times have been saved.</h2>
                    </div>
                </div>


                <div class="center">
                    <button class="button button-cancel clear select-view" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
                    </button>

                    <button class="button select-view" type="button" id="confirm-daily-time" disabled>
                        <?php echo esc_html__( 'Next', 'disciple_tools' )?>
                    </button>
                </div>
                <button class="button button-cancel clear confirm-view" id="back-to-select" aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'back', 'disciple_tools' )?>
                </button>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="center">
                    <button class="button success-confirmation-section" id="close-ok-success" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'ok', 'disciple_tools' )?>
                    </button>
                </div>
            </div>

            <div style="margin-top: 50px">
                <hr>
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
