<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class P4_Ramadan_Porch_Power extends DT_Magic_Url_Base
{
    public $page_title = 'Ramadan Posts';
    public $root = PORCH_LANDING_ROOT;
    public $type = 'power';
    public $post_type = PORCH_LANDING_POST_TYPE;
    public $meta_key = PORCH_LANDING_META_KEY;

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        parent::__construct();

        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        $length = strlen( $this->root . '/' . $this->type );
        if ( substr( $url, 0, $length ) !== $this->root . '/' . $this->type ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( !$this->check_parts_match( false ) ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] ); // body for no post key

        require_once( 'landing-enqueue.php' );
        require_once( 'enqueue.php' );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 99 );
        add_filter( 'language_attributes', [ $this, 'dt_custom_dir_attr' ] );
    }
    public function dt_custom_dir_attr( $lang ){
        return ramadan_custom_dir_attr( $lang );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'dt_campaign';
        $allowed_js[] = 'luxon';
        return array_merge( $allowed_js, P4_Ramadan_Porch_Landing_Enqueue::load_allowed_scripts() );
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        return P4_Ramadan_Porch_Landing_Enqueue::load_allowed_styles();
    }

    public function wp_enqueue_scripts() {
        P4_Ramadan_Porch_Landing_Enqueue::load_scripts();
    }

    public function body(){
//        require_once( 'top-section.php' );

        $porch_fields = DT_Porch_Settings::porch_fields();
        $campaign_fields = DT_Campaign_Settings::get_campaign()();
        $langs = dt_ramadan_list_languages();
        $post_id = $campaign_fields["ID"];

        $power_fields = $porch_fields["power"]["value"];
        $timezone = "America/Chicago";
        if ( isset( $campaign_fields["campaign_timezone"]["key"] ) ){
            $timezone = $campaign_fields["campaign_timezone"]["key"];
        }
        $dt_now = new DateTime();
        $dt_now->setTimezone( new DateTimeZone( $timezone ) );
        $dt_now->setTimestamp( strtotime( $power_fields["start"] ) );
        $off = $dt_now->getOffset();
        $stamp = $dt_now->getTimestamp() - $off;
        $start = $stamp + $power_fields["start_time"];
        $dt_now->setTimestamp( strtotime( $power_fields["end"] ) );
        $off = $dt_now->getOffset();
        $stamp = $dt_now->getTimestamp() - $off;
        $end = $stamp + $power_fields["end_time"];

        $min_time_duration = 15;
        if ( isset( $campaign_fields["min_time_duration"]["key"] ) ){
            $min_time_duration = $campaign_fields["min_time_duration"]["key"];
        }
        $duration = $end - $start;
        $hours  = $duration / 3600;

        $power_prayer_slots_committed = 0;

        $current_commitments = DT_Time_Utilities::get_current_commitments( $post_id );
        foreach ( $current_commitments as $time => $committed ){
            if ( $time < $end && $time >= $start ){
                $power_prayer_slots_committed += $committed;
            }
        }
        $committed_time_in_hours = $power_prayer_slots_committed * $min_time_duration / 60;



        $lang = dt_campaign_get_current_lang();

        $campaign_root = "campaign_app";
        $campaign_type = $campaign_fields["type"]["key"];
        $key_name = 'public_key';
        $key = "";
        if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( $campaign_root, $campaign_type );
        }
        if ( isset( $campaign_fields[$key_name] ) ){
            $key = $campaign_fields[$key_name];
        }
        $atts = [
            "root" => $campaign_root,
            "type" => $campaign_type,
            "public_key" => $key,
            "meta_key" => $key_name,
            "post_id" => (int) $campaign_fields["ID"],
            "rest_url" => rest_url(),
            "lang" => $lang
        ];
        $dt_ramadan_selected_campaign_magic_link_settings = $atts;
        $dt_ramadan_selected_campaign_magic_link_settings["color"] = PORCH_COLOR_SCHEME_HEX;
        if ( $dt_ramadan_selected_campaign_magic_link_settings["color"] === "preset" ){
            $dt_ramadan_selected_campaign_magic_link_settings["color"] = '#4676fa';
        }

        ?>

