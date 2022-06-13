<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class P4_Ramadan_Porch_Landing_Enqueue
{
    public static function load_scripts() {
        $lang = dt_ramadan_get_current_lang();
        $translations = dt_ramadan_list_languages();
        if ( isset( $translations[$lang]["dir"] ) && $translations[$lang]['dir'] === 'rtl' ){
            wp_enqueue_style( 'porch-style-css', trailingslashit( plugin_dir_url( __FILE__ ) ) . 'css/rtl.css', array(), filemtime( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'css/rtl.css' ), 'all' );
        }
    }

    public static function load_allowed_scripts() {
        return [
            'jquery',
            'jquery-ui',
            'porch-site-js'
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
