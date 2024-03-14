<?php
if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly.

class DT_Generic_Porch_Contact_Us extends DT_Magic_Url_Base{
    public $page_title = 'Contact Us';
    public $root = PORCH_LANDING_ROOT;
    public $type = 'contact-us';
    public $post_type = PORCH_LANDING_POST_TYPE;
    public $meta_key = PORCH_LANDING_META_KEY;

    private static $_instance = null;

    public static function instance(){
        if ( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct(){
        $this->page_title = __( 'Contact Us', 'disciple-tools-prayer-campaigns' );
        parent::__construct();
        add_action( 'rest_api_init', [ $this, 'add_endpoints' ] );

        /**
         * tests if other URL
         */
        $url = dt_get_url_path();
        $length = strlen( $this->root . '/' . $this->type );
        if ( substr( $url, 0, $length ) !== $this->root . '/' . $this->type ){
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

    public function dt_magic_url_base_allowed_js( $allowed_js ){
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

    public function dt_magic_url_base_allowed_css( $allowed_css ){
        return DT_Generic_Porch_Landing_Enqueue::load_allowed_styles();
    }

    public function wp_enqueue_scripts(){
        DT_Generic_Porch_Landing_Enqueue::load_scripts();
    }

    public function body(){
        DT_Generic_Porch::instance()->require_once( 'top-section.php' );
        ?>

        <style>
            .wow p {
                font-weight: 600;
            }
        </style>
        <div id="campaign-contact-us">
            <section class="section" data-stellar-background-ratio="0.2">
                <div class="container">
                    <div class="section-header" style="padding-bottom: 40px;">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Contact Us', 'disciple-tools-prayer-campaigns' ); ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>
                    <form onSubmit="event.preventDefault();submit_contact_us_form();return false;" id="form-content" style="max-width: 600px; margin: auto">
                        <p>
                            <label style="width: 100%">
                                <?php esc_html_e( 'Name', 'disciple-tools-prayer-campaigns' ); ?>
                                <br>
                                <input type="text" id="contact-name" required style="width: 100%">
                            </label>
                        </p>
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
                                <?php esc_html_e( 'Message', 'disciple-tools-prayer-campaigns' ); ?>
                                <br>
                                <textarea id="contact-message" required rows="4" type="text" style="width: 100%"></textarea>
                            </label>
                            <button id="contact-submit-button" class="btn btn-common" style="font-weight: bold; margin-left: 0">
                                <?php esc_html_e( 'Submit', 'disciple-tools-prayer-campaigns' ); ?>
                                <img id="contact-submit-spinner" style="display: none; margin-left: 10px" src="<?php echo esc_url( trailingslashit( get_stylesheet_directory_uri() ) ) ?>spinner.svg" width="22px;" alt="spinner "/>
                            </button>
                        </p>
                    </form>
                    <div id="form-confirm" class="section-header" style="display: none">
                        <h3 class="section-subtitle"><?php esc_html_e( 'Thank you', 'disciple-tools-prayer-campaigns' ); ?></h3>
                    </div>
                </div>
            </section>
            <script>

                let submit_contact_us_form = function (){
                    $('#contact-submit-spinner').show();
                    let honey = $('#email').val();
                    if ( honey ){
                        return;
                    }

                    let payload = {
                        'parts': jsObject.parts,
                        'name': $('#contact-name').val(),
                        'email': $('#email-2').val(),
                        'message': $('#contact-message').val()
                    };

                    jQuery.ajax({
                        type: 'POST',
                        data: JSON.stringify(payload),
                        contentType: 'application/json; charset=utf-8',
                        dataType: 'json',
                        url: jsObject.root + jsObject.parts.root + /v1/ + jsObject.parts.type,
                        beforeSend: (xhr) => {
                            xhr.setRequestHeader("X-WP-Nonce", jsObject.nonce);
                        },
                    }).done(function(data){
                        $('#contact-submit-spinner').show()
                        $('#form-content').hide()
                        $('#form-confirm').show()
                    })
                    .fail(function(e) {
                        console.log(e);
                    });
                }

            </script>
        </div>
        <?php
    }

    public function footer_javascript(){
        require_once( 'footer.php' );
    }

    public function header_javascript(){
        require_once( 'header.php' );
    }

    public function add_endpoints(){
        $namespace = $this->root . '/v1/';
        register_rest_route(
            $namespace, $this->type, [
                [
                    'methods' => 'POST',
                    'callback' => [ $this, 'contact_us' ],
                    'permission_callback' => function( WP_REST_Request $request ) {
                        return true;
                    }
                ]
            ]
        );
    }

    public function contact_us( WP_REST_Request $request ){
        $params = $request->get_params();
        $params = dt_recursive_sanitize_array( $params );

        if ( !isset( $params['message'], $params['email'], $params['name'] ) ) {
            return false;
        }

        $name = $params['name'];
        $email = $params['email'];
        $message = wp_kses_post( $request->get_params()['message'] );

        $campaign = DT_Campaign_Settings::get_campaign();
        if ( empty( $campaign ) ){
            return false;
        }
        $campaign_id = $campaign['ID'];

        // Get shared with.
        $mention = '';
        $users = DT_Posts::get_shared_with( 'campaigns', $campaign_id, false );
        foreach ( $users as $user ){
            $mention .= dt_get_user_mention_syntax( $user['user_id'] );
            $mention .= ', ';
        }
        $comment = 'Message on Contact Us by ' . $name . ' ' . $email . ": \n" . $message;

        $added_comment = DT_Posts::add_post_comment( 'campaigns', $campaign_id, $mention . $comment, 'contact_us', [], false );

        $subs = DT_Posts::list_posts( 'subscriptions', [ 'campaigns' => [ $campaign_id ], 'contact_email' => [ $email ] ], false );
        if ( sizeof( $subs['posts'] ) === 1 ){
            DT_Posts::add_post_comment( 'subscriptions', $subs['posts'][0]['ID'], $comment, 'contact_us', [], false );
        }

        do_action( 'campaign_contact_us', $campaign_id, $name, $email, $message );
        return $added_comment;
    }
}

DT_Generic_Porch_Contact_Us::instance();

