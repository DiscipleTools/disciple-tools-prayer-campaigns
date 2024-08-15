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
        $porch_dir = DT_Prayer_Campaigns::get_dir_path() . 'porches/generic/site/';
        $porch_url = DT_Prayer_Campaigns::get_url_path() . 'porches/generic/site/';
        wp_enqueue_style( 'bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.3/css/bootstrap.min.css', array(), '4.5.3' );
        wp_enqueue_style( 'main-styles', $porch_url . 'css/main.css', array(), filemtime( $porch_dir . 'css/main.css' ) );
        wp_enqueue_style( 'menu_sideslide', $porch_url . 'css/menu_sideslide.css', array(), filemtime( $porch_dir . 'css/menu_sideslide.css' ) );

        wp_enqueue_script( 'my-jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js', [], '2.1.4', true );
        wp_enqueue_script( 'main', $porch_url . 'js/main.js', [ 'my-jquery' ], filemtime( $porch_dir . 'js/main.js' ), true );

        wp_enqueue_script( 'magic_link_scripts', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'magic-link.js', [
            'jquery',
            'lodash',
        ], filemtime( plugin_dir_path( __FILE__ ) . 'magic-link.js' ), true );
        wp_localize_script(
            'magic_link_scripts', 'jsObject', [
                'map_key' => DT_Mapbox_API::get_key(),
                'rest_base' => esc_url( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'parts' => $this->parts,
                'translations' => [],
                'rest_namespace' => $this->root . '/v1/' . $this->type,
            ]
        );
        wp_enqueue_style( 'magic_link_css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'magic-link.css', [], filemtime( plugin_dir_path( __FILE__ ) . 'magic-link.css' ) );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js = [
            'magic_link_scripts',
            'main',
            'my-jquery',
            'jquery.counterup',
            'bootstrap'
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


        ?>
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

                            <button class="menu-button" id="open-button" aria-label="Menu Open"><i class="lnr lnr-menu"></i></button>
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
                                <?php display_translated_field( 'subtitle' ); ?>
                            </h4>

                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- Header Section End -->
        <?php
        self::display_prayer_fuel( $campaign_id, $post['lang'] );
        ?>


        <?php
    }


    public static function display_prayer_fuel( $campaign_id, $lang ){
        $campaign = DT_Posts::get_post( 'campaigns', $campaign_id, true, false );
        $theme_color =
        $theme_color = defined( 'CAMPAIGN_LANDING_COLOR_SCHEME_HEX' ) ? CAMPAIGN_LANDING_COLOR_SCHEME_HEX : '#4676fa';
        if ( !empty( $campaign['custom_theme_color'] ) ){
            $theme_color = $campaign['custom_theme_color'];
        }


        $day = isset( $_GET['day'] ) ? sanitize_key( wp_unslash( $_GET['day'] ) ) : null;
        if ( empty( $day ) ){
            $frequency = isset( $campaign['prayer_fuel_frequency']['key'] ) ? $campaign['prayer_fuel_frequency']['key'] : 'daily';
            $day = DT_Campaign_Fuel::what_day_in_campaign( gmdate( 'Y-m-d' ), null, $frequency );
        }

        $today = DT_Campaign_Prayer_Fuel_Post_Type::instance()->get_days_posts( $day );

        ?>

        <section id="contact" class="section">
            <div class="container">
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
            $namespace, '/' . $this->type, [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'access_account' ],
                    'permission_callback' => function( WP_REST_Request $request ){
                        $magic = new DT_Magic_URL( $this->root );
                        return $magic->verify_rest_endpoint_permissions_on_post( $request );
                    },
                ],
            ]
        );
    }

    public function access_account( WP_REST_Request $request ) {
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );

        $post_id = $params['parts']['post_id']; //has been verified in verify_rest_endpoint_permissions_on_post()

        if ( empty( $params['email'] ) ){
            return new WP_Error( __METHOD__, 'Missing required parameter.', [ 'status' => 400 ] );
        }

        DT_Prayer_Campaigns_Send_Email::send_account_access( $post_id, $params['email'] );

        return $params;
    }

}
Campaigns_Prayer_Fuel::instance();
