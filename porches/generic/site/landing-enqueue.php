<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class DT_Generic_Porch_Landing_Enqueue
{
    public static function load_scripts() {
        $lang = dt_campaign_get_current_lang();
        $lang_dir = DT_Campaign_Languages::get_language_direction( $lang );
        if ( $lang_dir === 'rtl' ){
            wp_enqueue_style( 'porch-style-css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'css/rtl.css', array(), filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'css/rtl.css' ), 'all' );
        }

        wp_deregister_script( 'jquery' );
        wp_enqueue_script( 'my-jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.min.js', [], '2.1.4', true );
        wp_enqueue_script( 'tether', 'https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.7/js/tether.min.js', [ 'my-jquery' ], '1.4.7', true );
        wp_enqueue_script( 'bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.3/js/bootstrap.min.js', [ 'my-jquery' ], '4.5.3', true );
        wp_enqueue_script( 'classie', 'https://cdnjs.cloudflare.com/ajax/libs/classie/1.0.1/classie.min.js', [ 'my-jquery' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'js/classie.js' ), true );
        wp_enqueue_script( 'jquery.stellar', 'https://cdnjs.cloudflare.com/ajax/libs/stellar.js/0.6.2/jquery.stellar.min.js', [ 'my-jquery' ], '0.6.2', true );
        wp_enqueue_script( 'jquery.nav', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-one-page-nav/3.0.0/jquery.nav.min.js', [ 'my-jquery' ], '3.0.0', true );
        wp_enqueue_script( 'smooth-scroll', 'https://cdnjs.cloudflare.com/ajax/libs/smooth-scroll/16.1.3/smooth-scroll.min.js', [ 'my-jquery' ], '16.1.3', true );
        wp_enqueue_script( 'smooth-on-scroll', 'https://cdnjs.cloudflare.com/ajax/libs/smoothscroll/1.4.10/SmoothScroll.min.js', [ 'my-jquery' ], '1.4.10', true );
        wp_enqueue_script( 'wow', 'https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js', [ 'my-jquery' ], '1.1.2', true );
        wp_enqueue_script( 'waypoints', 'https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js', [ 'my-jquery' ], '4.0.1', true );
        wp_enqueue_script( 'jquery.counterup', 'https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js', [ 'waypoints' ], '1.0.0', true );
        wp_enqueue_script( 'main', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/main.js', [ 'my-jquery', 'jquery.counterup', 'bootstrap' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'js/main.js' ), true );

        //todo, upgrade jquery, upgrade bootstrap, remove counter up, check that each file is needed, bundle them?.
    }

    public static function load_allowed_scripts() {
        return [
            'my-jquery',
            'porch-site-js',
            'tether',
            'bootstrap',
            'classie',
            'jquery.stellar',
            'jquery.nav',
            'smooth-scroll',
            'wow',
            'menu',
            'jquery.counterup',
            'waypoints',
            'main',
        ];
    }

    public static function load_allowed_styles() {
        return [
            'jquery-ui-site-css',
            'porch-style-css',
            'animate-css',
            'themeisle-gutenberg-animation-style',
            'genesis-blocks-style-css',
            'bootstrap',
            'main-styles',
            'font-awesome',
            'line-icons',
            'menu_sideslide',
            'responsive',
            'p4m-colors',
            'wp-block-library',
            'global-styles',
        ];
    }
}
