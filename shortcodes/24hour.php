<?php

function dt_24hour_campaign_register_scripts( $atts ){

    wp_register_script( 'luxon', 'https://cdn.jsdelivr.net/npm/luxon@2.3.1/build/global/luxon.min.js', false, "2.3.1", true );
    //campaigns core js
    if ( !wp_script_is( 'dt_campaign_core', "registered" ) ){
        wp_register_script( 'dt_campaign_core', trailingslashit( plugin_dir_url( __DIR__ ) ) . 'post-type/campaign_core.js', [
            'jquery',
            'lodash',
            'luxon'
        ], filemtime( plugin_dir_path( __DIR__ ) . 'post-type/campaign_core.js' ), true );
        wp_localize_script( 'dt_campaign_core', 'dt_campaign_core', [ 'color' => $atts["color"] ?? '' ] );
    }

    wp_enqueue_style( 'dt_campaign_style', trailingslashit( plugin_dir_url( __DIR__ ) ) . 'magic-links/24hour/24hour.css', [], filemtime( plugin_dir_path( __DIR__ ) . 'magic-links/24hour/24hour.css' ) );

    //24 hour campaign js
    if ( !wp_script_is( 'dt_campaign' ) ){
        wp_enqueue_script( 'dt_campaign', trailingslashit( plugin_dir_url( __DIR__ ) ) . 'magic-links/24hour/24hour.js', [ 'luxon', 'jquery', 'dt_campaign_core' ], filemtime( plugin_dir_path( __DIR__ ) . 'magic-links/24hour/24hour.js' ), true );
        wp_localize_script(
            'dt_campaign', 'campaign_objects', [
                'translations' => [
                    //"campaign_duration" => __( 'Everyday from %1$s to %2$s', "disciple-tools-prayer-campaigns" ),
                    "select_a_time" => __( 'Select a time', 'disciple-tools-prayer-campaigns' ),
                    "fully_covered_once" => __( 'fully covered once', 'disciple-tools-prayer-campaigns' ),
                    "fully_covered_x_times" => __( 'fully covered %1$s times', 'disciple-tools-prayer-campaigns' ),
                    "time_slot_label" => _x( '%1$s for %2$s minutes.', "Monday 5pm for 15 minutes", 'disciple-tools-prayer-campaigns' ),
                    //"going_for_twice" => __( 'All of the %1$s time slots are covered in once prayer once. Help us cover them twice!', 'disciple-tools-prayer-campaigns' ),
                    //"invitation" => __( '%1$s people praying %2$s minutes everyday day will cover the region in 24h prayer.', 'disciple-tools-prayer-campaigns' ),
                ],
                "parts" => [
                    "root" => $atts['root'],
                    "type" => $atts['type'],
                    "public_key" => $atts['public_key'],
                    "meta_key" => $atts['meta_key'],
                    "post_id" => $atts["post_id"],
                    "lang" => $atts["lang"] ?? "en_US"
                ],
                "root" => get_rest_url(),
                "remote" => $atts["rest_url"] !== get_rest_url(),
            ]
        );
    }
    set_transient( "dt_magic_link_remote_" . $atts["post_id"], $atts["rest_url"], DAY_IN_SECONDS );
}

