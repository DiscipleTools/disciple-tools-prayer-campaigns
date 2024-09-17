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

        /**
         * Tether is a JavaScript library for efficiently making an absolutely positioned element stay next to another element on the page.
         * For example, you might want a tooltip or dialog to open, and remain, next to the relevant item on the page.
         */
        wp_enqueue_script( 'bootstrap', 'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.3/js/bootstrap.min.js', [ 'jquery' ], '4.5.3', true );
        /**
         * Classie - class helper functions
         * Landing Page side menu
         */
        wp_enqueue_script( 'classie', 'https://cdnjs.cloudflare.com/ajax/libs/classie/1.0.1/classie.min.js', [ 'jquery' ], '1.0.1', true );
        /**
         * WOW.js - Reveal Animations When You Scroll
         * Makes the sections appear with animation
         */
        wp_enqueue_script( 'wow', 'https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js', [ 'jquery' ], '1.1.2', true );
        wp_enqueue_script( 'main', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/main.js', [ 'jquery', 'bootstrap' ], filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'js/main.js' ), true );
    }

    public static function load_allowed_scripts() {
        return [
            'jquery',
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
