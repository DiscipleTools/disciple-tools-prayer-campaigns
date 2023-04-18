<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Generic_Porch_Stats extends DT_Magic_Url_Base
{
    public $page_title = 'Campaign Stats';
    public $root = PORCH_LANDING_ROOT;
    public $type = 'stats';
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
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

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
        return dt_campaign_custom_dir_attr( $lang );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'dt_campaign';
        $allowed_js[] = 'luxon';
        return array_merge( $allowed_js, DT_Generic_Porch_Landing_Enqueue::load_allowed_scripts() );
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        return DT_Generic_Porch_Landing_Enqueue::load_allowed_styles();
    }

    public function wp_enqueue_scripts() {
        DT_Generic_Porch_Landing_Enqueue::load_scripts();
    }

    public function body(){
//        DT_Generic_Porch::instance()->require_once( 'top-section.php' );

        $porch_fields = DT_Porch_Settings::settings();
        $campaign_fields = DT_Campaign_Settings::get_campaign();
        $langs = dt_campaign_list_languages();
        $post_id = $campaign_fields['ID'];
        $lang = dt_campaign_get_current_lang();
        dt_campaign_set_translation( $lang );
        $current_selected_porch = DT_Campaign_Settings::get( 'selected_porch' );


        $timezone = 'America/Chicago';
        if ( isset( $campaign_fields['campaign_timezone']['key'] ) ){
            $timezone = $campaign_fields['campaign_timezone']['key'];
        }

        $min_time_duration = 15;
        if ( isset( $campaign_fields['min_time_duration']['key'] ) ){
            $min_time_duration = (int) $campaign_fields['min_time_duration']['key'];
        }
        $subscribers_count = DT_Subscriptions::get_subscribers_count( $post_id );
        $coverage_percent = DT_Campaigns_Base::query_coverage_percentage( $post_id );

        $total_number_of_time_slots = DT_Campaigns_Base::query_coverage_total_time_slots( $post_id );
        $current_commitments = DT_Time_Utilities::get_current_commitments( $post_id );

        arsort( $current_commitments );
        $committed_time_slots = 0;
        if ( method_exists( 'DT_Campaigns_Base', 'query_total_events_count' ) ){
            $committed_time_slots = DT_Campaigns_Base::query_total_events_count( $post_id );
        }
        $lang = dt_campaign_get_current_lang();

        $total_mins_prayed = DT_Campaigns_Base::get_minutes_prayed_and_scheduled( $post_id );
        $mins_as_a_group = DT_Campaigns_Base::time_prayed_as_a_group( $post_id );
        $campaign_root = 'campaign_app';
        $campaign_type = $campaign_fields['type']['key'];
        $key_name = 'public_key';
        $key = '';
        if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
            $key_name = DT_Magic_URL::get_public_key_meta_key( $campaign_root, $campaign_type );
        }
        if ( isset( $campaign_fields[$key_name] ) ){
            $key = $campaign_fields[$key_name];
        }
        $atts = [
            'root' => $campaign_root,
            'type' => $campaign_type,
            'public_key' => $key,
            'meta_key' => $key_name,
            'post_id' => (int) $campaign_fields['ID'],
            'rest_url' => rest_url(),
            'lang' => $lang
        ];
        $selected_campaign_magic_link_settings = $atts;
        $selected_campaign_magic_link_settings['color'] = PORCH_COLOR_SCHEME_HEX;
        if ( $selected_campaign_magic_link_settings['color'] === 'preset' ){
            $selected_campaign_magic_link_settings['color'] = '#4676fa';
        }

        $thank_you = __( 'Thank you for praying with us!', 'disciple-tools-prayer-campaigns' );
        if ( !empty( $porch_fields['people_name']['value'] ) && !empty( $porch_fields['country_name']['value'] ) ){
            $thank_you = sprintf( _x( 'Thank you for joining us in prayer for the %1$s in %2$s.', 'Thank you for joining us in prayer for the French in France.', 'disciple-tools-prayer-campaigns' ), $porch_fields['people_name']['value'], $porch_fields['country_name']['value'] );
        }
        ?>

        <style>
            .wow p {
                font-weight: 600;
            }
            #campaign-stats .center {
                text-align: center;
            }
            #campaign-stats .dt-magic-link-language-selector {
                border: 1px solid black;
                background-color: transparent;
                color: black;
                border-radius: 5px;
                padding: 5px;
                margin-inline-start: 1em;
            }
        </style>
        <div id="campaign-stats">
            <section class="section" data-stellar-background-ratio="0.2" style="padding-bottom: 0; min-height: 800px">
                <div class="container">
                    <div class="section-header" style="margin-bottom: 20px">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Campaign Stats', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>
                    <div class="center" style="margin: 30px">
                        <select class="dt-magic-link-language-selector">
                            <?php foreach ( $langs as $code => $language ) : ?>
                                <option value="<?php echo esc_html( $code ); ?>" <?php selected( $lang === $code ) ?>>
                                    <?php echo esc_html( $language['flag'] ); ?> <?php echo esc_html( $language['native_name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="center"><?php echo esc_html( $thank_you ); ?></p>

                    <div class="row" style="padding-top:40px">
                        <div class="col-sm-12 col-md-9">
                            <div class="row">
                                <div class="col-md-6 col-sm-6">
                                    <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                                        <h4><?php esc_html_e( 'Prayer Commitments Needed', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                        <p>
                                            <?php echo esc_html( $total_number_of_time_slots ); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                                        <h4><?php esc_html_e( 'Percentage Covered', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                        <p>
                                            <?php echo esc_html( $coverage_percent ); ?>%
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                                        <h4><?php esc_html_e( 'Prayer Commitments', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                        <p>
                                            <?php echo esc_html( $committed_time_slots ); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                                        <h4><?php esc_html_e( 'Time Committed', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                        <p>
                                            <?php echo esc_html( DT_Time_Utilities::display_minutes_in_time( $total_mins_prayed ) ) ?>
                                            <br>
                                            (<?php echo esc_html( $total_mins_prayed / 60 ); ?> <?php esc_html_e( 'hours', 'disciple-tools-prayer-campaigns' ); ?>)
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                                        <h4><?php esc_html_e( 'Time Prayed in Groups', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                        <p>
                                            <?php echo esc_html( DT_Time_Utilities::display_minutes_in_time( $mins_as_a_group ) ) ?>
                                            <br>
                                            (<?php echo esc_html( $mins_as_a_group / 60 ); ?> <?php esc_html_e( 'hours', 'disciple-tools-prayer-campaigns' ); ?>)
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                                        <h4><?php esc_html_e( 'Number of People who Prayed', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                        <p>
                                            <?php echo esc_html( $subscribers_count ); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <?php
                            if ( empty( $selected_campaign_magic_link_settings ) ) :?>
                                <div class="container">
                                    <p style="margin:auto">Choose a campaign in settings <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns' ) );?>">here</a></p>
                                </div>
                            <?php else :
                                $selected_campaign_magic_link_settings['section'] = 'calendar';
                                echo dt_24hour_campaign_shortcode( //phpcs:ignore
                                    $selected_campaign_magic_link_settings
                                );
                            endif;
                            ?>
                        </div>
                    </div>

                </div>
            </section>

            <!--
                user stories and feedback section
            -->
            <section class="section" data-stellar-background-ratio="0.2">
                <div class="container">
                    <div class="section-header" style="padding-bottom: 40px;">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Share with us your Prayer Stories', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>
                    <form onSubmit="submit_feedback_form();return false;" id="form-content">
                        <p>
                            <label style="width: 100%">
                                <?php esc_html_e( 'Email', 'disciple-tools-prayer-campaigns' ); ?>
                                <br>
                                <input type="email" id="email" style="display: none">
                                <input type="email" id="email-2" required style="width: 100%">
                            </label>
                        </p>
                        <p>
                            <label style="width: 100%">
                                <?php esc_html_e( 'Share with us about your prayer time (E.g. testimonies, insights, blessings, etc)', 'disciple-tools-prayer-campaigns' ); ?>
                                <br>
                                <textarea id="campaign-stories" required rows="4" type="text" style="width: 100%"></textarea>
                            </label>
                            <button id="stories-submit-button" class="btn btn-common" style="font-weight: bold">
                                <?php esc_html_e( 'Submit', 'disciple-tools-prayer-campaigns' ); ?>
                                <img id="stories-submit-spinner" style="display: none; margin-left: 10px" src="<?php echo esc_url( trailingslashit( get_stylesheet_directory_uri() ) ) ?>spinner.svg" width="22px;" alt="spinner "/>
                            </button>
                        </p>
                    </form>
                    <div id="form-confirm" class="center" style="display: none">
                        <h3><?php esc_html_e( 'Thank You', 'disciple-tools-prayer-campaigns' ); ?></h3>
                    </div>
                </div>
            </section>
            <script>

                let submit_feedback_form = function (){

                    $('#stories-submit-spinner').show()
                    let honey = $('#email').val();
                    if ( honey ){
                        return;
                    }

                    let email = $('#email-2').val();
                    let story = $('#campaign-stories').val()
                    window.makeRequest( "POST", '/stories', { parts: jsObject.parts, email, story }, jsObject.parts.root + /v1/ + jsObject.parts.type ).done(function(data){
                        $('#stories-submit-spinner').show()
                        $('#form-content').hide()
                        $('#form-confirm').show()
                    })
                    .fail(function(e) {
                        // jQuery('#error').html(e)
                    })
                }

            </script>


            <?php if ( $current_selected_porch === 'ramadan-porch' ) {
                $ramadan_stats = p4m_cached_api_call( 'https://pray4movement.org/wp-json/dt-public/campaigns/campaigns-stats?start_date=2023-03-01&end_date=2023-05-01&focus=ramadan', 'GET', HOUR_IN_SECONDS );
                ?>
            <section class='section' data-stellar-background-ratio='0.2' style='padding-bottom: 0; min-height: 800px'>
                <div class='container'>
                    <div class='section-header' style='margin-bottom: 20px'>
                        <h2 class='section-title wow fadeIn' data-wow-duration='1000ms'
                            data-wow-delay='0.3s'><?php esc_html_e( 'Global Ramadan Stats', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>

                    <div class='row' style='padding-top:40px'>
                        <div class='col-md-4 col-sm-6'>
                            <div class='item-boxes wow fadeInDown' data-wow-delay='0.2s'>
                                <h4><?php esc_html_e( 'Total Prayer Campaigns', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                <p>
                                    <?php echo esc_html( number_format( $ramadan_stats['campaigns_count'] ) ); ?>
                                </p>
                            </div>
                        </div>
                        <div class='col-md-4 col-sm-6'>
                            <div class='item-boxes wow fadeInDown' data-wow-delay='0.2s'>
                                <h4><?php esc_html_e( 'Countries Prayed For', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                <p>
                                    <?php echo esc_html( $ramadan_stats['countries_prayed_for_count'] ); ?>
                                </p>
                            </div>
                        </div>
                        <div class='col-md-4 col-sm-6'>
                            <div class='item-boxes wow fadeInDown' data-wow-delay='0.2s'>
                                <h4><?php esc_html_e( 'Intercessors', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                <p>
                                    <?php echo esc_html( number_format( $ramadan_stats['intercessors_count'] ) ); ?>
                                </p>
                            </div>
                        </div>
                        <div class='col-md-4 col-sm-6'>
                            <div class='item-boxes wow fadeInDown' data-wow-delay='0.2s'>
                                <h4><?php esc_html_e( 'Time Prayed', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                <p>
                                    <?php echo esc_html( DT_Time_Utilities::display_minutes_in_time( $ramadan_stats['time_committed'] ) ); ?>
                                </p>
                            </div>
                        </div>
                        <div class='col-md-4 col-sm-6'>
                            <div class='item-boxes wow fadeInDown' data-wow-delay='0.2s'>
                                <h4><?php esc_html_e( '15 Minute Time Slots Filled', 'disciple-tools-prayer-campaigns' ); ?></h4>
                                <p>
                                    <?php echo esc_html( number_format( $ramadan_stats['time_slots_count'] ) ); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="center">
                        See <a href="https://pray4movement.org/ramadan"> https://pray4movement.org/ramadan</a> for more details.
                    </div>
                </div>
            </section>

            <?php } ?>


            <!--
                p4m mailchimp signup section
            -->
            <section class="section" data-stellar-background-ratio="0.2" style="padding-top: 0;">
                <div class="container">
                    <div class="section-header" style="padding-bottom: 40px;">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'More Prayer Opportunities', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>

                    <p class="center"><?php esc_html_e( 'Would you like to hear about other prayer efforts and opportunities with Pray4Movement.org?', 'disciple-tools-prayer-campaigns' ); ?></p>
                    <!-- Begin Mailchimp Signup Form -->
                    <?php // phpcs:ignore ?>
                    <link href="//cdn-images.mailchimp.com/embedcode/classic-10_7.css" rel="stylesheet" type="text/css">
                    <style type="text/css">
                        #mc_embed_signup{background:#fff; clear:left; font:14px Helvetica,Arial,sans-serif;  width:600px;}
                        /* Add your own Mailchimp form style overrides in your site stylesheet or in this style block.
                           We recommend moving this block and the preceding CSS link to the HEAD of your HTML file. */
                    </style>
                    <style type="text/css">
                        #mc-embedded-subscribe-form input[type=checkbox]{display: inline; width: auto;margin-right: 10px;}
                        #mergeRow-gdpr {margin-top: 20px;}
                        #mergeRow-gdpr fieldset label {font-weight: normal;}
                        #mc-embedded-subscribe-form .mc_fieldset{border:none;min-height: 0px;padding-bottom:0px;}
                    </style>

                    <div id="mc_embed_signup" class="center" style="margin: auto">
                        <form action="https://training.us14.list-manage.com/subscribe/post?u=d9ad41b66865008d664ac28bf&amp;id=4df6e5ea4e" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
                            <div id="mc_embed_signup_scroll">

                                <div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
                                <div class="mc-field-group">
                                    <label for="mce-FNAME">First Name </label>
                                    <input type="text" value="" name="FNAME" class="" id="mce-FNAME">
                                </div>
                                <div class="mc-field-group">
                                    <label for="mce-LNAME">Last Name </label>
                                    <input type="text" value="" name="LNAME" class="" id="mce-LNAME">
                                </div>
                                <div class="mc-field-group">
                                    <label for="mce-EMAIL">Email Address  <span class="asterisk">*</span>
                                    </label>
                                    <input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
                                </div>
                                <div id="mergeRow-gdpr" class="mergeRow gdpr-mergeRow content__gdprBlock mc-field-group" >
                                    <div class="content__gdpr" style="display: none">
                                        <label>Marketing Permissions</label>
                                        <p>Please select all the ways you would like to hear from :</p>
                                        <fieldset class="mc_fieldset gdprRequired mc-field-group" name="interestgroup_field">
                                            <label class="checkbox subfield" for="gdpr_142225">
                                                <input type="checkbox" id="gdpr_142225" name="gdpr[142225]" value="Y" class="av-checkbox gdpr" checked>
                                                <span>Email</span>
                                            </label>
                                        </fieldset>
                                        <p>You can unsubscribe at any time by clicking the link in the footer of our emails.</p>
                                    </div>
                                    <div class="content__gdprLegal">
                                        <p>We use Mailchimp as our marketing platform. By clicking below to subscribe, you acknowledge that your information will be transferred to Mailchimp for processing. <a href="https://mailchimp.com/legal/terms" target="_blank">Learn more about Mailchimp's privacy practices here.</a></p>
                                    </div>
                                </div>
                                <div hidden="true"><input type="hidden" name="tags" value="7237301,7236505"></div>
                                <div id="mce-responses" class="clear">
                                    <div class="response" id="mce-error-response" style="display:none"></div>
                                    <div class="response" id="mce-success-response" style="display:none"></div>
                                </div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
                                <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_d9ad41b66865008d664ac28bf_4df6e5ea4e" tabindex="-1" value=""></div>
                                <div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
                            </div>
                        </form>
                    </div>
                    <?php // phpcs:ignore ?>
                    <script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script><script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[3]='ADDRESS';ftypes[3]='address';fnames[4]='PHONE';ftypes[4]='phone';}(jQuery));var $mcj = jQuery.noConflict(true);</script>
                    <!--End mc_embed_signup-->

                </div>
            </section>
        </div>

        <?php
        do_action( 'dt_prayer_campaigns_stats_page' );
    }

    public function footer_javascript(){
        require_once( 'footer.php' );
    }

    public function header_javascript(){
        require_once( 'header.php' );
    }

    public function add_endpoints() {
        $namespace = $this->root . '/v1/'. $this->type;
        register_rest_route(
            $namespace, 'stories', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'add_story' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function add_story( WP_REST_Request $request ) {
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        if ( !isset( $params['story'], $params['email'] ) ){
            return false;
        }
        $params['story'] = wp_kses_post( $request->get_params()['story'] );

        $campaign_fields = DT_Campaign_Settings::get_campaign();
        $post_id = $campaign_fields['ID'];

        $comment = 'Story feedback from ' . site_url( 'prayer/stats' ) . ' by ' . $params['email'] . ": \n" . $params['story'];
        DT_Posts::add_post_comment( 'campaigns', $post_id, $comment, 'stories', [], false );

        $subs = DT_Posts::list_posts( 'subscriptions', [ 'campaigns' => [ $post_id ], 'contact_email' => [ $params['email'] ] ], false );
        if ( sizeof( $subs['posts'] ) === 1 ){
            DT_Posts::add_post_comment( 'subscriptions', $subs['posts'][0]['ID'], $comment, 'stories', [], false, true );
        }

        return true;
    }

}
DT_Generic_Porch_Stats::instance();
