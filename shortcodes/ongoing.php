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
            wp_enqueue_script( 'dt_campaign', trailingslashit( plugin_dir_url( __DIR__ ) ) . $this->js_file, [ 'luxon', 'jquery', 'dt_campaign_core' ], filemtime( plugin_dir_path( __DIR__ ) . $this->js_file ), true );
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
}

function dt_ongoing_campaign_calendar( $atts ){
    $shortcode_instance = DT_Ongoing_Shortcode::instance( $atts );
    if ( !empty( $shortcode_instance->atts ) ){
        ob_start();
        //to show this month and next month.
        ?>
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
    if ( !empty( $shortcode_instance->atts ) ){
        ob_start();
        ?>
        <h2>Shortcode Content</h2>
        <?php
        return ob_get_clean();
    }
}
add_shortcode( 'dt-ongoing-campaign-signup', 'dt_ongoing_campaign_signup' );

