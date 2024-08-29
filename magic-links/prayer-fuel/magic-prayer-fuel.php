<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


/**
 * Class Disciple_Tools_Resend_Email_Magic_Link
 */
class Campaigns_Prayer_Fuel extends DT_Magic_Url_Base {

    public $magic = false;
    public $parts = false;
    public $page_title = '';
    public $page_description = 'Prayer Fuel';
    public $root = 'prayer';
    public $type = 'fuel';
    public $post_type = 'subscriptions';
    private $meta_key = '';
    public $show_bulk_send = false;
    public $show_app_tile = true;

    private static $_instance = null;
    public $meta = []; // Allows for instance specific data.

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        $this->meta_key = $this->root . '_' . $this->type . '_magic_key';
        $this->page_title = __( 'Prayer Fuel', 'disciple-tools-prayer-campaigns' );
        parent::__construct();

        /**
         * post type and module section
         */
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );


        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        if ( strpos( $url, $this->root . '/' . $this->type ) === false ) {
            return;
        }
        /**
         * tests magic link parts are registered and have valid elements
         */
        if ( !$this->check_parts_match() ){
            return;
        }

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] ); // body for no post key
        add_action( 'dt_blank_head', [ $this, '_header' ] );
        add_action( 'dt_blank_footer', [ $this, '_footer' ] );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );

    }

    public function wp_enqueue_scripts(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) || empty( $post['campaigns'] ) ){
            return;
        }
        $campaign_id = $post['campaigns'][0]['ID'];
        dt_campaigns_register_scripts( $this->parts, $campaign_id );
        $porch_dir = DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/';
        $porch_url = DT_Prayer_Campaigns::get_url_path() . 'porches/generic/site/';
        wp_enqueue_style( 'bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.3/css/bootstrap.min.css', array(), '4.5.3' );
        wp_enqueue_style( 'main-styles', $porch_url . 'css/main.css', array(), filemtime( $porch_dir . 'css/main.css' ) );
        wp_enqueue_style( 'menu_sideslide', $porch_url . 'css/menu_sideslide.css', array(), filemtime( $porch_dir . 'css/menu_sideslide.css' ) );

        wp_enqueue_script( 'main', $porch_url . 'js/main.js', [ 'jquery' ], filemtime( $porch_dir . 'js/main.js' ), true );

        wp_enqueue_script( 'magic_link_scripts', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'magic-link.js', [
            'jquery',
        ], filemtime( plugin_dir_path( __FILE__ ) . 'magic-link.js' ), true );
        wp_localize_script(
            'magic_link_scripts', 'prayer_fuel_scripts', [
                'map_key' => DT_Mapbox_API::get_key(),
                'rest_base' => esc_url( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'translations' => [
                    'Your prayer time has been extended' => __( 'Your prayer time has been extended', 'disciple-tools-prayer-campaigns' ),
                    'signup_up_to_pray' => _x( 'You are signed up to pray %1$s until %2$s.', 'You are signed up to pray daily at 3pm until October 23, 2030.', 'disciple-tools-prayer-campaigns' ),
                    'OK' => __( 'OK', 'disciple-tools-prayer-campaigns' ),
                    'Email me the updated calendar' => __( 'Email me the updated calendar', 'disciple-tools-prayer-campaigns' ),
                    'Prayer Fuel' => __( 'Prayer Fuel', 'disciple-tools-prayer-campaigns' ),
                    'Join me in praying!' => __( 'Join me in praying!', 'disciple-tools-prayer-campaigns' ),

                ],
                'rest_namespace' => $this->root . '/v1/' . $this->type,
                'campaign_url' => DT_Campaign_Landing_Settings::get_landing_page_url( $campaign_id ),
                'auto_extend_prayer_times' => $post['auto_extend_prayer_times'] ?? true,
                'site_url' => site_url(),
            ]
        );
        wp_enqueue_style( 'magic_link_css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'magic-link.css', [], filemtime( plugin_dir_path( __FILE__ ) . 'magic-link.css' ) );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js = [
            'magic_link_scripts',
            'main',
            'jquery',
            'jquery.counterup',
            'bootstrap',
            'dt_campaign_core',
            'luxon'
        ];
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css = [];
        $allowed_css[] = 'magic_link_css';
        $allowed_css[] = 'main-styles';
        $allowed_css[] = 'bootstrap';
        $allowed_css[] = 'menu_sideslide';
        return $allowed_css;
    }

    public function header_javascript(){
        ( new DT_Generic_Porch_Loader() )->load_porch();
        require_once( DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/header.php' );
    }

    public function footer_javascript(){
//        require_once( DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/footer.php' );
    }

    public function body(){
        $post = DT_Posts::get_post( 'subscriptions', $this->parts['post_id'], true, false );
        if ( is_wp_error( $post ) || empty( $post['campaigns'] ) ){
            return;
        }
        if ( isset( $post['lang'] ) && $post['lang'] !== 'en_US' ){
            $lang_code = $post['lang'];
            add_filter( 'determine_locale', function ( $locale ) use ( $lang_code ){
                if ( !empty( $lang_code ) ){
                    return $lang_code;
                }
                return $locale;
            } );
        }
        $this->page_title = __( 'Prayer Fuel', 'disciple-tools-prayer-campaigns' );
        $campaign_id = $post['campaigns'][0]['ID'];
        $campaign_url = DT_Campaign_Landing_Settings::get_landing_root_url();
        $langs = DT_Campaign_Languages::get_enabled_languages( $campaign_id );
        $campaign_fields = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
        $lang = dt_campaign_get_current_lang();

        $color = DT_Campaign_Landing_Settings::get_campaign_color( $campaign_id );

        ?>
        <style>
            :root {
                --cp-color: <?php echo esc_html( $color ) ?>;
                --cp-color-dark: color-mix(in srgb, var(--cp-color), #000 10%);
                --cp-color-light: color-mix(in srgb, var(--cp-color), #fff 70%);
            }
        </style>

        <!-- HEADER -->
        <header id="hero-area" data-stellar-background-ratio="0.5" class="stencil-background">
            <div class="fixed-top">
                <div class="container">
                    <div class="logo-menu">
                        <a href="<?php echo esc_url( !empty( $campaign_fields['logo_link_url'] ) ? $campaign_fields['logo_link_url'] : $campaign_url ) ?>" class="logo"><?php echo esc_html( $campaign_fields['name'] ?? 'Set Up a Campaign' ) ?></a>
                        <div class="d-flex align-items-center">

                            <?php if ( count( $langs ) > 1 ): ?>

                                <select class="dt-magic-link-language-selector">

                                    <?php foreach ( $langs as $code => $language ) : ?>

                                        <?php if ( isset( $language['native_name'] ) ) : ?>
                                            <option value="<?php echo esc_html( $code ); ?>" <?php selected( $lang === $code ) ?>>

                                                <?php echo esc_html( $language['flag'] ?? '' ); ?> <?php echo esc_html( $language['native_name'] ); ?>

                                            </option>
                                        <?php endif; ?>

                                    <?php endforeach; ?>

                                </select>

                            <?php endif; ?>

                            <button class="share-button" id="share-button" aria-label="Share">
                                <img class="dt-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/share.svg' ) ?>"/>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ( !isset( $campaign_fields['enable_overlay_blur'] ) || $campaign_fields['enable_overlay_blur']['key'] === 'yes' ) : ?>
                <div class="overlay"></div>
            <?php endif; ?>
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="contents content-height text-center">

                            <?php if ( !empty( $campaign_fields['logo_url'] ) ) : ?>

                                <img class="logo-image" src="<?php echo esc_url( $campaign_fields['logo_url'] ) ?>" alt=""  />

                            <?php else : ?>

                                <h1 class="wow fadeInDown" style="font-size: 3em;" data-wow-duration="1000ms" data-wow-delay="0.3s">
                                    <?php echo esc_html( DT_Porch_Settings::get_field_translation( 'name' ) ?? 'Set Up A Campaign' ) ?>
                                </h1>

                            <?php endif; ?>

                            <h4>
                                <?php echo esc_html( DT_Porch_Settings::get_field_translation( 'subtitle' ) ?? '' ) ?>
                            </h4>

                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- Header Section End -->
        <?php
        self::display_prayer_fuel( $campaign_id, $post['lang'] );
    }


    public static function display_prayer_fuel( $campaign_id, $lang ){
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $theme_color = DT_Campaign_Landing_Settings::get_campaign_color( $campaign_id );

        $day = isset( $_GET['day'] ) ? sanitize_key( wp_unslash( $_GET['day'] ) ) : null;
        if ( empty( $day ) ){
            $frequency = isset( $campaign['prayer_fuel_frequency']['key'] ) ? $campaign['prayer_fuel_frequency']['key'] : 'daily';
            $day = DT_Campaign_Fuel::what_day_in_campaign( gmdate( 'Y-m-d' ), null, $frequency );
        }

        $today = DT_Campaign_Prayer_Fuel_Post_Type::instance()->get_days_posts( $day );

        ?>

        <section id="contact" class="section">
            <div class="container">
                <div id="alert-section" class="row"></div>

                <div class="row">
                    <div class="section-header col">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( _x( 'Prayer Fuel', 'disciple-tools-prayer-campaigns' ) ) ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>

                </div>

                <div class="row">
                    <?php do_action( 'dt_campaigns_before_prayer_fuel', $today->posts, $day );
                    foreach ( $today->posts as $item ) :
                        dt_campaign_post( $item );
                    endforeach;
                    if ( empty( $today->posts ) && is_numeric( $day ) ):
                        $most_recent = DT_Campaign_Prayer_Fuel_Post_Type::instance()->get_most_recent_post( $day );
                        if ( (int) $day <= 0 ) : ?>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 blog-item">
                                <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
                                    <div class="blog-item-text">
                                        <?php echo esc_html( sprintf( __( 'Content will start in %s days', 'disciple-tools-prayer-campaigns' ), - $day ) ); ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ( !empty( $most_recent ) ) :
                            foreach ( $most_recent->posts as $item ) :
                                dt_campaign_post( $item );
                            endforeach;
                        else : ?>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 blog-item">
                                <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
                                    <div class="blog-item-text">
                                        <?php echo esc_html( __( 'This campaign is finished. See Fuel below', 'disciple-tools-prayer-campaigns' ) ); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if ( $today->found_posts || $most_recent ) : ?>

                    <div class="row" style="margin-top: 30px">
                        <?php dt_campaign_user_record_prayed(); ?>
                    </div>

                    <?php do_action( 'dt_campaigns_after_prayer_fuel', 'after_record_prayed', $today->posts, $day );


                    if ( !isset( $porch_fields['show_prayer_timer']['value'] ) || empty( $porch_fields['show_prayer_timer']['value'] ) || $porch_fields['show_prayer_timer']['value'] !== 'no' ) :
                        if ( function_exists( 'show_prayer_timer' ) ) : ?>
                            <div style="margin-top: 30px">
                                <div class='section-header'>
                                    <h2 class='section-title wow fadeIn' data-wow-duration='1000ms'
                                        data-wow-delay='0.3s'><?php echo esc_html( __( 'Prayer Timer', 'disciple-tools-prayer-campaigns' ) ); ?></h2>
                                    <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                                </div>
                                <div>
                                    <?php echo do_shortcode( "[dt_prayer_timer color='" . $theme_color . "' duration='15' lang='" . $lang . "']" ); ?>
                                </div>
                            </div>
                        <?php endif;
                    endif; ?>
                <?php endif; ?>
            </div>
        </section>
        <!-- Contact Section End -->

        <?php
    }

    /**
     * Register REST Endpoints
     * @link https://github.com/DiscipleTools/disciple-tools-theme/wiki/Site-to-Site-Link for outside of wordpress authentication
     */
    public function add_endpoints() {
        $namespace = $this->root . '/v1';
        register_rest_route(
            $namespace, '/'.$this->type . '/campaign_info', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'campaign_info' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'. $this->type, [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'manage_profile' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
        register_rest_route(
            $namespace, '/'. $this->type . '/email-calendar', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'email_calendar' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function campaign_info( WP_REST_Request $request ) {
        //return DT_Prayer_Subscription_Management_Magic_Link::campaign_info( $request );
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );
        $subscriber_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()

        $campaign_id = $params['campaign_id'] ?? null;
        if ( empty( $campaign_id ) ){
            return new WP_Error( __METHOD__, 'Missing campaign id', [ 'status' => 400 ] );
        }
        $subscriber = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( is_wp_error( $subscriber ) ){
            return;
        }
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );

        $start = (int) DT_Time_Utilities::start_of_campaign_with_timezone( $campaign_id );
        $end = $campaign['end_date']['timestamp'] ?? null;
        if ( $end ){
            $end = (int) DT_Time_Utilities::end_of_campaign_with_timezone( $campaign_id, 12, $start );
        }
        $min_time_duration = DT_Time_Utilities::campaign_min_prayer_duration( $campaign_id );

        $lang = dt_campaign_get_current_lang(); //todo remove?
        dt_campaign_set_translation( $lang );


        //subscriber info
        $my_recurring_signups = DT_Subscriptions::get_recurring_signups( $subscriber_id, $campaign_id );
        $my_commitments = DT_Subscriptions::get_subscriber_prayer_times( $campaign_id, $subscriber_id );

        return [
            'campaign_id' => (int) $campaign_id,
            'start_timestamp' => $start,
            'end_timestamp' => $end,
            'slot_length' => (int) $min_time_duration,
            'current_commitments' => [],
            'enabled_frequencies' => $campaign['enabled_frequencies'] ?? [ 'daily', 'pick' ],
            'frequency_durations' => [
                'daily' => min( is_numeric( $campaign['daily_signup_length'] ) ? (int) $campaign['daily_signup_length'] : 90, 365 ),
                'weekly' => min( is_numeric( $campaign['weekly_signup_length'] ) ? (int) $campaign['weekly_signup_length'] : 180, 365 ),
            ],
            'subscriber_info' => [
                'my_commitments' => $my_commitments,
                'my_recurring_signups' => $my_recurring_signups,
                'timezone' => $subscriber['timezone'] ?? 'America/Chicago',
                'receive_prayer_time_notifications' => $subscriber['receive_prayer_time_notifications'] ?? false,
                'auto_extend_prayer_times' => $subscriber['auto_extend_prayer_times'] ?? true,
            ]
        ];
    }
    public function manage_profile( WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( ! isset( $params['parts'], $params['parts']['meta_key'], $params['parts']['public_key'], $params['action'] ) ) {
            return new WP_Error( __METHOD__, 'Missing parameters', [ 'status' => 400 ] );
        }

        $params = dt_recursive_sanitize_array( $params );
        $action = $params['action'];
        $campaign_id = $params['campaign_id'] ?? null;

        // manage
        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()
        if ( ! $post_id ){
            return new WP_Error( __METHOD__, 'Missing post record', [ 'status' => 400 ] );
        }

        switch ( $action ) {
            case 'update_recurring_signup':
                return DT_Subscriptions::update_recurring_signup( $post_id, $params['report_id'] ?? null, $params );
            default:
                return new WP_Error( __METHOD__, 'Missing valid action', [ 'status' => 400 ] );
        }
    }

    public function email_calendar( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );

        DT_Subscriptions_Base::send_welcome_email( $params['parts']['post_id'], $params['campaign_id'] );
        return true;
    }

}
Campaigns_Prayer_Fuel::instance();
