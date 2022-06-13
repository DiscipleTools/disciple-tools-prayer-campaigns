<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Ramadan_Porch_Menu
 */
class DT_Generic_Porch_Admin_Menu implements IDT_Porch_Admin_Menu {

    public $token = 'dt_porch_generic';
    public $title = 'Settings';

    public function __construct() {
        if ( ! is_admin() ) {
            return;
        }

        require_once __DIR__ . '/dt-generic-porch-landing-tab-home.php';
        require_once __DIR__ . '/dt-generic-porch-landing-tab-starter-content.php';

        if ( isset( $_POST['install_campaign_nonce'], $_POST["download_csv"], $_POST['selected_campaign'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['install_campaign_nonce'] ) ), 'install_campaign_nonce' )
        ){
            add_action( "init", function (){
                $campaign_id = sanitize_text_field( wp_unslash( $_POST['selected_campaign'] ) ); //phpcs:ignore
                $this->download_campaign_csv( $campaign_id );
                exit;
            });
        } else {
            add_action( "admin_menu", array( $this, "register_menu" ) );
            add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );
        }


    } // End __construct()

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        if ( current_user_can( 'wp_api_allowed_user' ) ) {
            add_submenu_page( 'edit.php?post_type=' . PORCH_LANDING_POST_TYPE, $this->title, $this->title, "edit_" . PORCH_LANDING_POST_TYPE, $this->token, [ $this, 'content' ], 0 );
        }
    }

    public function scripts(){
        wp_enqueue_script( 'dt_ramadan_script', plugin_dir_url( __FILE__ ) . '/admin.js', [
            'jquery',
        ], filemtime( plugin_dir_path( __FILE__ ) . '/admin.js' ), true );
    }

    /**
     * Menu stub. Replaced when Disciple.Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds porch tab links
     * @since 0.1
     */
    public function tab_headers( string $link ) {

        $tab = $this->get_tab();

        ?>

        <a href="<?php echo esc_attr( $link ) . 'home' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'home' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">Home Page</a>
        <a href="<?php echo esc_attr( $link ) . 'content' ?>" class="nav-tab <?php echo esc_html( ( $tab == 'content' ) ? 'nav-tab-active' : '' ); ?>">Install Starter Content</a>

        <?php
    }

    public function tab_content() {

        $tab = $this->get_tab();

        switch ( $tab ){
            case "home":
                $object = new DT_Generic_Porch_Landing_Tab_Home();
                $object->content();
                break;
            case "content":
                $object = new DT_Generic_Porch_Landing_Tab_Starter_Content();
                $object->content();
                break;
            default:
                break;
        }
    }

    private function get_tab(): string {
        if ( isset( $_GET["tab"] ) ) {
            return sanitize_key( wp_unslash( $_GET["tab"] ) );
        }

        return "";
    }

    public function download_campaign_csv( $campaign_id, $filename = "subscribers.csv", $delimiter = "," ){
        $subscribers = DT_Posts::list_posts( "subscriptions", [ "campaigns" => [ $campaign_id ], "status" => [ "active" ], 'limit' => 1000 ], false );
        if ( is_wp_error( $subscribers ) ){
            return;
        }
        header( 'Content-Type: application/csv' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
        $f = fopen( 'php://output', 'w' );

        $array = [
            [ "Name", "Email" ],
        ];
        foreach ( $subscribers["posts"] as $sub ){
            $a = [
                $sub['name'],
            ];
            if ( isset( $sub['contact_email'][0]["value"] ) ){
                $a[] = $sub['contact_email'][0]["value"];
            } else {
                $a[] = '';
            }
            $array[] = $a;
        }
        foreach ( $array as $line ){
            fputcsv( $f, $line, $delimiter );
        }
    }
}
