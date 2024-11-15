<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Generic_Porch_Contact_Us {
    public $page_title = '';
    public $root = CAMPAIGN_LANDING_ROOT;
    public $type = 'contact-us';
    public $post_type = CAMPAIGN_LANDING_POST_TYPE;
    public $meta_key = CAMPAIGN_LANDING_META_KEY;

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        $this->page_title = __( 'Contact Us', 'disciple-tools-prayer-campaigns' );

        // load if valid url
        add_action( 'dt_blank_body', [ $this, 'body' ] ); // body for no post key
        add_filter( 'dt_blank_title', [ $this, 'dt_blank_title' ] ); // adds basic title to browser tab
    }
    public function dt_blank_title( $title ) {
        return $this->page_title;
    }

    public function body(){
        $campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
        $post_id = $campaign_fields['ID'];
        $lang = dt_campaign_get_current_lang();

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
        $cf_keys = DT_Campaign_Landing_Settings::get_cloudflare_turnstile_keys();

        $atts = [
            'root' => $campaign_root,
            'type' => $campaign_type,
            'public_key' => $key,
            'meta_key' => $key_name,
            'post_id' => (int) $post_id,
            'rest_url' => rest_url(),
            'lang' => $lang,
        ];

        dt_campaigns_register_scripts( $atts, $atts['post_id'] );


        echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>';//phpcs:ignore
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
                        </p>
                        <?php if ( !empty( $cf_keys['dt_cloudflare_site_key'] ) ) : ?>
                            <p>
                            <div
                                class="cf-turnstile"
                                data-sitekey="<?php echo esc_html( $cf_keys['dt_cloudflare_site_key'] ); ?>"
                                data-theme="light"
                                style="margin-top:1em;"
                            ></div>
                            </p>
                        <?php endif; ?>
                        <p id="form-error-section" style="color: red"></p>
                        <p>
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

                    $('#contact-submit-spinner').show()
                    let honey = $('#email').val();
                    if ( honey ){
                        return;
                    }
                    $('#form-error-section').text('');

                    //cloudflare turnstile token
                    const cf_token = $('input[name="cf-turnstile-response"]').val();

                    let name = $('#contact-name').val();
                    let email = $('#email-2').val();
                    let message = $('#contact-message').val()

                    let payload = {
                        'parts': window.campaign_objects.magic_link_parts,
                        campaign_id:  window.campaign_objects.magic_link_parts.post_id,
                        name,
                        email,
                        message,
                        cf_token,
                    };

                    let link = window.campaign_objects.rest_url + window.campaign_objects.magic_link_parts.root + '/v1/' + window.campaign_objects.magic_link_parts.type + '/contact_us';

                    jQuery.ajax({
                        type: 'POST',
                        data: JSON.stringify(payload),
                        contentType: 'application/json; charset=utf-8',
                        dataType: 'json',
                        url: link,
                        beforeSend: (xhr) => {
                          xhr.setRequestHeader("X-WP-Nonce", window.campaign_objects.nonce);
                        },
                    }).done(function(data){
                        $('#contact-submit-spinner').hide()
                        $('#form-content').hide()
                        $('#form-confirm').show()
                    })
                    .fail(function(e) {
                      const message = e.responseJSON?.message || 'There was an error submitting your form. Please try again.';
                      $('#form-error-section').text(message);
                      $('#contact-submit-spinner').hide()
                    })
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
}
DT_Generic_Porch_Contact_Us::instance();
