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
        if ( !isset( $atts['root'], $atts['type'], $atts['public_key'], $atts['meta_key'], $atts['post_id'], $atts['rest_url'] ) ){
            return;
        }

        if ( $atts['type'] !== 'ongoing' && $atts['root'] !== 'campaign_app' ){
            return;
        }
        if ( empty( $atts['color'] ) ){
            $atts['color'] = 'dodgerblue';
        }
        $this->atts = $atts;
        if ( isset( $atts['lang'] ) ){
            add_filter( 'determine_locale', function ( $locale ) use ( $atts ){
                $lang_code = sanitize_text_field( wp_unslash( $atts['lang'] ) );
                if ( !empty( $lang_code ) ){
                    return $lang_code;
                }
                return $locale;
            } );
            dt_campaign_reload_text_domain();
        }
        $this->dt_24hour_campaign_register_scripts( $atts );
    }

    private function dt_24hour_campaign_register_scripts( $atts ){

        require_once( trailingslashit( __DIR__ ) . '../parts/components.php' );

        wp_register_script( 'luxon', 'https://cdn.jsdelivr.net/npm/luxon@2.3.1/build/global/luxon.min.js', false, '2.3.1', true );
        //campaigns core js
        if ( !wp_script_is( 'dt_campaign_core', 'registered' ) ){
            wp_register_script( 'dt_campaign_core', trailingslashit( plugin_dir_url( __DIR__ ) ) . 'post-type/campaign_core.js', [
                'jquery',
                'lodash',
                'luxon'
            ], filemtime( plugin_dir_path( __DIR__ ) . 'post-type/campaign_core.js' ), true );
            wp_localize_script( 'dt_campaign_core', 'dt_campaign_core', [ 'color' => $atts['color'] ?? '' ] );
        }

        wp_enqueue_style( 'dt_campaign_style', trailingslashit( plugin_dir_url( __DIR__ ) ) . $this->css_file, [], filemtime( plugin_dir_path( __DIR__ ) . 'magic-links/24hour/24hour.css' ) );

        //24 hour campaign js
        if ( !wp_script_is( 'dt_campaign' ) ){
            wp_enqueue_script( 'dt_campaign', trailingslashit( plugin_dir_url( __DIR__ ) ) . $this->js_file, [ 'luxon', 'jquery', 'dt_campaign_core' ], filemtime( plugin_dir_path( __DIR__ ) . $this->js_file ), true );
            wp_localize_script(
                'dt_campaign', 'campaign_objects', [
                    'translations' => [
                        //"campaign_duration" => __( 'Everyday from %1$s to %2$s', "disciple-tools-prayer-campaigns" ),
                        'praying_everyday' => _x( 'Everyday at %1$s for %2$s until %3$s', 'everyday at 3pm for 15 minutes until Jan 3 2031', 'disciple-tools-prayer-campaigns' ),
                        'select_a_time' => __( 'Select a time', 'disciple-tools-prayer-campaigns' ),
                        'covered' => _x( 'covered', 'Jun, Jul, Aug covered', 'disciple-tools-prayer-campaigns' ),
                        'covered_once' => __( 'covered once', 'disciple-tools-prayer-campaigns' ),
                        'covered_x_times' => __( 'covered %1$s times', 'disciple-tools-prayer-campaigns' ),
                        'time_slot_label' => _x( '%1$s for %2$s minutes.', 'Monday 5pm for 15 minutes', 'disciple-tools-prayer-campaigns' ),
                        'days' => __( 'days', 'disciple-tools-prayer-campaigns' ),
                    ],
                    'parts' => [
                        'root' => $atts['root'],
                        'type' => $atts['type'],
                        'public_key' => $atts['public_key'],
                        'meta_key' => $atts['meta_key'],
                        'post_id' => $atts['post_id'],
                        'lang' => $atts['lang'] ?? 'en_US'
                    ],
                    'root' => get_rest_url(),
                    'remote' => $atts['rest_url'] !== get_rest_url(),
                    'home' => home_url(),
                ]
            );
        }
        set_transient( 'dt_magic_link_remote_' . $atts['post_id'], $atts['rest_url'], DAY_IN_SECONDS );
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
                color: <?php echo esc_html( $shortcode_instance->atts['color'] ) ?>;
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
    $color = $shortcode_instance->atts['color'];
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
                    <p style="max-width: 410px"><strong><?php esc_html_e( "Chose a time to pray everyday for the next 3 months. Near the end you'll have the option to sign up for more.", 'disciple-tools-prayer-campaigns' ); ?></strong></p>
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
                    <div>
                        <button style="margin-top:10px;" disabled id="cp-confirm-daily-times" class="cp-nav" data-open="cp-view-confirm" data-back-to="cp-daily-prayer-time"><?php esc_html_e( 'Confirm Times', 'disciple-tools-prayer-campaigns' ); ?></button>
                    </div>
                </div>
            </div>

            <!-- individual prayer times -->
            <?php part_campaign_calendar_times_picker() ?>

            <!-- name and email -->
            <?php dt_campaign_sign_up_form() ?>

            <!-- validate email -->
            <?php dt_email_validate_form() ?>

            <!-- success confirmation -->
            <?php success_confirmation_section() ?>

            <!-- timezone changer -->
            <?php part_campaign_timezone_changer() ?>

            <!-- campaign closed -->
            <?php part_campaign_is_closed() ?>



        </div>
        <?php
        return ob_get_clean();
    }
}
add_shortcode( 'dt-ongoing-campaign-signup', 'dt_ongoing_campaign_signup' );

