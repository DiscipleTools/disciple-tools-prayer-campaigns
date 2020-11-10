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
            add_action('dt_blank_footer', [$this, 'form_footer' ]);
        }

        if ( dt_prayer_is_contact_url($this->type ) ) {
            add_action('dt_blank_head', [$this, 'contact_head' ]);
            add_action('dt_blank_title', [$this, 'contact_title' ]);
            add_action('dt_blank_body', [$this, 'contact_body' ]);
            add_action('dt_blank_footer', [$this, 'contact_footer' ]);
        }
    }

    public function register_type( array $types ) : array {
        $types[$this->type] = [
            'type' => $this->type,
            'actions' => [
                'edit' => 'edit',
                'instructions' => 'instructions',
                'list' => 'list',
            ]
        ];
        return $types;
    }

    public function load_scripts(){
        wp_register_script( 'prayer-'.$this->type, plugin_dir_url(__FILE__) . $this->type . '-type.js', ['jquery'], filemtime( plugin_dir_path(__FILE__) . $this->type . '-type.js' ) );
        wp_enqueue_script( 'prayer-'.$this->type );
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
            'datepicker',
            'prayer-'.$this->type
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

    public function form_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        ?>
        <style>
            body {
                background-color: inherit;
            }
        </style>
        <?php
    }

    public function contact_head(){
        wp_head(); // styles controlled by wp_print_styles and wp_print_scripts actions
        ?>
        <style>
            body {
                background-color: inherit;
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

        $user = wp_get_current_user();
        $user->add_cap('create_contacts');
        dt_write_log($user);

        // FORM BODY
        ?>
        <div style="max-width:1200px;margin:1em auto;">
            <div class="grid-x grid-padding-x">
                <div class="cell">
                    <h2>Form</h2>
                    <input type="text" placeholder="Name" name="title" id="title" /><br>
                    <button id="new_contact_submit" type="button" class="button small">Submit</button>
                </div>
            </div>
            <hr>
            <div class="grid-x grid-padding-x">
                <div class="cell" id="link">
                    <a href="/prayer/saturation/e49d970b1cadef1523313e384572ae454293264923b93d307777e389820cae8f">Sample Contact</a>
                </div>
            </div>
        </div> <!-- wrapper -->
        <script>
            jQuery(document).ready(function(){
                jQuery('#new_contact_submit').on('click', function(){
                    let title = jQuery('#title').val()
                    console.log(title)

                    jQuery.ajax({
                        type: "POST",
                        data: JSON.stringify({
                            title: title
                        }),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: `<?php echo esc_url_raw(rest_url()) ?>dt-prayer/v1/create`,
                        beforeSend: function (xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest') ?>');
                        }
                    })
                        .done(function(data){
                            if ( ! data ) {
                                return
                            }
                            window.location.href = '<?php echo esc_url_raw(site_url()) ?>/prayer/saturation/' + data
                        })

                })
            })
        </script>
        <?php
    }

    public function form_footer(){
        ?>
        <script>console.log('dt_blank_footer for form')</script>
        <?php
    }

    public function contact_title(){
        ?>
        Blank Form
        <?php
    }

    public function contact_body(){
        $parts = $this->parts;
        // catch contact response
        $actions = dt_prayer_actions( $parts['type'] );
        ?>
        <div style="max-width:1200px;margin:1em auto;">
            <div class="cell center">
                <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' ?>">Form</a>
                | <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] ?>">Home</a>
                <?php foreach( $actions as $action ) { ?>
                    | <a href="<?php echo site_url() . '/' . $parts['root'] . '/'. $parts['type'] . '/' . $parts['public_key'] . '/' . $action  ?>"><?php echo ucfirst( $action ) ?></a>
                <?php } ?>
            </div>
            <hr>
            <div class="cell">
                <h2><?php echo empty( $parts['action'] ) ? 'Home' : ucfirst( $parts['action'] ) ?></h2>

                <div class="grid-x grid-padding-x">
                    <div class="medium-6 cell">
                        Title : <?php echo get_the_title( $parts['post_id'] ) ?>
                    </div>
                    <div class="medium-6 cell">
                        Test
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function contact_footer() {
        ?>
        <script>console.log('dt_blank_footer for contact')</script>
        <?php
    }


}
DT_Prayer_Saturation_Type::instance();
