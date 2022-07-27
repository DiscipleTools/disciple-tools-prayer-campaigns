<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class P4_Ramadan_Porch_Landing_Enqueue
{
    public static function load_scripts() {
        $lang = dt_campaign_get_current_lang();
        $translations = dt_campaign_list_languages();
        if ( isset( $translations[$lang]["dir"] ) && $translations[$lang]['dir'] === 'rtl' ){
            wp_enqueue_style( 'porch-style-css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'css/rtl.css', array(), filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'css/rtl.css' ), 'all' );
        }

        wp_deregister_script( 'jquery' );
        wp_enqueue_script( 'my-jquery', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/jquery-min.js", [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/jquery-min.js" ), true );
        wp_enqueue_script( 'tether', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/tether.min.js", [ 'my-jquery' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/tether.min.js" ), true );
        wp_enqueue_script( 'bootstrap', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/bootstrap.min.js", [ 'my-jquery' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/bootstrap.min.js" ), true );
        wp_enqueue_script( 'classie', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/classie.js", [ 'my-jquery' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/classie.js" ), true );
        wp_enqueue_script( 'jquery.stellar', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/jquery.stellar.min.js", [ 'my-jquery' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/jquery.stellar.min.js" ), true );
        wp_enqueue_script( 'jquery.nav', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/jquery.nav.js", [ 'my-jquery' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/jquery.nav.js" ), true );
        wp_enqueue_script( 'smooth-scroll', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/smooth-scroll.js", [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/smooth-scroll.js" ), true );
        wp_enqueue_script( 'smooth-on-scroll', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/smooth-on-scroll.js", [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/smooth-on-scroll.js" ), true );
        wp_enqueue_script( 'wow', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/wow.js", [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/wow.js" ), true );
        wp_enqueue_script( 'menu', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/menu.js", [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/menu.js" ), true );
        wp_enqueue_script( 'jquery.counterup', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/jquery.counterup.min.js", [ 'my-jquery' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/jquery.counterup.min.js" ), true );
        wp_enqueue_script( 'waypoints', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/waypoints.min.js", [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/waypoints.min.js" ), true );
        wp_enqueue_script( 'main', trailingslashit( plugin_dir_url( __FILE__ ) ) . "js/main.js", [], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . "js/main.js" ), true );
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
        ];
    }
}
