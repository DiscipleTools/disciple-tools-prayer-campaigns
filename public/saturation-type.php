<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Prayer_Saturation_Type
{
    public $parts = false;
    public $type = 'saturation';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        // register type
        add_filter( 'dt_prayer_register_type', [$this, 'register_type'] );

        // fail if not valid url
        $this->parts = dt_prayer_parse_url_parts();
        if ( ! $this->parts ){
            return;
        }

        // fail if does not match type
        if ( $this->type !== $this->parts['type'] ){
            return;
        }

        // load page elements
        add_action( 'wp_enqueue_scripts', [$this, 'load_scripts'], 999 );
        add_action( 'wp_print_scripts', [$this, 'print_scripts'], 1500);
        add_action( 'wp_print_styles', [$this, 'print_styles'], 1500 );

        if ( dt_prayer_is_form_url( $this->type ) ) {
            add_action('dt_blank_head', [$this, 'form_head' ]);
            add_action('dt_blank_title', [$this, 'form_title' ]);
            add_action('dt_blank_body', [$this, 'form_body' ]);
        }

        if ( dt_prayer_is_contact_url($this->type ) ) {
            add_action('dt_blank_head', [$this, 'management_head' ]);
            add_action('dt_blank_title', [$this, 'management_title' ]);
            add_action('dt_blank_body', [$this, 'management_body' ]);
        }
    }

    public function register_type( array $types ) : array {
        $types[$this->type] = [
            'type' => $this->type,
            'actions' => [
                'instructions' => 'instructions',
                'unsubscribe' => 'unsubscribe',
            ]
        ];
        return $types;
    }

    public function load_scripts(){
        wp_register_script( 'prayer-'.$this->type, plugin_dir_url(__FILE__) . $this->type . '-type.js', ['jquery'], filemtime( plugin_dir_path(__FILE__) . $this->type . '-type.js' ) );
        wp_enqueue_script( 'prayer-'.$this->type );
        wp_register_script( 'jquery-steps', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-steps/1.1.0/jquery.steps.min.js', ['jquery'], '1.1.0' );
        wp_enqueue_script( 'jquery-steps' );
    }

    public function print_scripts(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_js = [
            'jquery',
            'jquery-ui',
            'site-js',
            'mapbox-gl',
            'mapbox-cookie',
            'jquery-cookie',
//            'datepicker',
//            'prayer-'.$this->type,
//            'jquery-steps'
        ];

        global $wp_scripts;
        if ( isset( $wp_scripts ) ){
            foreach( $wp_scripts->queue as $key => $item ){
                if ( ! in_array( $item, $allowed_js ) ){
                    unset( $wp_scripts->queue[$key] );
                }
            }
        }
    }
    public function print_styles(){
        // @link /disciple-tools-theme/dt-assets/functions/enqueue-scripts.php
        $allowed_css = [
            'foundation-css',
            'jquery-ui-site-css',
            'site-css',
            'datepicker-css'
        ];

        global $wp_styles;
        if ( isset( $wp_styles ) ) {
            foreach ($wp_styles->queue as $key => $item) {
                if (!in_array($item, $allowed_css)) {
                    unset($wp_styles->queue[$key]);
                }
            }
        }
    }


    /* FORM */
    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        ?>
        <style>
            body {
                background-color: inherit;
            }
            #form-wrapper {
                max-width:1200px;
                margin:1em auto;
            }
            #email {display:none;}
            #map-wrapper {
                height: 400px !important;
            }
            #map {
                height: 400px !important;
            }
        </style>
        <?php
    }
    public function form_title(){
        ?>
        Blank Form
        <?php
    }
    public function form_body(){
        // FORM BODY
        ?>
        <div id="form-wrapper">
            <div class="grid-x grid-padding-x">
                <div class="cell center"><h2>Prayer Subscription</h2></div>
                <div class="cell center">
                    Select Timezone<br>
                    <select name="timezone"></select>
                </div>
                <div class="cell">
                     <div id="map-wrapper">
                        <div id='map'><span class="loading-spinner active"></span></div>
                     </div>
                    <br>
                </div>
                <div class="cell">
                    <input type="text" placeholder="Name" name="title" id="title" /><br>
                    <input type="text" placeholder="Email" name="email" id="email" />
                    <input type="text" placeholder="Email" name="e2" id="e2" /><br>
                    <input type="text" placeholder="Phone" name="phone" id="phone" /><br>
                    <button id="new_contact_submit" type="button" class="button small">Submit</button><br>
                    <div id="error"></div>
                </div>
            </div>
        </div> <!-- form wrapper -->
        <script>
            jQuery(document).ready(function(){

                // init map
                mapboxgl.accessToken = '<?php echo DT_Mapbox_API::get_key() ?>';
                var map = new mapboxgl.Map({
                    container: 'map',
                    style: 'mapbox://styles/mapbox/light-v10',
                    center: [-98, 38.88],
                    minZoom: 1,
                    zoom: 1
                });

                map.on('idle', () => {
                    jQuery('.loading-spinner').removeClass('active')
                })

                jQuery('#new_contact_submit').on('click', function(){
                    let title = jQuery('#title').val()
                    let e2 = jQuery('#e2').val()
                    let email = jQuery('#email').val()
                    let phone = jQuery('#phone').val()

                    if ( email !== '' ){
                        jQuery('#form-wrapper').empty().html(`Shame, shame, we know your name ... robot!`)
                    }

                    let source = 'web'
                    if ( window.location.hash !== ''){
                        source = window.location.hash.substring(1)
                    }

                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({
                            title: title,
                            overall_status: 'new',
                            sources: {
                                values: [
                                    { value: source}
                                ]
                            },
                            contact_email: {
                                values: [
                                    { value: e2 }
                                ]
                            },
                            contact_phone: {
                                values: [
                                    { value: phone }
                                ]
                            },
                            location_grid: {
                                values: [
                                    { value: '100089589' }
                                ]
                            },
                            email: email,
                            type: 'saturation'
                        }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: `<?php echo esc_url_raw(rest_url()) ?>dt-prayer/v1/create`,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest') ?>');
                        }
                    })
                        .done(function(data){
                            show_success( data ) /* temporary public hash*/
                        })
                        .fail(function(e) {
                            console.log(e)
                            jQuery('#error').html(e)
                        })
                })

                function show_success( data ){
                    let new_url = '<?php echo esc_url_raw(site_url()) ?>/prayer/saturation/' + data
                    jQuery('#form-wrapper').empty().html(`
                       <div class="grid-x grid-padding-x">
                            <div class="cell">
                                <h2 class="center">Success! Check your email to confirm.</h2>
                                <div class="center">Once you confirm your subscription we'll start connecting you with prayer needs for the areas you indicated.</div>
                            </div>
                            <div class="cell"><br><br><hr><br><br><br></div>
                            <div class="cell center"><h2 class="center">Sample Email</h2></div>
                            <div class="cell center">
                            <hr style="width:50%;">
                            Thank you for subscribing to prayer for a disciple making movement in  ______. <br>
                            <a class="button large" href="${new_url}" style="font-size:2em;margin: 1em 0;">CONFIRM PRAYER SUBSCRIPTION</a>
                            </div>
                        </div>
                    `)
                }

            })
        </script>
        <?php
    }


    public function management_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions

        ?>
        <style>
            body {
                background-color: inherit;
            }
        </style>
        <?php
    }
    public function management_title(){
        ?>
        Blank Form
        <?php
    }
    public function management_body(){
        $parts = $this->parts;
        dt_prayer_confirm_subscription( $parts['post_id'], $parts['type'] );

        // catch contact response
        $actions = dt_prayer_actions( $parts['type'] );

        switch( $parts['action'] ) {
            case 'instructions':
            case 'unsubscribe':
                ?>
                <div style="max-width:1200px;margin:1em auto;">
                    <div class="cell center">
                        <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ?>">Home</a>
                        <?php foreach( $actions as $action ) { ?>
                            | <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $action  ?>"><?php echo ucfirst( $action ) ?></a>
                        <?php } ?>
                    </div>
                    <hr>
                </div>
                <?php
                break;
            default:
                ?>
                <div style="max-width:1200px;margin:1em auto;">
                    <div class="cell center">
                        <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ?>">Home</a>
                        <?php foreach( $actions as $action ) { ?>
                            | <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $action  ?>"><?php echo ucfirst( $action ) ?></a>
                        <?php } ?>
                    </div>
                    <hr>
                    <div class="cell">
                        <h2>Hello, <?php echo get_the_title( $parts['post_id'] ) ?></h2>
                        <p>
                            Thank you for praying for disciple making movements! We have not because we ask not, says James.
                        </p>
                        <p>
                            <input name="name" value="<?php echo get_the_title( $parts['post_id'] ) ?>" type="text" />
                        </p>
                        <p>
                            <input name="name" value="<?php echo get_the_title( $parts['post_id'] ) ?>" type="text" />
                        </p>
                        <p>
                            <input name="name" value="<?php echo get_the_title( $parts['post_id'] ) ?>" type="text" />
                        </p>
                        <p>
                            <button type="button" class="button small">Update</button>
                        </p>
                    </div>
                    <div class="cell">
                        <h2>Manage your prayer locations:</h2>
                        <table>
                            <thead>
                            <tr>
                                <td>
                                    Subscription
                                </td>
                                <td style="width:100px;">
                                    Action
                                </td>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    You are praying for ______________
                                </td>
                                <td>
                                    <button type="button" class="button small">Unsubscribe</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    You are praying for ______________
                                </td>
                                <td>
                                    <button type="button" class="button small">Unsubscribe</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    You are praying for ______________
                                </td>
                                <td>
                                    <button type="button" class="button small">Unsubscribe</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="cell">
                        <p>
                            <button type="button" class="button small">Unsubscribe from all</button>
                        </p>
                    </div>
                </div>
                <?php
                break;
        }

    }

}
DT_Prayer_Saturation_Type::instance();
