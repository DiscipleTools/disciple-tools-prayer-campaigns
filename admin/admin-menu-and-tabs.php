<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaigns_Menu
 */
class DT_Prayer_Campaigns_Menu {

    public $token = 'dt_prayer_campaigns';
    private $title = 'Prayer Campaigns';
    private static $_instance = null;

//    private $porch_selector;

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
        add_action( 'admin_menu', array( $this, 'register_menu' ) );

        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
        if ( $page != $this->token ) {
            return;
        }

        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-campaign-settings.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-translations.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-starter-content.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-email-settings.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-new-campaign.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-scripts.php';

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'dt_options_script_pages', array( $this, 'dt_options_script_pages' ) );

//        add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 140 );
    }

//    public function after_setup_theme(){
//        $this->porch_selector = DT_Porch_Selector::instance();
//    }

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        $icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTAwcHQiIGhlaWdodD0iMTAwcHQiIHZlcnNpb249IjEuMSIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiA8ZyBmaWxsPSIjZmZmIj4KICA8cGF0aCBkPSJtNDMuMTAyIDI2LjYwMmMtMy4xOTkyLTAuNjAxNTYtNy4xMDE2IDEuMTAxNi04LjYwMTYgNy4zMDA4LTAuODAwNzggMy4zOTg0LTAuNjAxNTYgOC44MDA4LTAuMzAwNzggMTQuNXYxYzAuMTAxNTYgMS4zMDA4LTEuMTAxNiAyLjM5ODQtMi41IDIuMTk5Mi0xLjEwMTYtMC4xMDE1Ni0xLjgwMDgtMS4xMDE2LTEuODk4NC0yLjE5OTJ2LTAuODk4NDRjLTAuMTk5MjItNC41LTAuMzAwNzgtOC41LTAuMTAxNTYtMTEuODk4IDAuMTAxNTYtMS4zOTg0IDAuMzAwNzgtMi42OTkyIDAuNS0zLjgwMDggMS4xMDE2LTQuODAwOCAzLjYwMTYtNy44OTg0IDYuNS05LjM5ODQgMS4zMDA4LTQuNjk5MiAyLjYwMTYtOS4xMDE2IDMuNjk5Mi0xMS42OTkgMy4xOTkyLTcuNjAxNi02LjE5OTItMTMtMTEuMzAxLTYuMzAwOC01LjEwMTYgNi42OTkyLTIxLjEwMiAzMC4xOTktMjEgNDIuODAxIDAgNS42OTkyIDEuMzk4NCAxNC42MDIgMy42MDE2IDIzIDAuMzk4NDQgMS4zMDA4IDAgMi42OTkyLTAuODAwNzggMy42OTkybC0zLjY5OTIgNC4zOTg0Yy0xLjEwMTYgMS4zMDA4LTAuODk4NDQgMy4zMDA4IDAuMzk4NDQgNC4zOTg0bDE1LjMwMSAxM2MxLjMwMDggMS4xMDE2IDMuMzAwOCAwLjg5ODQ0IDQuMzk4NC0wLjM5ODQ0IDIuMTk5Mi0yLjY5OTIgNS42OTkyLTcuMTAxNiA4LjY5OTItMTEuMTAyIDYuMTAxNi04LjE5OTIgMTAtMTcuNjAyIDEwLjgwMS0yMi4zMDEgMC42OTkyMi00LjE5OTIgMC44MDA3OC0xMC4xOTkgMC42MDE1Ni0xNi0wLjEwMTU2LTUtMC4xOTkyMi0xMC4zMDEgMC4zMDA3OC0xNC42OTkgMC41LTMuMTAxNi0yLTUuMTAxNi00LjU5NzctNS42MDE2eiIvPgogIDxwYXRoIGQ9Im05Mi42OTkgNzkuMzk4LTMuNjk5Mi00LjM5ODRjLTAuODk4NDQtMS0xLjE5OTItMi4zOTg0LTAuODAwNzgtMy42OTkyIDIuMTk5Mi04LjM5ODQgMy42MDE2LTE3LjE5OSAzLjYwMTYtMjMgMC4xMDE1Ni0xMi42MDItMTUuODk4LTM2LjEwMi0yMS00Mi44MDEtNS4xMDE2LTYuNjk5Mi0xNC41LTEuMzAwOC0xMS4zMDEgNi4zMDA4IDEuMTAxNiAyLjYwMTYgMi4zOTg0IDcgMy42OTkyIDExLjY5OSAyLjg5ODQgMS42MDE2IDUuMzAwOCA0LjYwMTYgNi41IDkuMzk4NCAwLjMwMDc4IDEuMTAxNiAwLjM5ODQ0IDIuMzk4NCAwLjUgMy44MDA4IDAuMTk5MjIgMy4zOTg0IDAuMTAxNTYgNy4zOTg0LTAuMTAxNTYgMTEuODk4djAuODk4NDRjMCAxLjEwMTYtMC44MDA3OCAyLjEwMTYtMS44OTg0IDIuMTk5Mi0xLjM5ODQgMC4xOTkyMi0yLjUtMC44OTg0NC0yLjUtMi4xOTkydi0xYzAuMTk5MjItNS42OTkyIDAuNS0xMS4xOTktMC4zMDA3OC0xNC41LTEuMzk4NC02LjE5OTItNS4zOTg0LTcuODk4NC04LjYwMTYtNy4zMDA4LTIuNjAxNiAwLjUtNS4xMDE2IDIuNS00LjY5OTIgNS42MDE2IDAuNSA0LjM5ODQgMC4zOTg0NCA5LjY5OTIgMC4zMDA3OCAxNC42OTktMC4xMDE1NiA1LjgwMDggMCAxMS44MDEgMC42MDE1NiAxNiAwLjgwMDc4IDQuNjk5MiA0LjYwMTYgMTQuMTAyIDEwLjgwMSAyMi4zMDEgMyA0IDYuNSA4LjM5ODQgOC42OTkyIDExLjEwMiAxLjEwMTYgMS4zOTg0IDMuMTAxNiAxLjYwMTYgNC4zOTg0IDAuMzk4NDRsMTUuMzAxLTEzYzEuNDAyMy0xLjA5NzcgMS42MDE2LTMuMDk3NyAwLjUtNC4zOTg0eiIvPgogPC9nPgo8L3N2Zz4K';
        add_menu_page( $this->title, $this->title, 'wp_api_allowed_user', $this->token, [ $this, 'content' ], $icon, 2 );
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'dt_campaign_admin_style', plugin_dir_url( __FILE__ ) . 'admin.css', [], filemtime( __DIR__. '/admin.css' ) );
        wp_enqueue_script( 'dt_campaign_admin_script', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ], filemtime( __DIR__ . '/admin.js' ), true );
        wp_localize_script(
            'dt_campaign_admin_script', 'dt_campaign_admin', [
                'root' => esc_url_raw( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' )
            ]
        );
    }

    public function dt_options_script_pages( $allowed_pages ) {
        $allowed_pages[] = $this->token;

        return $allowed_pages;
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

        if ( !( current_user_can( 'manage_dt' ) || current_user_can( 'wp_api_allowed_user' ) ) ) { // manage dt is a permission that is specific to Disciple.Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        if ( isset( $_GET['tab'] ) ) {
            $tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
        } else {
            if ( current_user_can( 'create_campaigns' ) ){
                $tab = 'campaigns';
            } else {
                $tab = 'dt_prayer_fuel';
            }
        }
        $campaign_id = isset( $_GET['campaign'] ) ? sanitize_key( wp_unslash( $_GET['campaign'] ) ) : null;
//        $porch = $this->porch_selector->get_selected_porch_loader();
//        $porch_admin = $porch->load_admin();

        $tabs = apply_filters( 'prayer_campaign_tabs', [] );

        ?>
        <!-- MODALS -->
        <dialog id="clone_dialog">
            <table style="min-width: 100%;">
                <tbody>
                <tr>
                    <td style="padding-right: 10px; vertical-align: middle;">
                        <label for="clone_modal_new_name">New Name</label>
                    </td>
                    <td>
                        <input id="clone_modal_new_name" name="clone_modal_new_name" type="text" style="width: 100%;" placeholder="Specify new name for cloned campaign." />
                    </td>
                </tr>
                <tr>
                    <td style="padding-right: 10px; vertical-align: middle;">
                        <label for="clone_modal_campaign">Campaign</label>
                    </td>
                    <td>
                        <select id="clone_modal_campaign" name="clone_modal_campaign" style="width: 100%;" disabled>
                            <?php DT_Prayer_Campaigns_Campaigns::echo_my_campaigns_select_options( $campaign_id ) ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <br>
                        <span id="clone_modal_spinner" class="loading-spinner active" style="margin-left: 5px; display: none;"></span>
                        <span id="clone_modal_msg"></span>
                    </td>
                </tr>
                </tbody>
            </table>
        </dialog>
        <!-- MODALS -->

        <div class="wrap">
            <h2>Prayer Campaigns</h2>
            <div>
                <a class="button <?php echo esc_html( $tab === 'campaigns' ? 'button-primary' : '' ); ?>" href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns' ) ); ?>">
                    General Settings
                </a>
                <a class="button <?php echo esc_html( $tab === 'new_campaign' ? 'button-primary' : '' ); ?>" href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=new_campaign' ) ); ?>">
                    Create a New Campaign
                </a>
                <a class="button" href="https://prayer.tools/docs/overview/" target="_blank">See Help Documentation</a>
            </div>
            <?php
            if ( !empty( $campaign_id ) ){
                $this->campaign_selector();
                $campaign = DT_Campaign_Landing_Settings::get_campaign( $campaign_id );
                $campaign_url_part = '';
                if ( !empty( $campaign['ID'] ) ){
                    $campaign_url_part .= '&campaign=' . $campaign['ID'];
                }

                $link = 'admin.php?page='. $this->token . $campaign_url_part . '&tab=';
                ?>
                <h2 class="nav-tab-wrapper">
                    <?php foreach ( $tabs as $key => $title ) :
                        $tab_link = $link . $key;
                        if ( $key === 'starter-content' ){
                            $tab_link .= '&import=true';
                        }
                        ?>

                        <a href="<?php echo esc_attr( $tab_link ) ?>"
                           class="nav-tab <?php echo esc_html( ( $tab == $key ) ? 'nav-tab-active' : '' ); ?>">
                            <?php echo esc_html( $title ) ?>
                        </a>
                    <?php endforeach; ?>
                </h2>
            <?php }

            //hook in and display content
            do_action( 'dt_prayer_campaigns_tab_content', $tab, $campaign_id );

            ?>
        </div>
        <?php
    }

    public function campaign_selector(){
        //todo set default campaign
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $campaign_id = isset( $_GET['campaign'] ) ? sanitize_key( wp_unslash( $_GET['campaign'] ) ) : null;
        if ( empty( $campaign ) ){
            $campaigns = DT_Posts::list_posts( 'campaigns', [] );
            if ( !empty( $campaigns['posts'] ) ){
                $campaign = $campaigns['posts'][0];
            }
        }
        if ( empty( $campaign ) ){
            return;
        }
        $tab = sanitize_key( wp_unslash( !empty( $_GET['tab'] ) ? $_GET['tab'] : 'campaigns' ) );
        $landing_page_url = DT_Campaign_Landing_Settings::get_landing_page_url( $campaign['ID'] );
        $campaign_color = DT_Campaign_Landing_Settings::get_campaign_color( $campaign['ID'], 50 );
        $campaign_color2 = DT_Campaign_Landing_Settings::get_campaign_color( $campaign['ID'], 30 );
        ?>

        <br>
        <form method="GET" >
            <input type="hidden" name="page" value="dt_prayer_campaigns"/>
            <input type="hidden" name="tab" value="<?php echo esc_html( $tab ); ?>"/>

            <table class="widefat striped metabox-table">
                <thead>
                <tr style="background-color: <?php echo esc_html( $campaign_color ) ?>">
                    <th>Select Campaign to edit settings</th>
                </tr>
                </thead>
                <tbody>
                <tr style="background-color: <?php echo esc_html( $campaign_color2 ) ?>">
                    <td>
                        <select name="campaign" id="campaign-selection" onchange="this.form.submit()">
                            <?php DT_Prayer_Campaigns_Campaigns::echo_my_campaigns_select_options( $campaign_id ) ?>
                        </select>
                        <a href="<?php echo esc_html( $landing_page_url ); ?>">Show Landing Page</a> |

                        <a href="<?php echo esc_html( site_url() . '/subscriptions/?filter_id=campaign_' . $campaign['ID'] ); ?>">Subscribers List</a> |
                        <a href="<?php echo esc_html( site_url() . '/campaigns/' . $campaign['ID'] ); ?>">Advanced Edit</a>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <?php
    }

}
