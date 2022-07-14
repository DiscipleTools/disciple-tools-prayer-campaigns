<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Campaign_Prayer_Fuel_Menu {

    public $token = 'dt_prayer_fuel';
    private $title = 'Prayer Fuel';
    private static $_instance = null;

    /**
     * DT_Prayer_Campaigns_Menu Instance
     *
     * Ensures only one instance of DT_Prayer_Campaigns_Menu is loaded or can be loaded.
     *
     * @since 0.1.0
     * @static
     * @return DT_Prayer_Campaigns_Menu instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {
        if ( !DT_Prayer_Campaigns::instance()->has_selected_porch() ) {
            return;
        }

        add_action( "admin_menu", array( $this, "register_menu" ) );
        add_action( "admin_enqueue_scripts", array( $this, "enqueue_scripts" ) );
    }

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_menu_page( $this->title, $this->title, 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    public function enqueue_scripts() {
        // wp_enqueue_script( 'dt_campaign_admin_script', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ], filemtime( __DIR__ . '/admin.js' ), true );
    }

    /**
     * Menu stub. Replaced when Disciple.Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( 'manage_dt' ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        $table = new DT_Campaign_Prayer_Fuel_Day_List();

        $table->prepare_items();

        ?>

        <div class="wrap">
            <h1 class="wp-heading-inline">Prayer Fuel</h1>

            <a href="post-new.php?post_type=landing" class="page-title-action">Add New</a>
            <hr class="wp-header-end">

            <?php $table->views(); ?>
            <?php $table->display(); ?>

        </div>

        <?php
    }
}

DT_Campaign_Prayer_Fuel_Menu::instance();
