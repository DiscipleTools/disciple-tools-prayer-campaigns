<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class P4_Ramadan_Porch_Menu
 */
class DT_Prayer_Campaigns_Media_Tab {

    public $token = 'disciple_tools_porch_template';
    public $title = 'Porch Template';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        if ( ! is_admin() ) {
            return;
        }

        // Adds back Media to Disciple.Tools system
        add_filter( 'dt_remove_menu_pages', [ $this, 'add_media_tab' ], 10, 1 );
        if ( isset( $_SERVER['REQUEST_URI'] ) && '/wp-admin/upload.php' === $_SERVER['REQUEST_URI'] ) {
            add_action( 'admin_notices', [ $this, 'admin_notices' ] );
        }

        add_filter( 'upload_mimes', [ $this, 'add_additional_mime_types' ], 1, 1 );

    } // End __construct()

    public function add_media_tab( $list ) {
        if ( isset( $list['media'] ) ) {
            unset( $list['media'] );
        }
        return $list;
    }
    public function add_additional_mime_types( $mime_types ){
        $mime_types['svg'] = 'image/svg+xml'; //Adding svg extension
        $mime_types['psd'] = 'image/vnd.adobe.photoshop'; //Adding photoshop files
        $mime_types['pdf'] = 'application/pdf'; //Adding photoshop files
        $mime_types['docx'] = 'application/vnd.openxmlformats-'; //Adding photoshop files
        $mime_types['doc'] = 'application/msword'; //Adding photoshop files
        $mime_types['csv'] = 'text/csv'; //Adding photoshop files
        $mime_types['zip'] = 'application/zip'; //Adding photoshop files
        return $mime_types;
    }

    public function admin_notices() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>SECURITY WARNING: <BR>ALL IMAGES AND MEDIA FILES ADDED HERE ARE PUBLICLY ACCESSIBLE TO THE INTERNET. <BR>DO NOT STORE SENSITIVE FILES!</p>
        </div>
        <?php
    }
}
DT_Prayer_Campaigns_Media_Tab::instance();
