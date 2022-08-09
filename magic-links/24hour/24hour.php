<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DT_Prayer_Campaign_24_Hour_Magic_Link extends DT_Magic_Url_Base {

    public $module = "campaigns_24hour_prayer";
    public $post_type = 'campaigns';
    public $page_title = "24 Hour Coverage";

    public $magic = false;
    public $parts = false;
    public $root = "campaign_app"; // define the root of the url {yoursite}/root/type/key/action
    public $type = '24hour'; // define the type
    public $type_name = "Campaigns";
    public $type_actions = [
        '' => "Manage",
        'access_account' => 'Access Account',
        'shortcode' => "Shortcode View"
    ];

    public function __construct(){
        parent::__construct();
        if ( !$this->check_parts_match() ){
            return;
        }

        if ( '' === $this->parts['action'] || 'shortcode' === $this->parts['action'] ) {
            $this->switch_language();
            add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
            add_action( 'dt_blank_body', [ $this, 'dt_blank_body' ], 10 );
        } else {
            return; // fail if no valid action url found
        }

        add_filter( 'dt_magic_url_base_allowed_js', [ $this, 'dt_magic_url_base_allowed_js' ], 10, 1 );
        add_filter( 'dt_magic_url_base_allowed_css', [ $this, 'dt_magic_url_base_allowed_css' ], 10, 1 );
        require_once( 'shortcode-display.php' );
    }

    public function dt_blank_body(){
        $disp = new DT_Campaigns_Fixed_Shortcode_Display( $this->parts );
        $disp->body();
    }

    public function wp_enqueue_scripts(){
        $lang = "en_US";
        if ( isset( $_GET["lang"] ) && !empty( $_GET["lang"] ) ){
            $lang = sanitize_text_field( wp_unslash( $_GET["lang"] ) );
        } elseif ( isset( $_COOKIE["dt-magic-link-lang"] ) && !empty( $_COOKIE["dt-magic-link-lang"] ) ){
            $lang = sanitize_text_field( wp_unslash( $_COOKIE["dt-magic-link-lang"] ) );
        }
        dt_24hour_campaign_register_scripts([
            "root" => $this->root,
            "type" => $this->type,
            "public_key" => $this->parts["public_key"],
            "meta_key" => $this->parts["meta_key"],
            "post_id" => $this->parts["post_id"],
            "rest_url" => rest_url(),
            "lang" => $lang,
        ]);
    }


    public function switch_language(){
        $lang = "en_US";
        if ( isset( $_GET["lang"] ) && !empty( $_GET["lang"] ) ){
            $lang = sanitize_text_field( wp_unslash( $_GET["lang"] ) );
        } elseif ( isset( $_COOKIE["dt-magic-link-lang"] ) && !empty( $_COOKIE["dt-magic-link-lang"] ) ){
            $lang = sanitize_text_field( wp_unslash( $_COOKIE["dt-magic-link-lang"] ) );
        }
        add_filter( 'locale', function ( $locale ) use ( $lang ){
            if ( !empty( $lang ) ){
                return $lang;
            }
            return $locale;
        } );
    }

    // add dt_campaign_core to allowed scripts
    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'dt_campaign_core';
        $allowed_js[] = 'dt_campaign';
        $allowed_js[] = 'luxon';
        return $allowed_js;
    }
    // add dt_campaign_core to allowed scripts
    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'dt_campaign_style';
        return $allowed_css;
    }

}
new DT_Prayer_Campaign_24_Hour_Magic_Link();
