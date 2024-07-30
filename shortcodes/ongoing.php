<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DT_Ongoing_Shortcode {
    public $atts;
    public $user_lang;
    private static $_instance = null;

    public static function instance( $atts ){
        if ( is_null( self::$_instance ) ){
            self::$_instance = new self( $atts );
        }
        return self::$_instance;
    } // End instance()

    public function __construct( $atts ){
        if ( !isset( $atts['root'], $atts['type'], $atts['public_key'], $atts['meta_key'], $atts['post_id'], $atts['rest_url'] ) ){
            return;
        }

        if ( $atts['root'] !== 'campaign_app' ){
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
        dt_campaigns_register_scripts( $atts, $atts['post_id'] );
        set_transient( 'dt_magic_link_remote_' . $atts['post_id'], $atts['rest_url'], DAY_IN_SECONDS );
    }
}

function dt_campaign_percentage( $atts ){
    $shortcode_instance = DT_Ongoing_Shortcode::instance( $atts );
    if ( !empty( $shortcode_instance->atts ) ){
        ob_start();
        ?>
        <style>
            :root {
                --cp-color: <?php echo esc_html( $atts['color'] ) ?>;
                --cp-color-dark: color-mix(in srgb, var(--cp-color), #000 10%);
                --cp-color-light: color-mix(in srgb, var(--cp-color), #fff 70%);
            }
        </style>
        <cp-percentage></cp-percentage>
        <?php
        return ob_get_clean();
    }
}

function dt_ongoing_campaign_calendar( $atts ){
    $shortcode_instance = DT_Ongoing_Shortcode::instance( $atts );
    if ( !empty( $shortcode_instance->atts ) ){
        ob_start();
        ?>
        <style>
            :root {
                --cp-color: <?php echo esc_html( $atts['color'] ) ?>;
                --cp-color-dark: color-mix(in srgb, var(--cp-color), #000 10%);
                --cp-color-light: color-mix(in srgb, var(--cp-color), #fff 70%);
            }
        </style>
        <cp-calendar
            locale="<?php echo esc_attr( dt_campaign_get_current_lang() ); ?>"
        ></cp-calendar>
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
        <style>
            :root {
                --cp-color: <?php echo esc_html( $atts['color'] ) ?>;
                --cp-color-dark: color-mix(in srgb, var(--cp-color), #000 10%);
                --cp-color-light: color-mix(in srgb, var(--cp-color), #fff 70%);
            }
        </style>
        <campaign-sign-up
            locale="<?php echo esc_attr( dt_campaign_get_current_lang() ); ?>"
        ></campaign-sign-up>
        <?php
        return ob_get_clean();
    }
}
add_shortcode( 'dt-ongoing-campaign-signup', 'dt_ongoing_campaign_signup' );

