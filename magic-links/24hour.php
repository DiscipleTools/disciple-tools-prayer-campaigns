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
                "campaign_duration" => __( 'Everyday from %1$s to %2$s', "disciple-tools-prayer-campaigns" ),
                "select_a_time" => __( 'Select a time', 'disciple-tools-prayer-campaigns' ),
                "fully_covered_once" => __( 'fully covered once', 'disciple-tools-prayer-campaigns' ),
                "fully_covered_x_times" => __( 'fully covered %1$s times', 'disciple-tools-prayer-campaigns' ),
                "time_slot_label" => _x( '%1$s for %2$s minutes.', "Monday 5pm for 15 minutes", 'disciple-tools-prayer-campaigns' ),
                "going_for_twice" => __( 'All of the %1$s time slots are covered in once prayer once. Help us cover them twice!', 'disciple-tools-prayer-campaigns' ),
                "invitation" => __( '%1$s people praying %2$s minutes everyday day will cover the region in 24h prayer.', 'disciple-tools-prayer-campaigns' ),
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
        #cp-wrapper .cp-center {
            text-align: center;
        }

        #cp-wrapper .day-cell {
            /*flex-basis: 14%;*/
            text-align: center;
            flex-grow: 0;

        }
        #cp-wrapper .display-day-cell {
            height: 40px;
        }
        #cp-wrapper .disabled-calendar-day {
            width:40px;
            height:40px;
            vertical-align: top;
            padding-top:10px;
            color: grey;
        }
        #cp-wrapper .calendar {
            display: flex;
            flex-wrap: wrap;
            width: 300px;
            margin: auto
        }
        #cp-wrapper .month-title {
            text-align: center;
            margin-bottom: 0;
        }
        #cp-wrapper .week-day {
            height: 20px;
            width:40px;
            color: grey;
        }
        #cp-wrapper #calendar-content h3 {
            margin-bottom: 0;
        }

        #cp-wrapper #email {
            display:none;
        }

        #cp-wrapper .day-in-select-calendar {
            color: black;
            display: inline-block;
            height: 40px;
            width: 40px;
            line-height: 0;
            vertical-align: middle;
            text-align: center;
            padding-top: 18px;
        }
        #cp-wrapper .selected-day {
            background-color: dodgerblue;
            color: white;
            border-radius: 50%;
            border: 2px solid;
        }

        #cp-wrapper .success-confirmation-section {
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
            max-width: 400px;
        }
        #cp-wrapper input {
            font-size: 1rem;
            line-height: 1rem;
            color: black;
            border: 1px solid black;
        }
        #cp-wrapper .cp-input {
            min-width: 250px;
            max-width: 400px;
            margin: auto;
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
            <h2 id="campaign-description" class="cp-center"><span><?php esc_html_e( 'Loading prayer campaign data...', 'disciple-tools-prayer-campaigns' ); ?><img src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/></span></h2>

            <!-- coverage tag -->
            <p id="coverage-level" class="cp-center"></p>
            <!--main percentage circle-->
            <div id="main-progress" class="cp-center">
                <div class="cp-center" style="margin:auto; background-color: #ededed; border-radius: 20px; height: 150px; width: 150px"></div>
            </div>
            <!--pray button-->
            <div class="cp-center">
                <button class="button cp-nav" id="open-select-times-button" data-open="cp-times-choose" data-force-scroll="true">
                    <?php esc_html_e( 'Pray With Us', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
            </div>

            <!--start and end dates-->
            <p id="cp-start-end" class="cp-center"></p>
            <div style="display: flex; flex-flow: wrap; justify-content: space-evenly; margin: 10px 0 10px 0">
                <div id="calendar-content"></div>
            </div>
            <!--time zone selector-->
            <p class="cp-center">
                <?php esc_html_e( 'Showing times for:', 'disciple-tools-prayer-campaigns' ); ?> <a href="javascript:void(0)" data-open="cp-timezone-changer" data-force-scroll="true" class="timezone-current cp-nav"></a>
            </p>

        </div>

        <!-- Daily or Individual-->
        <div id="cp-times-choose" class="cp-view" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-main-page">
                <span aria-hidden="true"> <?php esc_html_e( '< back', 'disciple-tools-prayer-campaigns' ); ?> </span>
            </button>
            <h2 class="cp-center"><?php esc_html_e( 'Select an option', 'disciple-tools-prayer-campaigns' ); ?></h2>
            <div style="display: flex; flex-wrap:wrap; justify-content: space-around; margin-top: 20px">
                <div style="margin-bottom: 20px" class="cp-center">
                    <button class="cp-nav" data-open="cp-daily-prayer-time"><?php esc_html_e( 'Pray every day at the same time', 'disciple-tools-prayer-campaigns' ); ?></button>
                    <br>
                    <p><?php esc_html_e( 'Example: Everyday at 4pm for 15mins', 'disciple-tools-prayer-campaigns' ); ?></p>
                </div>
                <div class="cp-center">
                    <button class="cp-nav" data-open="cp-choose-individual-times"><?php esc_html_e( 'Select days and times to pray', 'disciple-tools-prayer-campaigns' ); ?></button>
                    <br>
                    <p><?php esc_html_e( 'Example: Monday 12th at 6pm for 15mins', 'disciple-tools-prayer-campaigns' ); ?></p>
                </div>
            </div>

        </div>


        <!-- Daily time select -->
        <div id="cp-daily-prayer-time" class="cp-view" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-times-choose">
                <span aria-hidden="true"> <?php esc_html_e( '< back', 'disciple-tools-prayer-campaigns' ); ?> </span>
            </button>

            <div style="display: flex; flex-wrap:wrap; justify-content: space-around;">
                <div>
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
                        <?php esc_html_e( 'Showing times for:', 'disciple-tools-prayer-campaigns' ); ?> <a href="javascript:void(0)" data-open="cp-timezone-changer" data-force-scroll="true" class="timezone-current cp-nav"></a>
                    </p>
                    <button style="margin-top:10px" disabled id="cp-confirm-daily-times" class="cp-nav" data-open="cp-view-confirm" data-force-scroll="true" data-back-to="cp-daily-prayer-time"><?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?></button>
                </div>
            </div>
        </div>

        <!-- individual prayer times -->
        <div id="cp-choose-individual-times" class="cp-view" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-times-choose">
                <span aria-hidden="true"> <?php esc_html_e( '< back', 'disciple-tools-prayer-campaigns' ); ?> </span>
            </button>
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
                    <button id="cp-add-prayer-time" data-day="" disabled style="margin: 10px 0; display: inline-block"><?php esc_html_e( 'Add prayer time', 'disciple-tools-prayer-campaigns' ); ?></button>
                    <span style="display: none" id="cp-time-added"><?php esc_html_e( 'Time added', 'disciple-tools-prayer-campaigns' ); ?></span>
                </div>
                <p>
                    <?php esc_html_e( 'Showing times for:', 'disciple-tools-prayer-campaigns' ); ?> <a href="javascript:void(0)" data-open="cp-timezone-changer" data-force-scroll="true" class="timezone-current cp-nav"></a>
                </p>

                <div style="margin: 30px 0">
                    <h3><?php esc_html_e( 'Selected Times', 'disciple-tools-prayer-campaigns' ); ?></h3>
                    <ul class="cp-display-selected-times"></ul>
                </div>
                <button style="margin-top:10px" disabled id="cp-confirm-individual-times" class="cp-nav" data-open="cp-view-confirm" data-force-scroll="true" data-back-to="cp-choose-individual-times"><?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?></button>
            </div>
        </div>

        <!-- confirm email -->
        <div id="cp-view-confirm" class="cp-view cp-center" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-times-choose">
                <span aria-hidden="true"> <?php esc_html_e( '< back', 'disciple-tools-prayer-campaigns' ); ?> </span>
            </button>
            <h2><?php esc_html_e( 'Confirm', 'disciple-tools-prayer-campaigns' ); ?></h2>
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
                <label for="name"><?php esc_html_e( 'Contact Name', 'disciple-tools-prayer-campaigns' ); ?><br>
                    <input class="cp-input" type="text" name="name" id="name" placeholder="<?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?>" required/>
                </label>
            </div>
            <div>
                <span id="email-error" class="form-error">
                    <?php esc_html_e( "You're email is required.", 'disciple-tools-prayer-campaigns' ); ?>
                </span>
                <label for="email"><?php esc_html_e( 'Contact Email', 'disciple-tools-prayer-campaigns' ); ?><br>
                    <input class="cp-input" type="email" name="email" id="email" placeholder="<?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>" />
                    <input class="cp-input" type="email" name="e2" id="e2" placeholder="<?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>" required />
                </label>
            </div>
            <div>
                <p>
                    <label for="receive_prayer_time_notifications">
                        <input type="checkbox" id="receive_prayer_time_notifications" name="receive_prayer_time_notifications" checked />
                        <?php esc_html_e( 'Receive Prayer Time Notifications (email verification needed).', 'disciple-tools-prayer-campaigns' ); ?>
                    </label>
                </p>
                <div>
                    <button class="button loader" id="cp-submit-form">
                        <?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple-tools-prayer-campaigns' ); ?> <img id="cp-submit-form-spinner" style="display: none" src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/></button>
                </div>
            </div>

            <div class="success-confirmation-section">
                <div class="cell center">
                    <h2><?php esc_html_e( 'Sent! Check your email.', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    <p>
                        &#9993; <?php esc_html_e( 'Click on the link included in the email to verify your commitment and receive prayer time notifications!', 'disciple-tools-prayer-campaigns' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( 'In the email is a link to manage your prayer times.', 'disciple-tools-prayer-campaigns' ); ?>
                    </p>
                    <p>
                        <button class="cp-nav" data-open="cp-main-page">OK</button>
                    </p>
                </div>
            </div>

            <div id="confirmation-times" style="margin-top: 40px">
                <h3><?php esc_html_e( 'Selected Times', 'disciple-tools-prayer-campaigns' ); ?></h3>
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
                <?php echo esc_html__( 'Cancel', 'disciple-tools-prayer-campaigns' )?>
            </button>
            <button class="button cp-nav" type="button" id="confirm-timezone" data-open="cp-main-page">
                <?php echo esc_html__( 'Select', 'disciple-tools-prayer-campaigns' )?>
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
