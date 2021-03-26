<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Campaign_24Hour_Prayer extends DT_Module_Base
{
    public $module = "campaigns_24hour_prayer";
    public $post_type = 'campaigns';

    public $magic = false;
    public $parts = false;
    public $root = "campaign_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = '24hour'; // define the type


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
        add_filter( 'dt_campaign_types', [ $this, 'dt_campaign_types' ], 20, 1 );
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 30, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
        add_action( 'wp_enqueue_scripts', [ $this, 'tile_scripts' ], 100 );

        // register type
        $this->magic = new DT_Magic_URL( $this->root );
        add_filter( 'dt_magic_url_register_types', [ $this, 'register_type' ], 10, 1 );

        // register REST and REST access
        add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

        // stop processing for non magic urls
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }

        // fail to blank if not valid url
        $this->parts = $this->magic->parse_url_parts();
        if ( ! $this->parts ){
            // @note this returns a blank page for bad url, instead of redirecting to login
            add_filter( 'dt_templates_for_urls', function ( $template_for_url ) {
                $url = dt_get_url_path();
                $template_for_url[ $url ] = 'template-blank.php';
                return $template_for_url;
            }, 199, 1 );
            add_filter( 'dt_blank_access', function(){ return true;
            } );
            add_filter( 'dt_allow_non_login_access', function(){ return true;
            }, 100, 1 );
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
            add_action( 'dt_blank_body', [ $this, 'form_body' ] );
        }
        else if ( 'access_account' === $this->parts['action'] ) {
            add_action( 'dt_blank_body', [ $this, 'access_account_body' ] );
        }
        else {
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

    public function fail_register( $template_for_url ) {
        $url = dt_get_url_path();
        $template_for_url[ $url ] = 'template-blank.php';
        return $template_for_url;
    }

    public function dt_details_additional_tiles( $tiles, $post_type = "" ){
        if ( $post_type === 'campaigns' && ! isset( $tiles["apps"] ) ){
            $tiles["apps"] = [ "label" => __( "Campaign Subscription Form Links", 'disciple-tools-campaigns' ) ];
        }
        return $tiles;
    }

    public function dt_details_additional_section( $section, $post_type ) {
        // test if campaigns post type and campaigns_app_module enabled
        if ( $post_type === $this->post_type ) {

            if ( 'apps' === $section ) {
                $record = DT_Posts::get_post( $post_type, get_the_ID() );

                if ( isset( $record['type']['key'] ) && '24hour' === $record['type']['key'] ) {
                    if ( isset( $record['public_key'] )) {
                        $key = $record['public_key'];
                    } else {
                        $key = DT_Subscriptions_Base::instance()->create_unique_key();
                        update_post_meta( get_the_ID(), 'public_key', $key );
                    }
                    if ( !isset( $record["start_date"]["timestamp"], $record["end_date"]["timestamp"] ) ){
                        ?>
                        <p>A Start Date and End Date are required for the 24 hour campaign</p>
                        <?php
                        return;
                    }
                    $link = trailingslashit( site_url() ) . $this->root . '/' . $this->type . '/' . $key;
                    ?>
                    <div class="cell">
                        <div class="section-subheader">
                            <?php esc_html_e( '24hour Prayer', 'disciple_tools' ); ?>
                        </div>
                        <a class="button hollow small" onclick="copyToClipboard('<?php echo esc_url( $link ) ?>')"><?php esc_html_e( 'Copy Link', 'disciple_tools' ); ?></a>
                        <a class="button hollow small" onclick="open_app('<?php echo esc_url( $link ) ?>')"><?php esc_html_e( 'Open', 'disciple_tools' ); ?></a>
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

                        function open_app(){
                            jQuery('#modal-large-content').empty().html(`
                            <div class="iframe_container">
                                <span id="campaign-spinner" class="loading-spinner active"></span>
                                <iframe id="campaign-iframe" src="<?php echo esc_url( $link ) ?>" width="100%" height="${window.innerHeight -150}px" style="border:none;">Your browser does not support iframes</iframe>
                            </div>
                            <style>
                            .iframe_container {
                                position: relative;
                            }
                            .iframe_container .loading-spinner {
                                position: absolute;
                                top: 10%;
                                left: 50%;
                            }
                            .iframe_container iframe {
                                background: transparent;
                                z-index: 1;
                            }
                            </style>
                            `)
                            jQuery('#campaign-iframe').on('load', function() {
                                document.getElementById('campaign-spinner').style.display='none';
                            });
                            jQuery('#modal-large').foundation('open')
                        }
                    </script>
                    <?php
                } // end if 24hour prayer
            } // end if apps section
        } // end if campaigns and enabled
    }

    public function dt_campaign_types( $types ) {
        $types['24hour'] = [
            'label' => __( '24hr Prayer Calendar', 'disciple_tools' ),
            'description' => __( 'Cover a region with 24h prayer.', 'disciple_tools' ),
            "visibility" => __( "Collaborators", 'disciple_tools' ),
            'color' => "#4CAF50"
        ];
        return $types;
    }

    public function tile_scripts(){
        if ( is_singular( "campaigns" ) ){
            $magic = new DT_Magic_URL( 'campaigns_app' );
            $types = $magic->list_types();
            $campaigns = $types['campaigns'] ?? [];
            $campaigns['new_key'] = $magic->create_unique_key();

            wp_localize_script( // add object to campaigns-post-type.js
                'dt_campaigns', 'campaigns_campaigns_module', [
                    'campaigns' => $campaigns,
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
                'access_account' => 'Access Account',
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
        $this->campaigns_styles_header();
        $this->campaigns_javascript_header();
    }

    public function form_footer(){
        wp_footer(); // styles controlled by wp_print_styles and wp_print_scripts actions
    }

    public function campaigns_styles_header(){
        ?>
        <style>
            body {
                background-color: white;
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
            }
            .time-cell {
                cursor:pointer;
                padding: 10px 5px 5px 5px;
            }

            .day-selector {
                padding: 10px 5px 5px 5px;
            }
            .day-selector:hover {
                background: lightblue;
                cursor:pointer;
            }
            .progress-bar {
                height:10px;
                background: dodgerblue;
                width:0;
            }
            .no-selection {
                max-width: 100%;
                padding-top: 10px;
                border: 1px solid grey;
                font-size:1.2em;
            }
            .remove-selection {
                /*float: right;*/
                color: red;
                cursor:pointer;
            }
            #email {
                display:none;
            }
            .day-extra {
                text-align: start;
                padding: 2px 5px;
            }


            .type-options {
                display:flex;
                flex-wrap:wrap;
                margin-bottom: 15px;
            }
            .type-option:hover .type-option-border {
                background-color: #ecf5fc;
                border: 1px solid #c2e0ff;
                border-radius:15px;
            }
            .type-option.selected .type-option-border {
                background-color: #ecf5fc;
                border: 1px solid #c2e0ff;
                border-radius:15px;
            }
            .type-option {
                padding-right: 10px;
                display: flex;
                flex-basis: 25%;
                cursor: pointer;
            }
            .type-option .type-option-border {
                padding: 5px 10px;
                border: 1px solid #c2e0ff;
                border-radius:15px;
                width: 100%;
            }
            .type-option input {
                align-self:center;
                margin-right:5px;
                margin-bottom: 0;
            }
            .type-option .type-option-title {
                color:#3f729b;
                font-size: larger;
            }

            .type-option .type-option-rows {
                display: inline-block;
            .type-option .type-option-rows div {
                    display: block;
                    margin-bottom: 5px;
            }
            @media screen and (max-width : 640px) {
                .type-option {
                    flex-basis: 100%;
                    margin-bottom: 1.25em;
                }
            }
        </style>
        <?php
    }

    public static function calendar_subscribe( $campaign_id, $location_id, $current_commitments, $start_timestamp, $end_timestamp ){
        ?>
        <script>
        let calendar_subscribe_object = [<?php echo json_encode([
            'campaign_id' => $campaign_id,
            'campaign_grid_id' => $location_id,
            'current_commitments' => $current_commitments,
            'translations' => [],
            'start_timestamp' => (int) $start_timestamp,
            'end_timestamp' => (int) $end_timestamp + 86400,
        ]) ?>][0]

        //format date to month and day
        let timestamp_to_month_day = (timestamp, timezone = null)=>{
            const options = { month: "long", day: "numeric" };
            if ( timezone ){
                options.timeZone = timezone
            }
            return new Intl.DateTimeFormat("en-US", options).format(
              timestamp * 1000
            );
        }

        //format date to hour:minutes
        let timestamp_to_time = (timestamp, timezone = null)=>{
            const options = { hour: "numeric", minute: "numeric" };
            if ( timezone ){
                options.timeZone = timezone
            }
            return new Intl.DateTimeFormat("en-US", options).format(
              timestamp * 1000
            );
        }

        let day_start_timestamp_utc = ( timestamp ) => {
            let start_of_day = new Date(timestamp*1000)
            start_of_day.setHours(0,0,0,0)
            return start_of_day.getTime()/1000
        }

        let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
        let selected_calendar_color = 'green'


        const number_of_days = ( calendar_subscribe_object.end_timestamp - calendar_subscribe_object.start_timestamp ) / ( 24*3600)
        let time_slot_coverage = {}

        //set up array of days and time slots according to timezone
        let calculate_day_times = function (custom_timezone=null){
            let days = [];

            let time_iterator = calendar_subscribe_object.start_timestamp;
            while ( time_iterator < calendar_subscribe_object.end_timestamp ){
                let day = timestamp_to_month_day( time_iterator, custom_timezone )

                if ( !days.length || day !== days[days.length-1]["formatted"] ){
                    days.push({
                        "key": day_start_timestamp_utc(time_iterator),
                        "formatted": day,
                        "percent": 0,
                        "slots": []
                    })
                }
                let time_formatted = timestamp_to_time(time_iterator, custom_timezone)
                days[days.length-1]["slots"].push({
                    "key": time_iterator,
                    "formatted": time_formatted,
                    "subscribers": parseInt(calendar_subscribe_object.current_commitments[time_iterator] || 0)
                })

                if ( calendar_subscribe_object.current_commitments[time_iterator] ){
                    days[days.length-1].percent += 100 / ( 24 / ( 15/60 ) ) // percent of day of 15 mins

                    if ( !time_slot_coverage[time_formatted]){
                        time_slot_coverage[time_formatted] = 0;
                    }
                    time_slot_coverage[time_formatted]++;
                }
                time_iterator += 15 * 60;
            }
            return days;
        }
        let days = calculate_day_times()



        jQuery(document).ready(function($){
            let selected_times = $('#selected-prayer-times')

            let update_timezone = function (){
                $('.timezone-current').html(current_time_zone)
                $('#selected-time-zone').val(current_time_zone).text(current_time_zone)
            }
            update_timezone()

            let draw_calendar = () => {
                let content = $('#calendar-content')
                content.empty()
                let list = ''
                days.forEach(day=>{
                    list += `<div class="cell day-cell" >
                        <div class="day-selector" data-time="${window.lodash.escape(day.key)}">
                            <div>${window.lodash.escape(day.formatted)} (${window.lodash.escape(parseInt(day.percent))}%)</div>
                            <div class="progress-bar" data-percent="${day.percent}"></div>
                        </div>
                        <div class="day-extra" id=calendar-extra-${window.lodash.escape(day.key)}></div>
                    </div>`
                })

                content.html(`<div class="grid-x" id="selection-grid-wrapper">${list}</div>`)
                jQuery.each( jQuery('.progress-bar'), function(){
                    jQuery(this).animate({
                        width: ( jQuery(this).data('percent') || 0 ) + '%'
                    })
                })

            }
            draw_calendar()
            let populate_select = () => {
                let select = $('#daily_time_select')
                select.empty();
                let select_html = `<option>Select a time</option>`

                let key = 0;
                let start_of_today = new Date()
                start_of_today.setHours(0,0,0,0)
                let start_time_stamp = start_of_today.getTime()/1000
                while ( key < 24 * 3600 ){
                    let time_formatted = timestamp_to_time(start_time_stamp+key)

                    let covered = time_slot_coverage[time_formatted] ? time_slot_coverage[time_formatted] === number_of_days : false;

                    select_html += `<option value="${window.lodash.escape(key)}">
                        ${window.lodash.escape(time_formatted)} ${ covered ? "(Already covered)": '' }
                    </option>`
                    key+=15*60
                }
                select.html(select_html)

            }
            populate_select()

            $('.type-option').on('click', function(){
                let type = $(this).attr('id')
                $('.type-option.selected').removeClass('selected')
                $(this).addClass('selected')
                $(`#${type} input`).prop('checked', true)
                $('.type-description-row').hide()

                if ( type === "type-daily" ){
                    $(`#type-daily-section`).show()
                    $(`#type-individual-section`).hide()
                } else {
                    $(`#type-individual-section`).show()
                    $(`#type-daily-section`).hide()
                }
            })


            // listen for click on day cell
            jQuery(document).on('click', '.day-selector', function(){
                let id = jQuery(this).data('time')
                let list_title = jQuery('#list-modal-title')
                let day=days.find(k=>k.key===id)
                list_title.empty().html(`<h2 class="section_title">${window.lodash.escape(day.formatted)}</h2>`)
                let list_content = jQuery('#list-modal-content')
                let time_cell = ''
                day.slots.forEach(slot=>{
                    let background_color = 'white'
                    if ( slot.subscribers > 0) {
                        background_color = 'lightblue'
                    }
                    if ( slot.selected ) {
                        background_color = selected_calendar_color
                    }
                    time_cell += `<div class="cell day-cell time-cell"
                                style="background-color:${background_color}" id="${slot.key}"
                                data-day=${window.lodash.escape(id)}
                                data-time="${window.lodash.escape(slot.key)}">
                        ${window.lodash.escape(slot.formatted)} (${window.lodash.escape(slot.subscribers)} praying)
                    </div>`
                })
                list_content.empty().html(`<div class="grid-x"> ${time_cell} </div>`)


                jQuery('#list-modal').foundation('open')
            })

            //click on calendar square
            $(document).on("click", '.day-cell.time-cell', function (){
                jQuery('#no-selections').remove()

                let selected_time_id = jQuery(this).data('time')
                let selected_day_id = jQuery(this).data('day')
                let day = days.find(k=>k.key===selected_day_id)
                let slot = day.slots.find(k=>k.key===selected_time_id)
                slot.selected = true
                if( 'rgb(0, 128, 0)' === jQuery(this).css('background-color') ) {
                    jQuery(this).css('background-color', 'white')
                    jQuery('#selected-'+selected_time_id).remove()
                } else {
                    jQuery(this).css('background-color', selected_calendar_color)
                    add_selected(selected_time_id, selected_day_id, day.formatted, slot.formatted)
                }
            })


            //change timezone
            $('#confirm-timezone').on('click', function (){
                current_time_zone = $("#timezone-select").val()
                update_timezone()
                days = calculate_day_times(current_time_zone)
                draw_calendar()
            })

            //create a recurring time slot
            jQuery('#daily_time_select').on( 'change', function (){
                $("#no-selections").remove()
                let slot = $(this).val()
                let label = $("#daily_time_select option:selected").text()

                days.forEach(day=>{
                    let time = parseInt(day.key) + parseInt(slot);
                    if ( time > calendar_subscribe_object.start_timestamp && time < calendar_subscribe_object.end_timestamp ){
                        let slot = day.slots.find(h=>parseInt(h.key)===parseInt(time))
                        if ( slot ){
                            slot.selected = true
                            jQuery(`#${time}`).css('background-color', selected_calendar_color)
                            add_selected( time, day.key, day.formatted, label)
                        }
                    }
                })
                $('#type-daily-section').hide()
                $('.type-option.selected').removeClass('selected')

            })

            // add selection function
            let add_selected = function (time, day, day_label, time_label){
                $("#progress-section").hide()
                $(`#calendar-extra-${day}`).append(`
                    <div id="selected-${window.lodash.escape(time)}" class="selected-hour"
                        data-time="${window.lodash.escape(time)}">
                        ${window.lodash.escape(time_label)}
                        <i class="fi-x remove-selection" data-time="${time}" data-day="${day}"></i>
                    </div>
                `)
                $('#confirmation-section').show()
            }

            //remove selection
            $(document).on("click", '.remove-selection', function (){
                let time_timestamp = $(this).data("time")
                let day_timestamp = $(this).data("day")
                let day = days.find(k=>k.key===day_timestamp)
                let slot = day.slots.find(k=>k.key===time_timestamp)
                slot.selected = false
                $(`#selected-${time_timestamp}`).remove()
            })
        })

        </script>

        <h3 class="type-description-row">Choose an option:</h3>
        <div class="type-options">
            <div class="type-option" id="type-daily">
                <div class="type-option-border">
                    <input type="radio" name="type" value="type-daily" style="display: none">
                    <div class="type-option-rows">
                        <div>
                            <img class="dt-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/list.svg' ) ?>">
                            <strong class="type-option-title">Pray Daily </strong>
                        </div>
                        <div class="type-description-row">
                            <img class="dt-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/visibility.svg' ) ?>"/>
                            (Recommended)
                        </div>
                        <div class="type-description-row">
                            <img class="dt-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                            Pray at the same time each day
                        </div>
                    </div>

                </div>
            </div>
            <div class="type-option" id="type-individual">
                <div class="type-option-border">
                    <input type="radio" name="type" value="type-individual" style="display: none">
                    <div class="type-option-rows">
                        <div>
                            <img class="dt-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/calendar.svg' ) ?>">
                            <strong class="type-option-title">Individual</strong>
                        </div>
                        <div class="type-description-row">
                            <img class="dt-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>"/>
                            Select individual prayer times
                        </div>
                    </div>
                </div>
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
        <div class="cell medium-6">


        </div>

        <div id="type-daily-section" style="display: none">
            <div>
                <label><strong><?php echo esc_html( "Pray every day at:" ); ?></strong><label>
                <select id="daily_time_select"></select>
            </div>
        </div>

        <div id="type-individual-section" style="display: none">
            <p>
                <?php esc_html_e( 'Click on a days and then the time slots you want to pray for.', 'disciple_tools' ); ?>
            </p>
        </div>
        <strong><?php esc_html_e( 'Timezone:', 'disciple_tools' ); ?></strong> (<a href="javascript:void(0)" data-open="timezone-changer" class="timezone-current"></a>)

        <div id="type-individual-section">
            <div class="center">
                <h3 style="display: inline-block"><?php esc_html_e( 'Prayer Calendar', 'disciple_tools' ); ?></h3>
            </div>

            <div class="grid-x" style=" height: inherit !important;">
                <div class="cell center" id="bottom-spinner"><span class="loading-spinner"></span></div>
                <div class="cell" id="calendar-content"><div class="center">... <?php esc_html_e( 'loading', 'disciple_tools' ); ?></div></div>
                <div class="cell grid" id="error"></div>
            </div>
        </div>
        <br>
        <div id="confirm-selection-section" style="display: none">
            <span id="selection-error" class="form-error">
                <?php esc_html_e( 'You must select at least one time slot above.', 'disciple_tools' ); ?>
            </span>
            <div id="selected-prayer-times" class="grid-x grid-padding-x grid-padding-y">
                <div class="cell no-selection" id="no-selections"><?php esc_html_e( 'No Selections', 'disciple_tools' ); ?></div>
            </div>
        </div>
        <div class="reveal small" id="list-modal"  data-v-offset="0" data-reveal>
            <h3 id="list-modal-title"></h3>
            <div id="list-modal-content"></div>
            <br>
            <div class="center">
                <button class="button hollow large" data-close="" aria-label="Close modal" type="button">
                    <span aria-hidden="true"><?php esc_html_e( 'Close', 'disciple_tools' ); ?></span>
                </button>
            </div>
            <button class="close-button" data-close="" aria-label="Close modal" type="button">
                <?php esc_html_e( 'Close', 'disciple_tools' ); ?> <span aria-hidden="true">×</span>
            </button>
        </div>
        <?php
    }


    public function campaigns_javascript_header(){
        $post = DT_Posts::get_post( 'campaigns', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) ) {
            return $post;
        }
        $grid_id = 1;
        if ( isset( $post['location_grid'] ) && ! empty( $post['location_grid'] ) ) {
            $grid_id = $post['location_grid'][0]['id'];
        }
        ?>
        <script>
            let postObject = [<?php echo json_encode([
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'campaign_id' => $post['ID'],
                'campaign_grid_id' => $grid_id,
                'translations' => []
            ]) ?>][0]

            /* FUNCTIONS */
            window.create_subscription = () => {
                let spinner = $('.loading-spinner')
                spinner.addClass('active')
                let submit_button = jQuery('#submit-form')
                submit_button.prop('disabled', true)

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
                    spinner.hide()
                    name_input.focus(function(){
                        jQuery('#name-error').hide()
                    })
                    submit_button.prop('disabled', false)
                    return;
                }

                let email_input = jQuery('#e2')
                let email = email_input.val()
                if ( ! email ) {
                    jQuery('#email-error').show()
                    spinner.hide()
                    email_input.focus(function(){
                        jQuery('#email-error').hide()
                    })
                    submit_button.prop('disabled', false)
                    return;
                }

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
                    jQuery.each(selected_times_divs, function(){
                        selected_times.push({ time: jQuery(this).data('time') } )
                    })
                }

                let data = {
                    name: name,
                    email: email,
                    selected_times: selected_times,
                    campaign_id: postObject.campaign_id,
                    timezone: current_time_zone
                }

                let wrapper = jQuery('#wrapper')
                wrapper.empty().html(`<div class="grid-x"><div class="cell center"><span class="loading-spinner active"></span></div></div>`)

                let alert = `
                    <div class="grid-x">
                        <div class="cell center"><h2>Sent! Check your email.
                        <br><br>
                        Click on the link included in the email to <strong>verify</strong> your commitment and receive prayer time notifications!</h2></div>
                    </div>
                `

                jQuery.ajax({
                    type: "POST",
                    data: JSON.stringify(data),
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    url: postObject.root + postObject.parts.root + '/v1/' + postObject.parts.type,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', postObject.nonce )
                    }
                })
                    .done(function(){
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
            }


            jQuery(document).ready(function(){
                jQuery('#submit-form').on('click', function(){
                    window.create_subscription()
                })
                clearInterval(window.fiveMinuteTimer)
            })
        </script>
        <?php
        return true;
    }

    public function form_body(){
        $link = trailingslashit( site_url() ) . $this->parts['root'] . '/' . $this->parts['type'] . '/' . $this->parts['public_key'] . '/access_account';
        $post_id = $this->parts['post_id'];
        $record = DT_Posts::get_post( "campaigns", $post_id, true, false );
        if ( is_wp_error( $record ) ){
            return;
        }
        $coverage_percentage = DT_Campaigns_Base::query_coverage_percentage( $post_id );
        $number_of_time_slots = DT_Campaigns_Base::query_coverage_total_time_slots( $post_id );
        $description = "Campaign Description";
        if ( isset( $record["description"] ) && !empty( $record["description"] ) ){
            $description = $record["description"];
        }
        // FORM BODY
        ?>
        <div id="custom-style"></div>
        <div id="wrapper">


            <span class="hide-for-small-only" style="position:absolute; right:10px;"><a href="<?php echo esc_url( $link ) ?>">Already have a commitment?</a></span>
            <div class="center" style="margin-bottom:20px">
                <h2><?php echo esc_html( $description ); ?></h2>
            </div>
            <div id="progress-section">
                <div class="progress-bar-container" style="border: 1px solid black" >
                    <div class="coverage-progress-bar" data-percent="<?php echo esc_html( $coverage_percentage ); ?>" style="background: dodgerblue; width:0; height:20px"></div>
                </div>
                <p>We have covered <strong><?php echo esc_html( $coverage_percentage ); ?>%</strong> of the <?php echo esc_html( $number_of_time_slots ); ?> time slots needed to cover the region in 24 hour prayer.</p>
            </div>

            <div class="center show-for-small-only"><a href="<?php echo esc_url( $link ) ?>">Already have a commitment?</a></div>


            <?php
            $post = DT_Posts::get_post( 'campaigns', $this->parts['post_id'], true, false );
            if ( is_wp_error( $post ) ) {
                return $post;
            }
            $grid_id = 1;
            if ( isset( $post['location_grid'] ) && ! empty( $post['location_grid'] ) ) {
                $grid_id = $post['location_grid'][0]['id'];
            }
            $current_commitments = DT_Time_Utilities::subscribed_times_list( $this->parts['post_id'] );
            $this->calendar_subscribe( $this->parts['post_id'], $grid_id, $current_commitments, $post['start_date']['timestamp'] ?? time(), $post['end_date']['timestamp'] ?? time() ) ?>

            <br>

            <div id="confirmation-section" style="display: none">
                <div class="center"><h2><?php echo esc_html( "Contact Information" ); ?></h2></div>
                <div class="grid-x ">
                    <div class="cell">
                        <span id="name-error" class="form-error">
                            <?php echo esc_html( "You're name is required." ); ?>
                        </span>
                        <label for="name"><?php esc_html_e( 'Name', 'disciple_tools' ); ?><br>
                            <input type="text" name="name" id="name" placeholder="Name" required/>
                        </label>
                    </div>
                    <div class="cell">
                        <span id="email-error" class="form-error">
                            <?php esc_html_e( "You're email is required.", 'disciple_tools' ); ?>
                        </span>
                        <label for="email"><?php esc_html_e( 'Email', 'disciple_tools' ); ?><br>
                            <input type="email" name="email" id="email" placeholder="Email" />
                            <input type="email" name="e2" id="e2" placeholder="Email" required />
                        </label>
                    </div>
                </div>


                <div class="grid-x grid-padding-x grid-padding-y">
                    <div class="cell center">
                        <input type="checkbox" id="receive_campaign_notifications" name="receive_campaign_notifications" checked />
                        <label for="receive_campaign_notifications"><?php esc_html_e( 'Receive Prayer Time Notifications', 'disciple_tools' ); ?></label>
                        <input type="checkbox" id="receive_campaign_emails" name="receive_campaign_emails" checked />
                        <label for="receive_campaign_emails"><?php esc_html_e( 'Receive Prayer Emails', 'disciple_tools' ); ?></label>
                    </div>
                    <div class="cell center">
                        <button class="button large" id="submit-form"><?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple_tools' ); ?></button>
                    </div>
                </div>
            </div>

        </div> <!-- form wrapper -->
        <script>
            let coverage_bar = jQuery('.coverage-progress-bar')
            coverage_bar.animate({
                width: ( coverage_bar.data('percent') || 0 ) + '%'
            })
        </script>
        <?php
    }

    public function access_account_body(){
        ?>
        <style>
            #email {
                display:none;
            }
        </style>
        <div id="custom-style"></div>
        <div id="wrapper">
            <div class="center"><h2><?php esc_html_e( "We'll Email You a Link to Your Account", 'disciple_tools' ); ?></h2></div>
            <div class="grid-x ">
                <div class="cell">
                    <span id="email-error" class="form-error">
                        <?php esc_html_e( "You're email is required.", 'disciple_tools' ); ?>
                    </span>
                    <label for="email"><?php esc_html_e( 'Email', 'disciple_tools' ); ?><br>
                        <input type="email" name="email" id="email" placeholder="Email" />
                        <input type="email" name="e2" id="e2" placeholder="Email" required />
                    </label>
                </div>
                <div class="cell center">
                    <button class="button large" id="send-link-form"><?php esc_html_e( 'Send me a link to my prayer times', 'disciple_tools' ); ?></button>
                </div>
            </div>
        </div> <!-- form wrapper -->
        <script>
            jQuery(document).ready(function($){

                jQuery('#send-link-form').on('click', function(){
                    let spinner = jQuery('.loading-spinner')
                    spinner.addClass('active')

                    let submit_button = jQuery('#send-link-form')
                    submit_button.prop('disabled', true)

                    let honey = jQuery('#email').val()
                    if ( honey ) {
                        jQuery('#next_1').html('Shame, shame, shame. We know your name ... ROBOT!').prop('disabled', true )
                        window.spinner.hide()
                        return;
                    }

                    let email_input = jQuery('#e2')
                    let email = email_input.val()
                    if ( ! email ) {
                        jQuery('#email-error').show()
                        spinner.hide()
                        email_input.focus(function(){
                            jQuery('#email-error').hide()
                        })
                        submit_button.prop('disabled', false)
                        return;
                    }

                    let data = {
                        'email': email,
                        'campaign_id': postObject.campaign_id
                    }

                    let wrapper = jQuery('#wrapper')
                    wrapper.empty().html(`<div class="grid-x"><div class="cell center"><span class="loading-spinner active"></span></div></div>`)

                    let alert = `
                    <div class="grid-x">
                        <div class="cell center">
                            <h2><?php esc_html_e( 'Sent! Check your email.', 'disciple_tools' ); ?>
                                <br><br>
                                <?php esc_html_e( "If your address is in our system, you'll get an email with a link to your prayer commitments.", 'disciple_tools' ); ?>
                            </h2>
                        </div>
                    </div>
                    `
                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: postObject.root + postObject.parts.root + '/v1/' + postObject.parts.type + '/access_account',
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', postObject.nonce )
                        }
                    })
                        .done(function(data){
                            console.log(data)
                            wrapper.empty().html(alert)
                            spinner.removeClass('active')
                        })
                        .fail(function(e) {
                            console.log(e)
                            $('#error').html(e)
                            spinner.removeClass('active')
                        })
                })
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
                    'callback' => [ $this, 'create_subscription' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'.$this->type . '/access_account', [
                [
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => [ $this, 'access_account' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function create_subscription( WP_REST_Request $request ) {
        $params = $request->get_params();

        // create
        if ( ! isset( $params['email'] ) || empty( $params['email'] ) ) {
            return new WP_Error( __METHOD__, "Missing email", [ 'status' => 400 ] );
        }
        if ( ! isset( $params['selected_times'] ) || empty( $params['selected_times'] ) ) {
            return new WP_Error( __METHOD__, "Missing times and locations", [ 'status' => 400 ] );
        }
        if ( ! isset( $params['timezone'] ) || empty( $params['timezone'] ) ) {
            return new WP_Error( __METHOD__, "Missing timezone", [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $email = $params['email'];
        $title = $params['name'];
        if ( empty( $title ) ) {
            $title = $email;
        }

        $user = wp_get_current_user();
        $user->add_cap( 'create_subscriptions' );

        $hash = dt_create_unique_key();

        $fields = [
            'title' => $title,
            "contact_email" => [
                [ "value" => $email ],
            ],
            'campaigns' => [
                "values" => [
                    [ "value" => $params['campaign_id'] ],
                ],
            ],
            'timezone' => $params["timezone"],
            'public_key' => $hash,
        ];

        // create post

        $new_id = DT_Posts::create_post( 'subscriptions', $fields, true );
        if ( is_wp_error( $new_id ) ) {
            return $new_id;
        }

        $campaign = DT_Posts::get_post( 'campaigns', $params['campaign_id'], true, false );
        if ( is_wp_error( $campaign ) ){
            return new WP_Error( __METHOD__, "Sorry, Something went wrong", [ 'status' => 400 ] );
        }
        $campaign_grid_id = isset( $campaign["location_grid"][0]['id'] ) ? $campaign["location_grid"][0]['id'] : null;

        // log reports
        foreach ( $params['selected_times'] as $time ){
            if ( !isset( $time["time"] ) ){
                continue;
            }
            $location_id = isset( $time['grid_id'] ) ? $time['grid_id'] : $campaign_grid_id;
            $args = [
                'parent_id' => $params['campaign_id'],
                'post_id' => $new_id['ID'],
                'post_type' => 'subscriptions',
                'type' => $this->root,
                'subtype' => $this->type,
                'payload' => null,
                'value' => 0,
                'lng' => null,
                'lat' => null,
                'level' => null,
                'label' => null,
                'grid_id' => $location_id,
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

        $email_sent = DT_Prayer_Campaigns_Send_Email::send_registration( $new_id['ID'] );

        if ( !$email_sent ){
            return new WP_Error( __METHOD__, "Could not sent email confirmation", [ 'status' => 400 ] );
        }

        return $hash;
    }

    public function access_account( WP_REST_Request $request ) {
        $params = $request->get_params();

        // @todo insert email reset link
        $params = dt_recursive_sanitize_array( $params );
        if ( ! isset( $params['email'], $params['campaign_id'] ) ) {
            return new WP_Error( __METHOD__, "Missing required parameter.", [ 'status' => 400 ] );
        }

        DT_Prayer_Campaigns_Send_Email::send_account_access( $params['campaign_id'], $params['email'] );

        return $params;
    }
}