function dt_24hour_campaign_body( $color = "", $section = "" ){
    if ( empty( $color ) ){
        $color = "dodgerblue";
    }
    ?>

    <style>
        <?php if ( $section === "percentage" ): ?>
        .cp-wrapper.cp-progress-wrapper {
            width: fit-content;
        }
        <?php endif; ?>
        <?php if ( $section === "calendar" ): ?>
        .cp-wrapper.cp-calendar-wrapper {
            width: fit-content;
        }
        <?php endif; ?>
        .cp-wrapper .month-title, .cp-calendar-wrapper .month-title {
            color: <?php echo esc_html( $color ) ?>;
        }
        .cp-wrapper .selected-day {
            background-color: <?php echo esc_html( $color ) ?>;
        }
        .cp-wrapper button {
            background-color: <?php echo esc_html( $color ) ?>;
        }
        .cp-wrapper button:hover {
            background-color: transparent;
            border-color: <?php echo esc_html( $color ) ?>;
            color: <?php echo esc_html( $color ) ?>;
        }

    </style>

    <?php if ( $section === "percentage" || $section === "" ) :?>
    <div class="cp-progress-wrapper cp-wrapper">
        <div id="main-progress" class="cp-center">
            <div class="cp-center" style="margin: 0 auto 10px auto; background-color: #ededed; border-radius: 20px; height: 150px; width: 150px;"></div>
        </div>
        <div style="color: rgba(0,0,0,0.57); text-align: center"><?php esc_html_e( 'Percentage covered in prayer', 'disciple-tools-prayer-campaigns' ); ?></div>
    </div>
    <?php endif; ?>
    <?php if ( $section === "calendar" || $section === "" ) : ?>
    <div class="cp-calendar-wrapper cp-wrapper">
        <div style="display: flex; flex-flow: wrap; justify-content: space-evenly; margin: 0">
            <div id="calendar-content"></div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ( $section === "sign_up" || $section === "" ) : ?>

    <div id="cp-wrapper" class="cp-wrapper loading-content">
        <div id="" class="cp-loading-page cp-view" >
            <?php esc_html_e( 'Loading prayer campaign data...', 'disciple-tools-prayer-campaigns' ); ?><img src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/>
        </div>

        <div id="cp-main-page" class="cp-view" style="display: none">
            <!--pray button-->
            <div class="cp-center">
                <button class="button cp-nav" id="open-select-times-button" data-open="cp-times-choose">
                    <?php esc_html_e( 'Choose Prayer Times', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
            </div>

        </div>

        <!-- Daily or Individual-->
        <div id="cp-times-choose" class="cp-view" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-main-page">
                <img src="<?php echo esc_html( plugin_dir_url( __DIR__ ) . 'assets/back_icon.svg' ) ?>"/>
                <span aria-hidden="true"> <?php esc_html_e( 'Back', 'disciple-tools-prayer-campaigns' ); ?> </span>
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
                <img src="<?php echo esc_html( plugin_dir_url( __DIR__ ) . 'assets/back_icon.svg' ) ?>"/>
                <span aria-hidden="true"> <?php esc_html_e( 'Back', 'disciple-tools-prayer-campaigns' ); ?> </span>
            </button>
            <div class="cp-center" style="display: flex; flex-direction: column;">
                <label >
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
                <div>
                    <button style="margin-top:10px;" disabled id="cp-confirm-daily-times" class="cp-nav" data-open="cp-view-confirm" data-back-to="cp-daily-prayer-time"><?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?></button>
                </div>
            </div>
        </div>

        <!-- individual prayer times -->
        <div id="cp-choose-individual-times" class="cp-view" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-times-choose">
                <img src="<?php echo esc_html( plugin_dir_url( __DIR__ ) . 'assets/back_icon.svg' ) ?>"/>
                <span aria-hidden="true"> <?php esc_html_e( 'Back', 'disciple-tools-prayer-campaigns' ); ?> </span>
            </button>
            <h2 id="individual-day-title" class="cp-center">
                <?php esc_html_e( 'Select a day and choose a time', 'disciple-tools-prayer-campaigns' ); ?>
            </h2>
            <div id="cp-day-content" class="cp-center" >
                <div style="margin-bottom: 20px;display: flex;flex-flow: wrap;justify-content: space-evenly;">
                    <div id="day-select-calendar" class=""></div>
                </div>
                <div class="cp-center" style="display: flex; flex-direction: column">
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
                        <ul class="cp-display-selected-times">
                            <li><?php esc_html_e( 'No selected Time', 'disciple-tools-prayer-campaigns' ); ?></li>
                        </ul>
                    </div>
                    <div>
                        <button style="margin-top:10px" disabled id="cp-confirm-individual-times" class="cp-nav" data-open="cp-view-confirm" data-force-scroll="true" data-back-to="cp-choose-individual-times"><?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- confirm email -->
        <div id="cp-view-confirm" class="cp-view cp-center" style="display: none">
            <button class="cp-close-button cp-nav" data-open="cp-times-choose">
                <img src="<?php echo esc_html( plugin_dir_url( __DIR__ ) . 'assets/back_icon.svg' ) ?>"/>
                <span aria-hidden="true"> <?php esc_html_e( 'Back', 'disciple-tools-prayer-campaigns' ); ?> </span>
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
                    <?php echo esc_html( "Your name is required" ); ?>
                </span>
                <label for="name"><?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?><br>
                    <input class="cp-input" type="text" name="name" id="name" placeholder="<?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?>" required/>
                </label>
            </div>
            <div>
                <span id="email-error" class="form-error">
                    <?php esc_html_e( "Your email is required.", 'disciple-tools-prayer-campaigns' ); ?>
                </span>
                <label for="email"><?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?><br>
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
                        <button class="cp-nav cp-ok-done-button"><?php esc_html_e( 'OK', 'disciple-tools-prayer-campaigns' ); ?></button>
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
            <button class="button cp-nav" type="button" id="confirm-timezone" data-open="cp-times-choose">
                <?php echo esc_html__( 'Select', 'disciple-tools-prayer-campaigns' )?>
            </button>
        </div>


        <!-- confirm email later-->
        <div id="cp-view-confirm-later" class="cp-view cp-center" style="display: none">
            <p><?php esc_html_e( "I'm interested in praying! Send me an email when it is time to choose prayer times.", 'disciple-tools-prayer-campaigns' ); ?></p>
            <div>
                <span id="name-error-later" class="form-error">
                    <?php echo esc_html( "Your name is required." ); ?>
                </span>
                <label for="name"><?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?><br>
                    <input class="cp-input" type="text" name="name" id="name-later" placeholder="<?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?>" required/>
                </label>
            </div>
            <div>
                <span id="email-error-later" class="form-error">
                    <?php esc_html_e( "Your email is required.", 'disciple-tools-prayer-campaigns' ); ?>
                </span>
                <label for="email"><?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?><br>
                    <input class="cp-input" type="email" name="email" id="email-later" placeholder="<?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>" />
                    <input class="cp-input" type="email" name="e2" id="e2-later" placeholder="<?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>" required />
                </label>
            </div>
            <div>
                <div>
                    <button class="button loader" id="cp-submit-form-later">
                        <?php esc_html_e( 'Submit Your Prayer Commitment', 'disciple-tools-prayer-campaigns' ); ?> <img id="cp-submit-form-spinner-later" style="display: none" src="<?php echo esc_url( trailingslashit( plugin_dir_url( __FILE__ ) ) ) ?>../spinner.svg" width="22px" alt="spinner "/></button>
                </div>
            </div>

            <div class="success-confirmation-section">
                <div class="cell center">
                    <h2><?php esc_html_e( 'Sent! Check your email.', 'disciple-tools-prayer-campaigns' ); ?></h2>
                    <p>
                        <button class="cp-nav cp-ok-done-button"><?php esc_html_e( 'OK', 'disciple-tools-prayer-campaigns' ); ?></button>
                    </p>
                </div>
            </div>

        </div>

        <!-- confirm email later-->
        <div id="cp-view-closed" class="cp-view cp-center" style="display: none">
            <p><?php esc_html_e( "We are not longer looking for sign ups", 'disciple-tools-prayer-campaigns' ); ?></p>
            <p><?php esc_html_e( "Thanks for praying with us!", 'disciple-tools-prayer-campaigns' ); ?></p>
        </div>


    </div> <!-- form wrapper -->
    <?php endif;
}

function dt_24hour_campaign_shortcode( $atts ){

    if ( !isset( $atts["root"], $atts["type"], $atts["public_key"], $atts["meta_key"], $atts["post_id"], $atts["rest_url"] ) ){
        return;
    }

    if ( $atts["type"] !== "24hour" && $atts["root"] !== "campaign_app" ){
        return;
    }

    if ( isset( $atts["lang"] ) ){
        add_filter( 'determine_locale', function ( $locale ) use ( $atts ){
            $lang_code = sanitize_text_field( wp_unslash( $atts["lang"] ) );
            if ( !empty( $lang_code ) ){
                return $lang_code;
            }
            return $locale;
        } );
        load_plugin_textdomain( 'disciple-tools-prayer-campaigns', false, trailingslashit( dirname( plugin_basename( __FILE__ ), 2 ) ). 'languages' );
    }

    dt_24hour_campaign_register_scripts( $atts );

    ob_start();
    dt_24hour_campaign_body( $atts["color"] ?? "", $atts["section"] ?? "" );
    return ob_get_clean();
}
add_shortcode( 'dt_campaign', 'dt_24hour_campaign_shortcode' );

if ( !function_exists( 'dt_recursive_sanitize_array' ) ){
    function dt_recursive_sanitize_array( array $array ) : array {
        foreach ( $array as $key => &$value ) {
            if ( is_array( $value ) ) {
                $value = dt_recursive_sanitize_array( $value );
            }
            else {
                $value = sanitize_text_field( wp_unslash( $value ) );
            }
        }
        return $array;
    }
}

function dt_campaign_router_endpoint( WP_REST_Request $request ){
    $params = $request->get_params();
    $params = dt_recursive_sanitize_array( $params );
    if ( isset( $params["parts"]["post_id"] ) ){
        $remote = get_transient( "dt_magic_link_remote_" . $params["parts"]["post_id"] );
        $url = $remote . $params["root"] .  $params["parts"]["root"] . '/v1/' .  $params["parts"]["type"] . '/' . $params["url"];
        if ( !empty( $remote ) ){
            if ( $request->get_method() === "GET" ){
                $fetch = wp_remote_get( $url, [ "body" => $params ] );
                if ( !is_wp_error( $fetch ) ){
                    return json_decode( wp_remote_retrieve_body( $fetch ) );
                }
            }
            if ( $request->get_method() === "POST" ){
                $fetch = wp_remote_post( $url, [ "body" => $params ] );
                if ( !is_wp_error( $fetch ) ){
                    return json_decode( wp_remote_retrieve_body( $fetch ) );
                }
            }
        }
    }

    return false;
}

add_action( 'rest_api_init', function (){
    $namespace = 'campaign_app/v1';
    register_rest_route(
        $namespace, '/24hour-router', [
            [
                'methods'  => [ "POST", "GET" ],
                'callback' => "dt_campaign_router_endpoint",
                'permission_callback' => '__return_true',
            ],
        ]
    );
} );


