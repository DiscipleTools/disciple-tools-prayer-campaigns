<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaigns_Menu
 */
class DT_Prayer_Campaigns_Menu {

    public $token = 'dt_prayer_campaigns';

    private static $_instance = null;

    private $campaigns;

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
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-home.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-translations.php';

        add_action( "admin_menu", array( $this, "register_menu" ) );
        add_action( "admin_enqueue_scripts", array( $this, "enqueue_scripts" ) );

        $this->campaigns = new DT_Prayer_Campaigns_Campaigns();
    }

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_submenu_page( 'dt_extensions', 'Prayer Campaigns', 'Prayer Campaigns', 'manage_dt', $this->token, [ $this, 'content' ] );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'dt_campaign_admin_script', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ], filemtime( __DIR__ . '/admin.js' ), true );
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

        if ( isset( $_GET["tab"] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET["tab"] ) );
        } else {
            $tab = 'campaigns';
        }

        $link = 'admin.php?page='.$this->token.'&tab=';


        switch ( $tab ) {
            case "campaigns":
                $this->campaigns->process_email_settings();
                $this->campaigns->process_porch_settings();
                $this->campaigns->process_language_settings();
                break;
            default:
                break;
        }

        if ( $this->has_selected_porch() ) {
            $porch = DT_Prayer_Campaigns::instance()->get_selected_porch_loader();
            $porch_admin = $porch->load_admin();
            $porch_dir = $porch_admin->get_porch_dir();

            $home_tab = new DT_Porch_Admin_Tab_Home( $porch_dir );
            $translations_tab = new DT_Porch_Admin_Tab_Translations( $porch_dir );
        }

        ?>

        <div class="wrap">
            <h2>Prayer Campaigns</h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'campaigns' ?>"
                    class="nav-tab <?php echo esc_html( ( $tab == 'campaigns' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">
                    Campaigns
                </a>

                <?php if ( $this->has_selected_porch() ) : ?>

                    <a href="<?php echo esc_attr( $link . $home_tab->key ) ?>" class="nav-tab <?php echo esc_html( ( $tab == $home_tab->key || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">
                        <?php echo esc_html( $home_tab->title ) ?>
                    </a>
                    <a href="<?php echo esc_attr( $link . $translations_tab->key ) ?>" class="nav-tab <?php echo esc_html( ( $tab == $translations_tab->key || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">
                        <?php echo esc_html( $translations_tab->title ) ?>
                    </a>

                    <?php $porch_admin->tab_headers( $link ); ?>

                <?php endif; ?>
            </h2>

            <?php
            switch ( $tab ) {
                case "campaigns":
                    $this->campaigns->content();
                    break;
                default:
                    break;
            }

            if ( $this->has_selected_porch() ) {
                switch ( $tab ) {
                    case $home_tab->key:
                        $home_tab->content();
                        break;
                    case $translations_tab->key:
                        $translations_tab->content();
                        break;
                    default:
                        break;
                }

                $porch_admin->tab_content();
            }
            ?>

        </div>

        <?php
    }

    private function has_selected_porch() {
        $selected_porch_id = DT_Prayer_Campaigns::instance()->get_selected_porch_id();

        return $selected_porch_id && !empty( $selected_porch_id ) ? true : false;
    }
}

DT_Prayer_Campaigns_Menu::instance();
