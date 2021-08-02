<?php

function dt_24hour_campaign_register_scripts( $atts ){
    //campaigns core js
    wp_enqueue_script( 'dt_campaign_core', trailingslashit( plugin_dir_url( __FILE__ ) ) . '../post-type/campaign_core.js', [
        'jquery',
        'lodash'
    ], filemtime( plugin_dir_path( __FILE__ ) . '../post-type/campaign_core.js' ), true );

    //24 hour campaign js
    wp_enqueue_script( 'dt_campaign', trailingslashit( plugin_dir_url( __FILE__ ) ) . '24hour.js', array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . '24hour.js' ), true );
    wp_localize_script(
        'dt_campaign', 'campaign_objects', [
            'translations' => [
                "Sample API Call" => __( "Sample API Call" )
            ],
            "parts" => [
                "root" => $atts['root'],
                "type" => $atts['type'],
                "public_key" => $atts['public_key'],
                "meta_key" => $atts['meta_key'],
                "post_id" => $atts["post_id"],
            ],
            "root" => $atts["rest_url"]
        ]
    );
}

function dt_24hour_campaign_body(){
    ?>

    <style>
        #cp-wrapper {
            max-width:1000px;
            margin: 10px auto;
            padding: 2em .5em;
            background-color: white;
            border-radius: 5px;
            min-height: 800px;
        }
        .cp-center {
            text-align: center;
            margin-bottom: 20px;
        }


        .day-cell {
            /*flex-basis: 14%;*/
            text-align: center;
            flex-grow: 0;

        }
        .display-day-cell {
            height: 40px;
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

        #email {
            display:none;
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

        .calendar-month-title {
            display: block;
            padding:0 10px;
        }
        .small-view .calendar-month-title {
            /*display: inline-block;*/
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
            margin-top: 20px;
        }

        #cp-wrapper .cp-close-button {
            position: relative;
            top: .5rem;
            font-size: 1em;
            line-height: 1;
            background-color: transparent !important;
            color: black !important;
            display: block;
        }
        #cp-wrapper select {
            font-size: 1rem;
            line-height: 1rem;
            color: black;
            border: 1px solid black ;
            display: block;
            min-width: 250px;
            max-width: 400px
        }
        #cp-wrapper input {
            font-size: 1rem;
            line-height: 1rem;
            color: black;
            border: 1px solid black ;
        }
        #cp-wrapper .cp-input {
            min-width: 250px;
        }
        #cp-wrapper {
            font-size: 1rem;
        }
        #cp-wrapper h3 {
            font-size: 1.5rem;
        }
        #cp-wrapper h2 {
            font-size: 1.7rem;
        }
        #cp-wrapper h1, #cp-wrapper h2, #cp-wrapper h3 {
            color: black;
        }
        #cp-wrapper label {
            font-size: 1rem;
        }

        #cp-wrapper p {
            margin: 10px;
        }
        #cp-wrapper.loading-content h2, #cp-wrapper.loading-content p {
            background-color: #ededed;
            border-radius: 100px;
            min-width: 100px;
            min-height: 20px;
            margin-bottom: 10px;
        }

        #cp-wrapper button {
            background-color: #366184;
            color: #fefefe;
            font-size: 1rem;
            border-radius: 5px;
            border: 1px solid transparent;
            font-weight: normal;
            padding: .85em 1em;
        }
        #cp-wrapper button[disabled] {
            opacity: .25;
            cursor: not-allowed;
        }

        #cp-wrapper .cp-day-select {
            background-color: transparent;
            padding: 2px 10px;
            color:black;
        }
        #cp-wrapper .cp-day-select.active {
            background-color: red;
        }

        #cp-wrapper .form-error {
            display: none;
            color: red;
        }

        #cp-wrapper .cp-display-selected-times {
            list-style-type: none;
            padding: 0;
        }

    </style>

    <div id="cp-wrapper" class="loading-content">
        <div id="cp-main-page" class="cp-view">
            <!--title-->
            <h2 id="campaign-description" class="cp-center"><span>Loading prayer campaign data...<img src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/></span></h2>

            <!-- coverage tag -->
            <p id="coverage-level" class="cp-center"></p>
            <!--main percentage circle-->
            <div id="main-progress" class="cp-center">
                <div class="cp-center" style="margin:auto; background-color: #ededed; border-radius: 20px; height: 150px; width: 150px"></div>
            </div>
            <!--pray button-->
            <div class="cp-center">
                <button class="button cp-nav" id="open-select-times-button" data-open="cp-times-choose" data-force-scroll="true" style="margin-top: 10px">
                    Pray With Us
                </button>
            </div>

            <!--start and end dates-->
            <p id="cp-start-end" class="cp-center"></p>
            <div style="display: flex; flex-flow: wrap; justify-content: space-evenly; margin: 10px 0 10px 0">
                <div id="calendar-content"></div>
            </div>
            <!--time zone selector-->
            <p class="cp-center">
                <?php esc_html_e( 'Showing times for: ', 'disciple_tools' ); ?><a href="javascript:void(0)" data-open="cp-timezone-changer" data-force-scroll="true" class="timezone-current cp-nav"></a>
            </p>

        </div>

        <!-- Daily or Individual-->
        <div id="cp-times-choose" class="cp-view" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-main-page">
                <span aria-hidden="true"> < back </span>
            </button>
            <h2 class="cp-center">Select an option</h2>
            <div style="display: flex; flex-wrap:wrap; justify-content: space-around; margin-top: 20px">
                <div style="margin-bottom: 20px" class="cp-center">
                    <button class="cp-nav" data-open="cp-daily-prayer-time">Pray every day at the same time</button>
                    <br>
                    <p>Example: Everyday at 4pm for 15mins</p>
                </div>
                <div class="cp-center">
                    <button class="cp-nav" data-open="cp-choose-individual-times">Select days and times to pray</button>
                    <br>
                    <p>Example: Monday 12th at 6pm for 15mins</p>
                </div>
            </div>

        </div>


        <!-- Daily time select -->
        <div id="cp-daily-prayer-time" class="cp-view" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-times-choose">
                <span aria-hidden="true"> < back </span>
            </button>

            <div style="display: flex; flex-wrap:wrap; justify-content: space-around;">
                <div>
                    <label>
                        <strong>Prayer Time</strong>
                        <select id="cp-daily-time-select">
                            <option>Daily Time</option>
                        </select>
                    </label>
                    <label>
                        <strong>For</strong>
                        <select id="cp-prayer-time-duration-select">
                            <option value="15">15 Minutes</option>
                            <option value="30">30 Minutes</option>
                            <option value="45">45 Minutes</option>
                            <option value="60">1 Hour</option>
                            <option value="90">1 Hour 30 Minutes</option>
                            <option value="120">2 Hours</option>
                        </select>
                    </label>
                    <p>
                        <?php esc_html_e( 'Showing times for: ', 'disciple_tools' ); ?><a href="javascript:void(0)" data-open="cp-timezone-changer" data-force-scroll="true" class="timezone-current cp-nav"></a>
                    </p>
                    <button style="margin-top:10px" disabled id="cp-confirm-daily-times" class="cp-nav" data-open="cp-view-confirm" data-force-scroll="true" data-back-to="cp-daily-prayer-time">Confirm Times</button>
                </div>
            </div>
        </div>

        <!-- individual prayer times -->
        <div id="cp-choose-individual-times" class="cp-view" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-times-choose">
                <span aria-hidden="true"> < back </span>
            </button>
            <h2 id="individual-day-title" class="cp-center">
                Select a day and choose a time
            </h2>
            <div id="cp-day-content" class="cp-center" >
                <div style="margin-bottom: 20px">
                    <div id="day-select-calendar" class="">Modal Calendar</div>
                </div>
                <label>
                    <strong>Select a prayer time</strong>
                    <select id="cp-individual-time-select" disabled style="margin: auto">
                        <option>Daily Time</option>
                    </select>
                </label>
                <label>
                    <strong>For how long</strong>
                    <select id="cp-individual-prayer-time-duration-select" style="margin: auto">
                        <option value="15">15 Minutes</option>
                        <option value="30">30 Minutes</option>
                        <option value="45">45 Minutes</option>
                        <option value="60">1 Hour</option>
                        <option value="90">1 Hour 30 Minutes</option>
                        <option value="120">2 Hours</option>
                    </select>
                </label>
                <div>
                    <button id="cp-add-prayer-time" data-day="" disabled style="margin: 10px 0; display: inline-block">Add prayer time</button>
                    <span style="display: none" id="cp-time-added">Time added</span>
                </div>
                <p>
                    <?php esc_html_e( 'Showing times for: ', 'disciple_tools' ); ?><a href="javascript:void(0)" data-open="cp-timezone-changer" data-force-scroll="true" class="timezone-current cp-nav"></a>
                </p>

                <div style="margin: 30px 0">
                    <h3>Selected Times</h3>
                    <ul class="cp-display-selected-times"></ul>
                </div>
                <button style="margin-top:10px" disabled id="cp-confirm-individual-times" class="cp-nav" data-open="cp-view-confirm" data-force-scroll="true" data-back-to="cp-choose-individual-times">Confirm Times</button>
            </div>
        </div>

        <!-- confirm email -->
        <div id="cp-view-confirm" class="cp-view cp-center" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-times-choose">
                <span aria-hidden="true"> < back </span>
            </button>
            <h2>Confirm</h2>
            <br>
            <!-- @todo summary -->
            <!--            <p>-->
            <!--                You are signing up to praying for-->
            <!--                <span id="time-duration-confirmation" style="font-weight: bold" ></span>-->
            <!--                on each selected day at <span id="time-confirmation" style="font-weight: bold"></span>-->
            <!--                in <span class="timezone-current"></span> timezone.-->
            <!--            </p>-->

            <div>
                <span id="name-error" class="form-error">
                    <?php echo esc_html( "You're name is required." ); ?>
                </span>
                <label for="name"><?php esc_html_e( 'Contact Name', 'disciple_tools' ); ?><br>
                    <input class="cp-input" type="text" name="name" id="name" placeholder="Name" required/>
                </label>
            </div>
            <div>
                <span id="email-error" class="form-error">
                    <?php esc_html_e( "You're email is required.", 'disciple_tools' ); ?>
                </span>
                <label for="email"><?php esc_html_e( 'Contact Email', 'disciple_tools' ); ?><br>
                    <input class="cp-input" type="email" name="email" id="email" placeholder="Email" />
                    <input class="cp-input" type="email" name="e2" id="e2" placeholder="Email" required />
                </label>
            </div>
            <div>
                <p>
                    <label for="receive_prayer_time_notifications">
                        <input type="checkbox" id="receive_prayer_time_notifications" name="receive_prayer_time_notifications" checked />
                        <?php esc_html_e( 'Receive Prayer Time Notifications (email verification needed).', 'disciple_tools' ); ?>
                    </label>
                </p>
                <div>
                    <button class="button loader" id="cp-submit-form">
                        <?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple_tools' ); ?> <img id="cp-submit-form-spinner" style="display: none" src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/></button>
                </div>
            </div>

            <div class="success-confirmation-section">
                <div class="cell center">
                    <h2>Sent! Check your email.</h2>
                    <p>
                        &#9993; Click on the link included in the email to <strong>verify</strong> your commitment and receive prayer time notifications!
                    </p>
                    <p>
                        In the email is a link to <strong>manage</strong> your prayer times.
                    </p>
                    <p>
                        <button class="cp-nav" data-open="cp-main-page">OK</button>
                    </p>
                </div>
            </div>

            <div id="confirmation-times" style="margin-top: 40px">
                <h3>Selected Times</h3>
                <ul class="cp-display-selected-times">

                </ul>
            </div>
        </div>
        <div id="cp-timezone-changer" style="display: none" class="cp-center cp-view">
            <h2>Change your timezone:</h2>
            <select id="timezone-select" style="margin: 20px auto">
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

            <button class="button button-cancel clear cp-nav" data-open="cp-main-page" aria-label="Close reveal" type="button">
                <?php echo esc_html__( 'Cancel', 'disciple_tools' )?>
            </button>
            <button class="button cp-nav" type="button" id="confirm-timezone" data-open="cp-main-page">
                <?php echo esc_html__( 'Select', 'disciple_tools' )?>
            </button>
        </div>

    </div> <!-- form wrapper -->
    <?php
}

function dt_24hour_campaign_shortcode( $atts ){

    if ( !isset( $atts["root"], $atts["type"], $atts["public_key"], $atts["meta_key"], $atts["post_id"], $atts["rest_url"] ) ){
        return;
    }

    if ( $atts["type"] !== "24hour" && $atts["root"] !== "campaign_app" ){
        return;
    }

    dt_24hour_campaign_register_scripts( $atts );

    dt_24hour_campaign_body();
}
add_shortcode( 'dt_campaign', 'dt_24hour_campaign_shortcode' );
