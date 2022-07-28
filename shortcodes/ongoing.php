<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DT_Ongoing_Shortcode {
    public $atts;
    public $user_lang;
    private $js_file = 'magic-links/ongoing/ongoing.js';
    private $css_file = 'magic-links/ongoing/ongoing.css';

    private static $_instance = null;
    public static function instance( $atts ) {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $atts );
        }
        return self::$_instance;
    } // End instance()

    public function __construct( $atts ) {
        if ( !isset( $atts["root"], $atts["type"], $atts["public_key"], $atts["meta_key"], $atts["post_id"], $atts["rest_url"] ) ){
            return;
        }

        if ( $atts["type"] !== "ongoing" && $atts["root"] !== "campaign_app" ){
            return;
        }
        if ( empty( $atts["color"] ) ){
            $atts["color"] = "dodgerblue";
        }
        $this->atts = $atts;
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
        $this->dt_24hour_campaign_register_scripts( $atts );
    }

    private function dt_24hour_campaign_register_scripts( $atts ){

        require_once( trailingslashit( __DIR__ ) . '../parts/components.php' );

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

        wp_enqueue_style( 'dt_campaign_style', trailingslashit( plugin_dir_url( __DIR__ ) ) . $this->css_file, [], filemtime( plugin_dir_path( __DIR__ ) . 'magic-links/24hour/24hour.css' ) );

        //24 hour campaign js
        if ( !wp_script_is( 'dt_campaign' ) ){
            wp_enqueue_script( 'dt_mailchimp', trailingslashit( plugin_dir_url( __DIR__ ) ) . 'assets/js/mailchimp.js', [], filemtime( plugin_dir_path( __DIR__ ) . 'assets/js/mailchimp.js' ), true );
            wp_enqueue_script( 'dt_campaign', trailingslashit( plugin_dir_url( __DIR__ ) ) . $this->js_file, [ 'luxon', 'jquery', 'dt_campaign_core' ], filemtime( plugin_dir_path( __DIR__ ) . $this->js_file ), true );
            wp_localize_script(
                'dt_campaign', 'campaign_objects', [
                    'translations' => [
                        //"campaign_duration" => __( 'Everyday from %1$s to %2$s', "disciple-tools-prayer-campaigns" ),
                        "praying_everyday" => _x( 'Everyday at %1$s for %2$s until %3$s', 'everyday at 3pm for 15 minutes until Jan 3 2031', "disciple-tools-prayer-campaigns" ),
                        "select_a_time" => __( 'Select a time', 'disciple-tools-prayer-campaigns' ),
                        "covered" => __( 'covered', 'disciple-tools-prayer-campaigns' ),
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
}

function dt_ongoing_campaign_calendar( $atts ){
    $shortcode_instance = DT_Ongoing_Shortcode::instance( $atts );
    if ( !empty( $shortcode_instance->atts ) ){
        ob_start();
        ?>
        <style>
            .cp-wrapper.cp-calendar-wrapper {
                width: fit-content;
            }
            .cp-wrapper .month-title, .cp-calendar-wrapper .month-title {
                color: <?php echo esc_html( $shortcode_instance->atts["color"] ) ?>;
            }
        </style>
        <div class="cp-calendar-wrapper cp-wrapper">
            <div style="display: flex; flex-flow: wrap; justify-content: space-evenly; margin: 0">
                <div id="calendar-content"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
add_shortcode( 'dt-ongoing-campaign-calendar', 'dt_ongoing_campaign_calendar' );


function dt_ongoing_campaign_signup( $atts ){
    $shortcode_instance = DT_Ongoing_Shortcode::instance( $atts );
    $color = $shortcode_instance->atts["color"];
    if ( !empty( $shortcode_instance->atts ) ){
        ob_start();
        ?>
        <style>
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
                        <div id="day-select-calendar" class="cp-calendar-wrapper"></div>
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

                <?php dt_campaign_sign_up_form() ?>

                <div id="confirmation-times" style="margin-top: 40px">
                    <h3><?php esc_html_e( 'Selected Times', 'disciple-tools-prayer-campaigns' ); ?></h3>
                    <ul class="cp-daily-selected-times"></ul>
                    <ul class="cp-display-selected-times"></ul>
                </div>
            </div>

            <div id="cp-success-confirmation-section" class="cp-view cp-center success-confirmation-section">
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


            <div id="cp-view-closed" class="cp-view cp-center" style="display: none">
                <p><?php esc_html_e( "We are not longer looking for sign ups", 'disciple-tools-prayer-campaigns' ); ?></p>
                <p><?php esc_html_e( "Thanks for praying with us!", 'disciple-tools-prayer-campaigns' ); ?></p>
            </div>


        </div>
        <?php
        return ob_get_clean();
    }
}
add_shortcode( 'dt-ongoing-campaign-signup', 'dt_ongoing_campaign_signup' );

