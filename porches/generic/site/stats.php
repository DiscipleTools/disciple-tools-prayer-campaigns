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
        $this->page_title = __( 'Campaign Stats', 'disciple-tools-prayer-campaigns' );
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
        $allowed_js = [];
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'dt_campaign';
        $allowed_js[] = 'luxon';
        $allowed_js[] = 'jquery';
        $allowed_js[] = 'lodash';
        $allowed_js[] = 'lodash-core';

        $allowed_js[] = 'campaign_css';
        $allowed_js[] = 'campaign_components';
        $allowed_js[] = 'campaign_component_sign_up';
        $allowed_js[] = 'campaign_component_css';
        $allowed_js[] = 'toastify-js';


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

        $campaign_name = $porch_fields['title']['value'];
        $campaign_name_translated = DT_Porch_Settings::get_field_translation( 'title' );

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
        $campaign_type = $campaign_fields['type']['key'] ?? 'ongoing';
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
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( $campaign_name_translated ); ?> <?php esc_html_e( 'Stats', 'disciple-tools-prayer-campaigns' ); ?></h2>
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
                        <div class="col-sm-12 col-md-8">
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
                        <div class="col-sm-12 col-md-4">
                            <?php
                            if ( empty( $selected_campaign_magic_link_settings ) ) :?>
                                <div class="container">
                                    <p style="margin:auto">Choose a campaign in settings <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns' ) );?>">here</a></p>
                                </div>
                            <?php else :
                                $selected_campaign_magic_link_settings['section'] = 'calendar';
                                echo dt_ongoing_campaign_calendar( //phpcs:ignore
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
                    <form onSubmit="submit_feedback_form();return false;" id="form-content" style="max-width: 600px; margin: auto">
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
                            <button id="stories-submit-button" class="btn btn-common" style="font-weight: bold; margin-left: 0">
                                <?php esc_html_e( 'Submit', 'disciple-tools-prayer-campaigns' ); ?>
                                <img id="stories-submit-spinner" style="display: none; margin-left: 10px" src="<?php echo esc_url( trailingslashit( get_stylesheet_directory_uri() ) ) ?>spinner.svg" width="22px;" alt="spinner "/>
                            </button>
                        </p>
                    </form>
                    <div id="form-confirm" class="center" style="display: none">
                        <h3><?php esc_html_e( 'Thank you', 'disciple-tools-prayer-campaigns' ); ?></h3>
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
                p4m news signup section
            -->
            <?php if ( dt_campaigns_is_p4m_news_enabled() ) : ?>
            <section class="section" data-stellar-background-ratio="0.2" style="padding-top: 0;">
                <div class="container">
                    <div class="section-header" style="padding-bottom: 40px;">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'More Prayer Opportunities', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>

                    <p class="center"><?php esc_html_e( 'Sign up to hear about other prayer efforts and opportunities with Pray4Movement.org and receive occasional communication from GospelAmbition.org.', 'disciple-tools-prayer-campaigns' ); ?></p>

                    <style>
                        #go-optin-form .dt-form-error {
                            color: #cc4b37;
                            font-size: 0.8rem;
                            font-weight: bold;
                        }

                        #go-optin-form .dt-form-success {
                            color: #4CAF50;
                            font-size: 0.8rem;
                            font-weight: bold;
                        }
                        #go-optin-form input {
                            display: block;
                            width: 100%;
                            color: black;
                            font-size: 16px;
                        }
                        #go-optin-form label {
                            display: block;
                            width: 100%;
                        }
                        #go-optin-form {
                            max-width: 600px;
                            margin: auto;
                        }
                        #go-optin-form .asterisk {
                            color: red;
                            font-weight: normal;
                            position: relative;
                            top: 5px;
                        }

                    </style>
                    <div class='go-opt-in__form'>
                        <form id='go-optin-form' action='https://pray4movement.org/wp-json/go-webform/optin' method='post'>
                            <div class='form-group'>
                                <label>
                                    Email Address <span class='asterisk'>*</span>
                                    <input type='email' name='email2' class='form-control' required>
                                    <input type='email' name='email' placeholder='Email Address' style='display: none'>
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    First Name
                                    <input type='text' name='first_name' class='form-control'>
                                </label>
                            </div>
                            <div class='form-group'>
                                <label>
                                    Last Name
                                    <input type='text' name='last_name' class='form-control'>
                                </label>
                            </div>

                            <div>
                                <div class='input-group'>
                                    <div class='input-group-button'>
                                        <button id='go-submit-form-button' type='submit' class='btn btn-common' style="font-weight: bold; margin-left: 0">
                                            Subscribe
                                            <img id='go-submit-spinner' style='display: none; height: 25px'
                                                 src="<?php echo esc_url( trailingslashit( get_stylesheet_directory_uri() ) ) ?>spinner.svg" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class='dt-form-success'></div>
                            <span class='dt-form-error'></span>
                        </form>
                    </div>
                    <script>
                      let go_form = document.getElementById('go-optin-form');
                      let error_span = go_form.querySelector('.dt-form-error');
                      go_form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        go_form.querySelector('#go-submit-spinner').style.display = 'inline-block';
                        go_form.querySelector('#go-submit-form-button').disabled = true;
                        let email = go_form.querySelector('input[name="email"]').value;
                        let email2 = go_form.querySelector('input[name="email2"]').value;
                        if (email) {
                          return
                        }
                        let first_name = go_form.querySelector('input[name="first_name"]').value;
                        let last_name = go_form.querySelector('input[name="last_name"]').value;

                        let data = {
                            email: email2,
                            first_name: first_name,
                            last_name: last_name,
                            lists: ['list_23'],
                            source: 'p4m_campaign_stats',
                            named_tags: {'p4m_campaign_name': '<?php echo esc_html( $campaign_name ); ?>'}
                        }

                        fetch('https://pray4movement.org/wp-json/go-webform/optin', {
                          method: 'POST',
                          body: JSON.stringify(data),
                          headers: {
                            'Content-Type': 'application/json'
                          }
                        }).then(function (response) {
                          go_form.querySelector('#go-submit-spinner').style.display = 'none';
                          go_form.querySelector('#go-submit-form-button').disabled = false;
                          if (response.status!==200) {
                            error_span.innerHTML = 'There was an error subscribing you. Please try again.';
                            error_span.style.display = 'block';
                          } else {
                            error_span.style.display = 'none';
                            go_form.querySelector('.dt-form-success').innerHTML = 'You have been subscribed!';
                            go_form.querySelector('input[name="email2"]').value = '';
                            go_form.reset()
                          }
                        }).catch(function (error) {
                          go_form.querySelector('#go-submit-spinner').style.display = 'none';
                          error_span.innerHTML = 'There was an error subscribing you. Please try again.';
                          error_span.style.display = 'block';
                        })
                      });
                    </script>

                </div>
            </section>
            <?php endif; ?>
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
