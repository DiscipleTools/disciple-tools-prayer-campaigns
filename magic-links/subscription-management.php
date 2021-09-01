<?php
class DT_Prayer_Subscription_Management_Magic_Link extends DT_Magic_Url_Base {

    public $post_type = 'subscriptions';
    public $page_title = "My Prayer Times";

    public $magic = false;
    public $parts = [];
    public $root = "subscriptions_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = 'manage'; // define the type
    public $type_name = 'Subscriptions';
    public $type_actions = [
        '' => "Manage",
        'download_calendar' => 'Download Calendar',
    ];

    public function __construct(){
        parent::__construct();
        if ( !$this->check_parts_match()){
            return;
        }
        $post = DT_Posts::get_post( "subscriptions", $this->parts["post_id"], true, false );
        if ( is_wp_error( $post ) || empty( $post["campaigns"] ) ){
            return;
        }
        if ( $post["lang"] && $post["lang"] !== "en_US" ){
            $lang_code = $post["lang"];
            add_filter( 'determine_locale', function ( $locale ) use ( $lang_code ){
                if ( !empty( $lang_code ) ){
                    return $lang_code;
                }
                return $locale;
            } );
        }
        $this->page_title = __( "My Prayer Times", 'disciple-tools-prayer-campaigns' );

        // add dt_campaign_core to allowed scripts
        add_action( 'dt_blank_head', [ $this, 'form_head' ] );
        add_action( 'dt_blank_footer', [ $this, 'form_footer' ] );

        // load if valid url
        if ( 'download_calendar' === $this->parts['action'] ) {
            //add_action( 'dt_blank_footer', [ $this, 'display_calendar' ] );
            $this->display_calendar();
            return;
        } else if ( '' === $this->parts['action'] ) {
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
        wp_enqueue_script( 'dt_campaign_core', trailingslashit( plugin_dir_url( __DIR__ ) ) . 'post-type/campaign_core.js', [
            'jquery',
            'lodash'
        ], filemtime( plugin_dir_path( __DIR__ ) . 'post-type/campaign_core.js' ), true );

    }

    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        $this->subscriptions_styles_header();
    }
    public function form_footer(){
        $this->subscriptions_javascript_header();
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
            .day-selector {
                cursor: pointer;
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
                padding: 5px 5px;
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


    public function get_clean_duration( $start_time, $end_time ) {
        $time_duration = ( $start_time - $end_time ) / 60;

        switch ( true ) {
            case $time_duration < 60:
                $time_duration .= ' minutes';
                break;
            case $time_duration === 60:
                $time_duration = $time_duration / 60 . ' hour';
                break;
            case $time_duration < 60:
                $time_duration = $time_duration . ' hours';
                break;
            case $time_duration > 60:
                $time_duration = $time_duration / 60 . ' hours';
        }
        return $time_duration;
    }

    public function get_timezone_offset( $timezone ) {
        $dt_now = new DateTime();
        $dt_now->setTimezone( new DateTimeZone( esc_html( $timezone ) ) );
        $timezone_offset = sprintf( '%+03d', $dt_now->getOffset() / 3600 );
        return $timezone_offset;
    }

    public function get_download_url() {
        $download_url = trailingslashit( $this->parts['public_key'] ) .'download_calendar';
        return $download_url;
    }

    public function display_calendar() {
        // Get post data
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $campaign_id = $post["campaigns"][0]["ID"];
        $locale = $post["lang"] ?: "en_US";

        //get summary from campaign strings
        $calendar_title = $post['campaigns'][0]['post_title'];
        $campaign = DT_Posts::get_post( "campaigns", $campaign_id, true, false );
        if ( isset( $campaign["campaign_strings"][$locale]["campaign_description"] ) ){
            $calendar_title = $campaign["campaign_strings"][$locale]["campaign_description"];
        } elseif ( isset( $campaign["campaign_strings"]["en_US"]["campaign_description"] ) ){
            $calendar_title = $campaign["campaign_strings"]["en_US"]["campaign_description"];
        }
        $calendar_timezone = $post['timezone'];
        $calendar_dtstamp = gmdate( 'Ymd' ).'T'. gmdate( 'His' ) . "Z";
        $calendar_description = "";
        if ( isset( $campaign["campaign_strings"][$locale]["reminder_content"] ) ){
            $calendar_description = $campaign["campaign_strings"][$locale]["reminder_content"];
        } elseif ( isset( $campaign["campaign_strings"]["en_US"]["reminder_content"] ) ){
            $calendar_description = $campaign["campaign_strings"]["en_US"]["reminder_content"];
        }
        $calendar_timezone_offset = self::get_timezone_offset( esc_html( $calendar_timezone ) );

        $my_commitments_reports = DT_Subscriptions_Management::instance()->get_subscriptions( $this->parts['post_id'] );
        $my_commitments = [];

        foreach ( $my_commitments_reports as $commitments_report ){
            $commitments_report['time_begin'] = $commitments_report['time_begin'] + $calendar_timezone_offset * 3600;
            $commitments_report['time_end'] = $commitments_report['time_end'] + $calendar_timezone_offset * 3600;

            $my_commitments[] = [
                "time_begin" => gmdate( 'Ymd', $commitments_report["time_begin"] ) . 'T'. gmdate( 'His', $commitments_report["time_begin"] ),
                "time_end" => gmdate( 'Ymd', $commitments_report["time_end"] ) . 'T'. gmdate( 'His', $commitments_report["time_end"] ),
                "time_duration" => self::get_clean_duration( $commitments_report["time_end"], $commitments_report["time_begin"] ),
                "location" => $commitments_report['label'],
            ];
        }

        header( 'Content-type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: inline; filename=calendar.ics' );

        echo "BEGIN:VCALENDAR\r\n";
        echo "VERSION:2.0\r\n";
        echo "PRODID:-//disciple.tools\r\n";
        echo "CALSCALE:GREGORIAN\r\n";
        echo "BEGIN:VTIMEZONE\r\n";
        echo "TZID:" . esc_html( $calendar_timezone ) . "\r\n";
        echo "BEGIN:STANDARD\r\n";
        echo "TZNAME:" . esc_html( $calendar_timezone_offset ) . "\r\n";
        echo "TZOFFSETFROM:" . esc_html( $calendar_timezone_offset ) . "00\r\n";
        echo "TZOFFSETTO:" . esc_html( $calendar_timezone_offset ) . "00\r\n";
        echo "DTSTART:19700101T000000\r\n";
        echo "END:STANDARD\r\n";
        echo "END:VTIMEZONE\r\n";

        foreach ( $my_commitments as $mc ) {
            $calendar_uid = md5( uniqid( mt_rand(), true ) ) . "@disciple.tools";

            echo "BEGIN:VEVENT\r\n";
            echo "UID:" . esc_html( $calendar_uid ) . "\r\n";
            echo "DTSTAMP:" . esc_html( $calendar_dtstamp ) . "\r\n";
            echo "SUMMARY:" . esc_html( $calendar_title ) . "\r\n";
            echo "DTSTART:" . esc_html( $mc['time_begin'] ) . "\r\n";
            echo "DTEND:" . esc_html( $mc['time_end'] ) . "\r\n";
            echo "DESCRIPTION:" . esc_html( $calendar_description ) . "\r\n";
            echo "STATUS:CONFIRMED\r\n";
            echo "SEQUENCE:3\r\n";
            echo "BEGIN:VALARM\r\n";
            echo "TRIGGER:-PT10M\r\n";
            echo "ACTION:DISPLAY\r\n";
            echo "END:VALARM\r\n";
            echo "END:VEVENT\r\n";
        }

        echo "END:VCALENDAR\r\n";
        die();
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
        $field_settings = DT_Posts::get_post_field_settings( "campaigns" );
        ?>
        <script>
            let calendar_subscribe_object = [<?php echo json_encode([
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'name' => get_the_title( $this->parts['post_id'] ),
                'translations' => [
                    "select_a_time" => __( 'Select a time', 'disciple-tools-prayer-campaigns' ),
                    "fully_covered_once" => __( 'fully covered once', 'disciple-tools-prayer-campaigns' ),
                    "fully_covered_x_times" => __( 'fully covered %1$s times', 'disciple-tools-prayer-campaigns' ),
                    "time_slot_label" => _x( '%1$s for %2$s minutes.', "Monday 5pm for 15 minutes", 'disciple-tools-prayer-campaigns' ),
                ],
                'my_commitments' => $my_commitments,
                'campaign_id' => $campaign_id,
                'current_commitments' => $current_commitments,
                'start_timestamp' => (int) DT_Time_Utilities::start_of_campaign_with_timezone( $campaign_id ),
                'end_timestamp' => (int) DT_Time_Utilities::end_of_campaign_with_timezone( $campaign_id ) + 86400,
                'slot_length' => 15,
                'timezone' => $post["timezone"],
                "duration_options" => $field_settings["duration_options"]["default"]
            ]) ?>][0]


            let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
            if ( calendar_subscribe_object.timezone ){
                current_time_zone = calendar_subscribe_object.timezone
            }
            const number_of_days = ( calendar_subscribe_object.end_timestamp - calendar_subscribe_object.start_timestamp ) / ( 24*3600)
            let time_slot_coverage = {}
            let selected_calendar_color = 'green'
            let verified = false

            jQuery(document).ready(function($){

                //set up array of days and time slots according to timezone
                let days = window.campaign_scripts.calculate_day_times(current_time_zone);

                let update_timezone = function (){
                    $('.timezone-current').html(current_time_zone)
                    $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
                }
                update_timezone()
                /**
                 * Draw or refresh the main calendar
                 */
                let draw_calendar = ( id = 'calendar-content') => {
                    let content = $(`#${id}`)
                    content.empty()
                    let list = ''
                    days.forEach(day=>{
                        list += `<div class="cell day-cell">
                            <div class="day-selector" data-time="${window.lodash.escape(day.key)}" data-day="${window.lodash.escape(day.key)}">
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

                /**
                 * SHow my commitment under each day
                 */
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

                /**
                 * Add notice showing that my times have been verified
                 */
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

                /**
                 * Remove a prayer time
                 */
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


                /**
                 * Modal for displaying on individual day
                 */
                $('.day-selector').on( 'click', function (){
                    let day_timestamp = $(this).data('day')
                    $('#view-times-modal').foundation('open')
                    let list_title = jQuery('#list-modal-title')
                    let day=days.find(k=>k.key===day_timestamp)
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



                function days_for_locale(localeName = 'en-US', weekday = 'long') {
                    let now = new Date()
                    const format = new Intl.DateTimeFormat(localeName, { weekday }).format;
                    return [...Array(7).keys()]
                    .map((day) => format(new Date().getTime() - ( now.getDay() - day  ) * 86400000 ));
                }
                let week_day_names = days_for_locale(navigator.language, 'narrow')
                let headers = `
                  <div class="day-cell week-day">${week_day_names[0]}</div>
                  <div class="day-cell week-day">${week_day_names[1]}</div>
                  <div class="day-cell week-day">${week_day_names[2]}</div>
                  <div class="day-cell week-day">${week_day_names[3]}</div>
                  <div class="day-cell week-day">${week_day_names[4]}</div>
                  <div class="day-cell week-day">${week_day_names[5]}</div>
                  <div class="day-cell week-day">${week_day_names[6]}</div>
                `


                /**
                 * daily prayer time screen
                 */
                let daily_time_select = $('#cp-daily-time-select')

                let select_html = `<option value="false">${calendar_subscribe_object.translations.select_a_time}</option>`

                let coverage = {}
                days.forEach(val=> {
                    let day = val.key
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
                                coverage[time_formatted] = [];
                            }
                            coverage[time_formatted].push(calendar_subscribe_object.current_commitments[key]);
                        }
                    }
                })
                let key = 0;
                let start_of_today = new Date()
                start_of_today.setHours(0,0,0,0)
                let start_time_stamp = start_of_today.getTime()/1000
                while ( key < 24 * 3600 ){
                    let time_formatted = window.campaign_scripts.timestamp_to_time(start_time_stamp+key)
                    let text = ''
                    let fully_covered = window.campaign_scripts.time_slot_coverage[time_formatted] ? window.campaign_scripts.time_slot_coverage[time_formatted] === number_of_days : false;
                    let level_covered = coverage[time_formatted] ? Math.min(...coverage[time_formatted]) : 0
                    if ( fully_covered && level_covered > 1  ){
                        text = `(${calendar_subscribe_object.translations.fully_covered_x_times.replace( '%1$s', level_covered)})`
                    } else if ( fully_covered ) {
                        text = `(${calendar_subscribe_object.translations.fully_covered_once})`
                    }
                    select_html += `<option value="${window.lodash.escape(key)}">
                      ${window.lodash.escape(time_formatted)} ${ window.lodash.escape(text) }
                  </option>`
                    key += calendar_subscribe_object.slot_length * 60
                }
                daily_time_select.empty();
                daily_time_select.html(select_html)

                let duration_options_html = ``
                for (const prop in calendar_subscribe_object.duration_options) {
                    if (calendar_subscribe_object.duration_options.hasOwnProperty(prop) && parseInt(prop) >= parseInt(calendar_subscribe_object.slot_length) ) {
                        duration_options_html += `<option value="${window.lodash.escape(prop)}">${window.lodash.escape(calendar_subscribe_object.duration_options[prop].label)}</option>`
                    }
                }
                $(".cp-time-duration-select").html(duration_options_html)

                daily_time_select.on("change", function (){
                    $('#cp-confirm-daily-times').attr('disabled', false)
                })

                let selected_times = [];
                $('#cp-confirm-daily-times').on("click", function (){
                    let daily_time_selected = parseInt($("#cp-daily-time-select").val());
                    let duration = parseInt($("#cp-prayer-time-duration-select").val())
                    days.forEach( day=>{
                        let time = day.key + daily_time_selected;
                        let now = new Date().getTime()/1000
                        let time_label = window.campaign_scripts.timestamp_to_format( time, { month: "long", day: "numeric", hour:"numeric", minute: "numeric" }, current_time_zone)
                        let already_added = selected_times.find(k=>k.time===time)
                        if ( !already_added && time > now && time >= calendar_subscribe_object['start_timestamp'] ) {
                            selected_times.push({time: time, duration: duration, label: time_label})
                        }
                    })
                    submit_times();
                })



                /**
                 * Individual prayer times screen
                 */
                let current_time_selected = $("cp-individual-time-select").val();

                //build the list of individually selected times
                let display_selected_times = function (){
                    let html = ""
                    selected_times.sort((a,b)=>{
                        return a.time - b.time
                    });
                    selected_times.forEach(time=>{
                        html += `<li>${calendar_subscribe_object.translations.time_slot_label.replace( '%1$s', time.label).replace( '%2$s', time.duration )} </li>`
                    })
                    $('.cp-display-selected-times').html(html)
                }

                //add a selected time to the array
                $('#cp-add-prayer-time').on("click", function(){
                    current_time_selected = $("#cp-individual-time-select").val();
                    let duration = parseInt($("#cp-individual-prayer-time-duration-select").val())
                    let time_label = window.campaign_scripts.timestamp_to_format( current_time_selected, { month: "long", day: "numeric", hour:"numeric", minute: "numeric" }, current_time_zone)
                    let now = new Date().getTime()/1000
                    let already_added = selected_times.find(k=>k.time===current_time_selected)
                    if ( !already_added && current_time_selected > now && current_time_selected >= calendar_subscribe_object['start_timestamp'] ){
                        $('#cp-time-added').show().fadeOut(1000)
                        selected_times.push({time: current_time_selected, duration: duration, label: time_label })
                    }
                    display_selected_times()
                    $('#cp-confirm-individual-times').attr('disabled', false)
                })

                //dawn calendar in date select view
                let modal_calendar = $('#day-select-calendar')
                let now = new Date().getTime()/1000
                let draw_modal_calendar = ()=> {
                    let last_month = "";
                    modal_calendar.empty()
                    let list = ''
                    days.forEach(day => {
                        if (day.month!==last_month) {
                            if (last_month) {
                                //add extra days at the month end
                                let day_number = window.campaign_scripts.get_day_number(day.key, current_time_zone);
                                if ( day_number !== 0 ) {
                                    for (let i = 1; i <= 7 - day_number; i++) {
                                        list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(i)}</div>`
                                    }
                                }
                                list += `</div>`
                            }

                            list += `<h3 class="month-title">${window.lodash.escape(day.month)}</h3><div class="calendar">`
                            if (!last_month) {
                                list += headers
                            }

                            //add extra days at the month start
                            let day_number = window.campaign_scripts.get_day_number(day.key, current_time_zone);
                            let start_of_week = window.campaign_scripts.start_of_week(day.key, current_time_zone);
                            for (let i = 0; i < day_number; i++) {
                                list += `<div class="day-cell disabled-calendar-day">${window.lodash.escape(start_of_week.getDate() + i)}</div>`
                            }
                            last_month = day.month
                        }
                        let disabled = (day.key + (24 * 3600)) < now;
                        list += `
            <div class="day-cell ${disabled ? 'disabled-calendar-day':'day-in-select-calendar'}" data-day="${window.lodash.escape(day.key)}">
                ${window.lodash.escape(day.day)}
            </div>
        `
                    })
                    modal_calendar.html(list)
                }
                draw_modal_calendar()

                //when a day is clicked on from the calendar
                $(document).on('click', '.day-in-select-calendar', function (){
                    $('#day-select-calendar div').removeClass('selected-day')
                    $(this).toggleClass('selected-day')
                    //get day and build content
                    let day_key = parseInt($(this).data("day"))
                    let day=days.find(k=>k.key===day_key);
                    //set time key on add button
                    $('#cp-add-prayer-time').data("day", day_key).attr('disabled', false)

                    //build time select
                    let select_html = ``;
                    day.slots.forEach(slot=> {
                        let text = ``
                        if ( slot.subscribers===1 ) {
                            text = "(covered once)";
                        }
                        if ( slot.subscribers > 1 ) {
                            text = `(covered ${slot.subscribers} times)`;
                        }
                        select_html += `<option value="${window.lodash.escape(slot.key)}" ${ (slot.key%(24*3600)) === (current_time_selected%(24*3600)) ? "selected" : '' }>
                            ${window.lodash.escape(slot.formatted)} ${window.lodash.escape(text)}
                        </option>`
                    })
                    $('#cp-individual-time-select').html(select_html).attr('disabled', false)
                })


                $('#cp-confirm-individual-times').on( 'click', function (){
                    submit_times();
                })

                let submit_times = function(){
                    let submit_button = $('.submit-form-button')
                    submit_button.addClass( 'loading' )
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
                        $('.hide-on-success').hide();
                        submit_button.removeClass('loading')
                        $('#modal-calendar').hide()

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
                }
                $('.close-ok-success').on("click", function (){
                    window.location.reload()
                })



                /**
                 * Delete profile
                 */
                $('#confirm-delete-profile').on('click', function (){
                    let spinner = $(this)
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
                    }).done(function(){
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
        //cannot manage a subscription that has no campaign
        if ( empty( $campaign_id ) ){
            $this->error_body();
            exit;
        }


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
                <?php esc_html_e( 'Your prayer times have been verified!', 'disciple-tools-prayer-campaigns' ); ?>
            </div>

            <div class="center">
                <h2 class=""><?php esc_html_e( 'My Prayer Times', 'disciple-tools-prayer-campaigns' ); ?></h2>
            </div>

            <div id="type-individual-section">
                <strong><?php esc_html_e( 'Showing times for:', 'disciple-tools-prayer-campaigns' ); ?></strong> <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>

                <div class="grid-x" style=" height: inherit !important;">
                    <div class="cell center" id="bottom-spinner"><span class="loading-spinner"></span></div>
                    <div class="cell" id="calendar-content"><div class="center">... <?php esc_html_e( 'loading', 'disciple-tools-prayer-campaigns' ); ?></div></div>
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
                    <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                </button>
                <button class="button" type="button" id="confirm-timezone" data-close>
                    <?php echo esc_html__( 'Select', 'disciple-tools-prayer-campaigns' )?>
                </button>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>


            <div class="center">
                <button class="button" data-open="daily-select-modal" id="open-select-times-button" style="margin-top: 10px">
                    <?php esc_html_e( 'Add a Daily Prayer Time', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
                <button class="button" data-open="select-times-modal" id="open-select-times-button" style="margin-top: 10px">
                    <?php esc_html_e( 'Add Individual Prayer Times', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
                <a class="button" style="margin-top: 10px" target="_blank" href="<?php echo esc_attr( self::get_download_url() ); ?>"><?php esc_html_e( 'Download Calendar', 'disciple-tools-prayer-campaigns' ); ?></a>
            </div>

            <div class="reveal" id="daily-select-modal" data-reveal>
                <label>
                    <strong><?php esc_html_e( 'Prayer Time', 'disciple-tools-prayer-campaigns' ); ?></strong>
                    <select id="cp-daily-time-select">
                        <option><?php esc_html_e( 'Daily Time', 'disciple-tools-prayer-campaigns' ); ?></option>
                    </select>
                </label>
                <label>
                    <strong><?php esc_html_e( 'For how long', 'disciple-tools-prayer-campaigns' ); ?></strong>
                    <select id="cp-prayer-time-duration-select" class="cp-time-duration-select"></select>
                </label>
                <p>
                    <strong><?php esc_html_e( 'Showing times for:', 'disciple-tools-prayer-campaigns' ); ?></strong> <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                </p>

                <div class="success-confirmation-section">
                    <div class="cell center">
                        <h2><?php esc_html_e( 'Your new prayer times have been saved.', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    </div>
                </div>


                <div class="center hide-on-success">
                    <button class="button button-cancel clear select-view" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                    </button>

                    <button disabled id="cp-confirm-daily-times" class="cp-nav button submit-form-button loader">
                        <?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?>
                    </button>
                </div>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="center">
                    <button class="button success-confirmation-section close-ok-success" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'ok', 'disciple-tools-prayer-campaigns' )?>
                    </button>
                </div>

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
                        <?php echo esc_html__( 'Close', 'disciple-tools-prayer-campaigns' )?>
                    </button>
                </div>
            </div>

            <div class="reveal" id="select-times-modal" data-reveal data-close-on-click="false" data-multiple-opened="true">

                <h2 id="individual-day-title" class="cp-center">
                    <?php esc_html_e( 'Select a day and choose a time', 'disciple-tools-prayer-campaigns' ); ?>
                </h2>
                <div id="cp-day-content" class="cp-center" >
                    <div style="margin-bottom: 20px">
                        <div id="day-select-calendar" class=""></div>
                    </div>
                    <label>
                        <strong><?php esc_html_e( 'Select a prayer time', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        <select id="cp-individual-time-select" disabled style="margin: auto">
                            <option><?php esc_html_e( 'Daily Time', 'disciple-tools-prayer-campaigns' ); ?></option>
                        </select>
                    </label>
                    <label>
                        <strong><?php esc_html_e( 'For how long', 'disciple-tools-prayer-campaigns' ); ?></strong>
                        <select id="cp-individual-prayer-time-duration-select" class="cp-time-duration-select" style="margin: auto"></select>
                    </label>
                    <div>
                        <button class="button" id="cp-add-prayer-time" data-day="" disabled style="margin: 10px 0; display: inline-block"><?php esc_html_e( 'Add prayer time', 'disciple-tools-prayer-campaigns' ); ?></button>
                        <span style="display: none" id="cp-time-added"><?php esc_html_e( 'Time added', 'disciple-tools-prayer-campaigns' ); ?></span>
                    </div>
                    <p>
                        <strong><?php esc_html_e( 'Showing times for:', 'disciple-tools-prayer-campaigns' ); ?></strong> <a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>
                    </p>

                    <div style="margin: 30px 0">
                        <h3><?php esc_html_e( 'Selected Times', 'disciple-tools-prayer-campaigns' ); ?></h3>
                        <ul class="cp-display-selected-times">
                            <li><?php esc_html_e( 'No selected Time', 'disciple-tools-prayer-campaigns' ); ?></li>
                        </ul>
                    </div>

                </div>

                <div class="success-confirmation-section">
                    <div class="cell center">
                        <h2><?php esc_html_e( 'Your new prayer times have been saved.', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    </div>
                </div>


                <div class="center hide-on-success">
                    <button class="button button-cancel clear select-view" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
                    </button>

                    <button disabled id="cp-confirm-individual-times" class="button submit-form-button loader">
                        <?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?>
                    </button>
                </div>
                <button class="button button-cancel clear confirm-view" id="back-to-select" aria-label="Close reveal" type="button">
                    <?php echo esc_html__( 'back', 'disciple-tools-prayer-campaigns' )?>
                </button>

                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="center">
                    <button class="button success-confirmation-section close-ok-success" data-close aria-label="Close reveal" type="button">
                        <?php echo esc_html__( 'ok', 'disciple-tools-prayer-campaigns' )?>
                    </button>
                </div>
            </div>

            <style>
                .danger-zone{
                    margin: 0 8px 0 8px;
                    display: flex;
                    justify-content: space-between;
                }

                .chevron img{
                    vertical-align: middle;
                    width: 20px;
                    cursor: pointer;
                }

                .danger-zone-content {
                    display: flex;
                    justify-content: space-between;
                    margin: 24px 10% 0 5%;
                }
                .collapsed {
                    display: none;
                }

                .toggle_up {
                    -moz-transform: scale(-1, -1);
                    -o-transform: scale(-1, -1);
                    -webkit-transform: scale(-1, -1);
                    transform: scale(-1, -1);
                }
            </style>
            <script>
                function toggle_danger() {
                    $('.danger-zone-content').toggleClass('collapsed');
                    $('.chevron').toggleClass('toggle_up');
                }
            </script>

            <div style="margin-top: 50px">
                <hr>
                <section>
                    <div class="danger-zone">
                        <h2><?php esc_html_e( 'Danger Zone', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <button class="chevron" onclick="toggle_danger();">
                            <img src="<?php echo esc_html( get_template_directory_uri() ); ?>/dt-assets/images/chevron_down.svg">
                        </button>
                    </div>
                    <div class="danger-zone-content collapsed">
                        <label>
                            <?php esc_html_e( 'Delete this profile and all the scheduled prayer times?', 'disciple-tools-prayer-campaigns' ); ?>
                        </label>
                        <button class="button alert" data-open="delete-profile-modal">Delete</button>
                        <!-- Reveal Modal Daily time slot-->
                        <div id="delete-profile-modal" class="reveal tiny" data-reveal>
                            <h2><?php esc_html_e( 'Are you sure you want to delete your profile?', 'disciple-tools-prayer-campaigns' ); ?></h2>
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
                </section>
            </div>
        </div>
        <?php
    }

    public function error_body(){
        ?>
        <div class="center" style="margin-top:50px">
            <h2 class=""><?php esc_html_e( 'This subscription has ended or is not configured correctly.', 'disciple-tools-prayer-campaigns' ); ?></h2>
        </div>
        <?php
    }
}
