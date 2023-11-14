<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Generic_Email_Confirmation extends DT_Magic_Url_Base {
    public $page_title = 'Email Confirmation';
    public $root = PORCH_LANDING_ROOT;
    public $type = 'email-confirmation';
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
        $page_info = DT_Campaign_Landing_Settings::determine_campaign_via_url( [ 'email-confirmation' ] );

        if ( empty( $page_info ) ) {
            return;
        }
        if ( $page_info['current_page'] !== 'email-confirmation' ){
            return;
        }
        if ( !empty( $page_info['root'] ) ){
            $this->root = $page_info['root'];
        }
        $campaign_id = $page_info['campaign_id'];

        parent::__construct();

        add_filter( 'dt_templates_for_urls', [ $this, 'register_url' ] );
        add_filter( 'dt_blank_access', function() {
            return true;
        });
        add_action( 'dt_blank_head', [ $this, '_header' ] );
        add_action( 'dt_blank_footer', [ $this, '_footer' ] );
        add_action( 'dt_blank_body', [ $this, 'body' ] );

        require_once( 'landing-enqueue.php' );

        require_once( 'enqueue.php' );

        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
//        add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 99 );
//        add_filter( 'language_attributes', [ $this, 'dt_custom_dir_attr' ] );
    }

    public function dt_custom_dir_attr( $lang ){
        return dt_campaign_custom_dir_attr( $lang );
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        return [];
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        return [];
    }

    public function wp_enqueue_scripts() {
        return;
    }

    public function register_url( $templates_for_urls ) {
        $url = dt_get_url_path();

        $templates_for_urls[$url] = 'template-blank.php';

        return $templates_for_urls;
    }

    public function body() {
        $lang = dt_campaign_get_current_lang();
        dt_campaign_set_translation( $lang );
        $campaign_link = DT_Campaign_Landing_Settings::get_landing_root_url();
        ?>
        <style>
            .success-confirmation-section .button {
                border: 1px solid transparent;
                border-radius: 5px;
                cursor: pointer;
                display: inline-block;
                font-family: inherit;
                font-size: .9rem;
                line-height: 1;
                margin: 0 0 1rem;
                padding: 0.85em 1em;
                text-align: center;
                color: white;
                background-color: <?php echo esc_html( PORCH_COLOR_SCHEME_HEX ); ?>;
            }
            .success-confirmation-section .button:hover {
                background-color: transparent;
                border-color: <?php echo esc_html( PORCH_COLOR_SCHEME_HEX ); ?>;
                color: <?php echo esc_html( PORCH_COLOR_SCHEME_HEX ); ?>;
            }
        </style>
        <div style='margin-top: 140px; padding:20px; font-size: 1.5rem; text-align: center' >
            <?php success_confirmation_section( $campaign_link ); ?>
        </div>
        <?php
    }


}

new DT_Generic_Email_Confirmation();