<!--        <section class="section">-->
<!--        </section>-->
        <div class="container">
            <img src="<?php echo esc_html( plugin_dir_url( __File__ ) . 'img/night-of-power.png' ); ?>" alt="night of power" style="width:100%">
        </div>

        <section class="section" data-stellar-background-ratio="0.2" style="padding-bottom: 0">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'How many hours of prayer can we mobilize for this night?', 'dt-campaign-generic-porch' ); ?></h2>
                    <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                </div>
                <p>
                    <?php esc_html_e( 'Ramadan is the most important month of the Islamic calendar for Muslims, however, there is one night considered more important than all the others -- Laylat al-Qadr (Night of Power). This is the night Muslims believe Mohammed began to receive the Qu’ran. Though Mohammed did not remember which night this happened exactly, most Muslims believe it was the 27th night of Ramadan. Any good deed performed on this one night -- giving of charity, praying, reciting the Qu’ran -- is considered better than a thousand months. Muslims believe the throne of God is opened on this night and if one is found praying, the chances of receiving their requests are increased.', 'dt-campaign-generic-porch' ); ?>
                </p>
                <p>
                    <?php esc_html_e( 'The Night of Power is a significant time for Christians to engage in extraordinary prayer for Muslims. As ones who already have access to the throne of God (Hebrews 4:16), let us pray fervently for Jesus to make Himself known to Muslims this night.', 'dt-campaign-generic-porch' ); ?>
                </p>
            </div>
        </section>
        <section class="section" data-stellar-background-ratio="0.2" style="padding-bottom: 0">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Sign Up to', 'dt-campaign-generic-porch' ); ?> <span><?php esc_html_e( 'Pray', 'dt-campaign-generic-porch' ); ?></span></h2>
                    <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                            <div class="icon">
                                <img style="height: 40px; margin-top:10px" src="<?php echo esc_html( plugin_dir_url( __File__ ) . 'img/calendar.svg' ) ?>" alt="Praying hands icon"/>
                            </div>
                            <h4><?php esc_html_e( 'Starting', 'dt-campaign-generic-porch' ); ?></h4>
                            <p>
                                <strong style="font-weight: 600" id="power_start_time"></strong>
                                <br>
                                <span class="timezone-disp"><?php echo esc_html( $timezone ); ?></span> <?php esc_html_e( 'time', 'dt-campaign-generic-porch' ); ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="item-boxes wow fadeInDown" data-wow-delay="0.4s">
                            <div class="icon">
                                <img style="height: 40px; margin-top:10px" src="<?php echo esc_html( plugin_dir_url( __File__ ) . 'img/calendar.svg' ) ?>" alt="a network icon indicating movement"/>
                            </div>
                            <h4><?php esc_html_e( 'Ending', 'dt-campaign-generic-porch' ); ?></h4>
                            <p>
                                <strong style="font-weight: 600" id="power_end_time"></strong>
                                <br>
                                <span class="timezone-disp"><?php echo esc_html( $timezone ); ?></span> <?php esc_html_e( 'time', 'dt-campaign-generic-porch' ); ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="item-boxes wow fadeInDown" data-wow-delay="0.6s">
                            <div class="icon">
                                <img style="height: 40px; margin-top:10px" src="<?php echo esc_html( plugin_dir_url( __File__ ) . 'img/24_7.svg' ) ?>" alt="clock icon"/>
                            </div>
                            <h4><?php esc_html_e( 'Duration', 'dt-campaign-generic-porch' ); ?></h4>
                            <p>
                                <strong style="font-weight: 600"><?php echo esc_html( $hours ); ?></strong>
                                <br>
                                <?php esc_html_e( 'Hours', 'dt-campaign-generic-porch' ); ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="item-boxes wow fadeInDown" data-wow-delay="0.6s">
                            <div class="icon">
                                <img style="height: 40px; margin-top:10px" src="<?php echo esc_html( plugin_dir_url( __File__ ) . 'img/24_7.svg' ) ?>" alt="clock icon"/>
                            </div>
                            <h4><?php esc_html_e( 'Prayer time committed', 'dt-campaign-generic-porch' ); ?></h4>
                            <p>
                                <strong style="font-weight: 600"><?php echo esc_html( $committed_time_in_hours ); ?></strong>
                                <br>
                                <?php esc_html_e( 'Hours', 'dt-campaign-generic-porch' ); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" data-stellar-background-ratio="0.2" style="padding-top:40px">
            <div id="sign-up" name="sign-up" class="container">
                <div class="row">
                    <?php
                    if ( empty( $dt_ramadan_selected_campaign_magic_link_settings ) ) :?>
                        <p style="margin:auto">Choose campaign in settings <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_porch_template&tab=general' ) );?>"><?php esc_html_e( 'here', 'dt-campaign-generic-porch' ); ?></a></p>
                    <?php else :
                        $dt_ramadan_selected_campaign_magic_link_settings["section"] = "sign_up";
                        echo dt_24hour_campaign_shortcode( //phpcs:ignore
                            $dt_ramadan_selected_campaign_magic_link_settings
                        );
                    endif;
                    ?>
                </div>
            </div>
        </section>

        <script>
            jQuery(document).ready(function($) {
                let power_current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
                $('.timezone-disp').html( power_current_time_zone );


                let power_start = <?php echo esc_html( $start ); ?>;
                let start_date = window.luxon.DateTime.fromSeconds(power_start).setZone(power_current_time_zone)
                $('#power_start_time').html(start_date.toFormat('MMMM dd hh:mm a'))

                let power_end = <?php echo esc_html( $end ); ?>;
                let end_date = window.luxon.DateTime.fromSeconds(power_end).setZone(power_current_time_zone)
                $('#power_end_time').html(end_date.toFormat('MMMM dd hh:mm a'))

                //configure campaigns tool
                $('#open-select-times-button').attr('data-open', 'cp-choose-individual-times').text('Select a time during the Night of Power')
                $('#cp-choose-individual-times .cp-close-button').hide()
            })
        </script>
        <?php
    }

    public function footer_javascript(){
        require_once( 'footer.php' );
    }

    public function header_javascript(){
        require_once( 'header.php' );
    }
}
P4_Ramadan_Porch_Power::instance();
