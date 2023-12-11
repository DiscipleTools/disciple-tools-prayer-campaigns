<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaigns_Menu
 */
class DT_Prayer_Campaigns_Menu {

    public $token = 'dt_prayer_campaigns';
    private $title = 'Prayer Campaigns';
    private static $_instance = null;

    private $campaigns;
    private $porch_selector;

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

        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-home.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-translations.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-starter-content.php';
        require_once trailingslashit( __DIR__ ) . 'dt-porch-admin-tab-email-settings.php';

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'dt_options_script_pages', array( $this, 'dt_options_script_pages' ) );

        add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 140 );
    }

    public function after_setup_theme(){
        $this->campaigns = new DT_Prayer_Campaigns_Campaigns();
        $this->porch_selector = DT_Porch_Selector::instance();
    }

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_menu_page( $this->title, $this->title, 'wp_api_allowed_user', $this->token, [ $this, 'content' ], null, 7 );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'dt_campaign_admin_script', plugin_dir_url( __FILE__ ) . 'admin.js', [ 'jquery' ], filemtime( __DIR__ . '/admin.js' ), true );
        wp_enqueue_style( 'dt_campaign_admin_style', plugin_dir_url( __FILE__ ) . 'admin.css', [], filemtime( __DIR__. '/admin.css' ) );
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
        $campaign = DT_Campaign_Landing_Settings::get_campaign( null, true );
        $campaign_url_part = '';
        if ( !empty( $campaign['ID'] ) ){
            $campaign_url_part .= '&campaign=' . $campaign['ID'];
        }

        $link = 'admin.php?page='. $this->token . $campaign_url_part . '&tab=';


        switch ( $tab ) {
            case 'campaigns':
                $this->campaigns->process_p4m_participation_settings();
                $this->campaigns->process_porch_settings();
                $this->campaigns->process_default_campaign_setting();
                break;
            default:
                break;
        }

        $porch = $this->porch_selector->get_selected_porch_loader();
        $porch_admin = $porch->load_admin();
        $porch_dir = $porch_admin->get_porch_dir();

        $home_tab = new DT_Porch_Admin_Tab_Home( $porch_dir );
        $translations_tab = new DT_Porch_Admin_Tab_Translations( $porch_dir );
        $prayer_content_tab = new DT_Porch_Admin_Tab_Starter_Content( $porch_dir );
        $prayer_fuel_tab = new DT_Campaign_Prayer_Fuel_Menu();
        $email_settings_tab = new DT_Porch_Admin_Tab_Email_Settings( $porch_dir );


        ?>

        <div class="wrap">
            <h2>Prayer Campaigns</h2>
            <h2 class="nav-tab-wrapper">
                <?php if ( current_user_can( 'create_campaigns' ) ) : ?>
                    <a href="<?php echo esc_attr( $link ) . 'campaigns' ?>"
                        class="nav-tab <?php echo esc_html( ( $tab == 'campaigns' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">
                        General Settings
                    </a>
                <?php endif; ?>
                <?php if ( $this->has_selected_porch() && current_user_can( 'create_campaigns' ) ) : ?>
                    <a href="<?php echo esc_attr( $link . $translations_tab->key ) ?>" class="nav-tab <?php echo esc_html( ( $tab == $translations_tab->key ) ? 'nav-tab-active' : '' ); ?>">
                        <?php echo esc_html( $translations_tab->title ) ?>
                        <?php if ( !DT_Porch_Settings::has_user_translations() ): ?>
                            <img style="width: 20px; vertical-align: sub" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/broken.svg' ) ?>"/>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <?php if ( $this->has_selected_porch() && current_user_can( 'create_campaigns' ) ) : ?>
                    <a href="<?php echo esc_attr( $link . $home_tab->key ) ?>" class="nav-tab <?php echo esc_html( ( $tab == $home_tab->key || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">
                        <?php echo esc_html( $home_tab->title ) ?>
                    </a>
                <?php endif; ?>
                <?php if ( current_user_can( 'create_campaigns' ) ) : ?>
                    <a href="<?php echo esc_attr( $link . $email_settings_tab->key ) ?>"
                       class="nav-tab <?php echo esc_html( ( $tab == $email_settings_tab->key || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">
                        <?php echo esc_html( $email_settings_tab->title ) ?>
                    </a>
                <?php endif; ?>
                <?php if ( $this->has_selected_porch() ) : ?>
                    <a href="<?php echo esc_attr( $link . $prayer_content_tab->key . '&import=wordpress' ) ?>" class="nav-tab <?php echo esc_html( ( $tab == $prayer_content_tab->key ) ? 'nav-tab-active' : '' ); ?>">
                        <?php echo esc_html( $prayer_content_tab->title ) ?>
                    </a>
                <?php endif; ?>
                <?php if ( $this->has_selected_porch() ) : ?>
                    <a href="<?php echo esc_attr( $link . $prayer_fuel_tab->token ) ?>" class="nav-tab <?php echo esc_html( ( $tab == $prayer_fuel_tab->token ) ? 'nav-tab-active' : '' ); ?>">
                        <?php echo esc_html( $prayer_fuel_tab->title ) ?>
                    </a>
                <?php endif; ?>

                <?php do_action( 'dt_prayer_campaigns_tab_headers', $this->has_selected_porch(), $link, $tab ); ?>


                <?php $this->has_selected_porch() && $porch_admin->tab_headers( $link ); ?>

            </h2>
            <?php $this->campaign_selector(); ?>


            <?php
            switch ( $tab ) {
                case 'campaigns':
                    $this->campaigns->content();
                    break;
                case $email_settings_tab->key:
                    $email_settings_tab->content();
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
                    case $prayer_content_tab->key:
                        $prayer_content_tab->content();
                        break;
                    case $prayer_fuel_tab->token:
                        $prayer_fuel_tab->content();
                        break;
                    default:
                        break;
                }

                do_action( 'dt_prayer_campaigns_tab_content', $tab );

                $porch_admin->tab_content();
            }

            ?>

        </div>



        <?php
    }

    public function campaign_selector(){
        //todo set default campaign
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
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
        ?>

        <br>
        <form method="GET" >
            <input type="hidden" name="page" value="dt_prayer_campaigns"/>
            <input type="hidden" name="tab" value="<?php echo esc_html( $tab ); ?>"/>

            <table class="widefat striped metabox-table">
                <thead>
                <tr>
                    <th>Select Campaign to edit settings</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        <select name="campaign" id="campaign-selection" onchange="this.form.submit()">
                            <?php DT_Prayer_Campaigns_Campaigns::echo_my_campaigns_select_options( $campaign['ID'] ) ?>
                        </select>
                        <a href="<?php echo esc_html( $landing_page_url ); ?>">Show Landing Page</a> |
                        <a href="<?php echo esc_html( site_url() . '/campaigns/' . $campaign['ID'] ); ?>">Edit Campaign</a>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
        <?php
    }

    private function has_selected_porch() {
        return $this->porch_selector->has_selected_porch();
    }
}
