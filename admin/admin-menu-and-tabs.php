<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaigns_Menu
 */
class DT_Prayer_Campaigns_Menu {

    public $token = 'dt_prayer_campaigns';

    private static $_instance = null;

    private DT_Prayer_Campaigns_Campaigns $campaign;

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
        add_action( "admin_menu", array( $this, "register_menu" ) );

        $this->campaigns = new DT_Prayer_Campaigns_Campaigns();
    }

    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_submenu_page( 'dt_extensions', 'Prayer Campaigns', 'Prayer Campaigns', 'manage_dt', $this->token, [ $this, 'content' ] );
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
                break;
            default:
                break;
        }

        if ( $this->has_selected_porch() ) {
            $porch = $this->get_selected_porch_instance();
            $porch_admin = $porch->load_admin();
        }

        ?>

        <div class="wrap">
            <h2>Prayer Campaigns</h2>
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_attr( $link ) . 'campaigns' ?>"
                    class="nav-tab <?php echo esc_html( ( $tab == 'campaigns' || !isset( $tab ) ) ? 'nav-tab-active' : '' ); ?>">
                    Campaigns
                </a>

                <?php
                if ( $this->has_selected_porch() ) {
                    $porch_admin->tab_headers( $link );
                }
                ?>
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
                $porch_admin->tab_content();
            }
            ?>

        </div>

        <?php
    }

    /**
     * @return DT_Porch_Interface
     */
    private function get_selected_porch_instance() {
        $porches = $this->campaigns->get_porches();

        $selected_porch_id = $this->campaigns->get_selected_porch_id();

        return $porches[$selected_porch_id]["class"];
    }

    private function has_selected_porch() {
        $selected_porch_id = $this->campaigns->get_selected_porch_id();

        return $selected_porch_id && !empty( $selected_porch_id ) ? true : false;
    }
}

DT_Prayer_Campaigns_Menu::instance();

/**
 * Class DT_Prayer_Campaigns_Tab_General
 */
class DT_Prayer_Campaigns_Campaigns {

    private $settings_key = 'dt_prayer_campaign_settings';

    private $selected_porch_id;

    public function __construct() {
        $this->selected_porch_id = $this->get_setting( 'selected_porch' );
    }

    private function default_email_address(): string {
        $default_addr = apply_filters( 'wp_mail_from', '' );

        if ( empty( $default_addr ) ) {

            // Get the site domain and get rid of www.
            $sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
            if ( 'www.' === substr( $sitename, 0, 4 ) ) {
                $sitename = substr( $sitename, 4 );
            }

            $default_addr = 'wordpress@' . $sitename;
        }

        return $default_addr;
    }

    private function default_email_name(): string {
        $default_name = apply_filters( 'wp_mail_from_name', '' );

        if ( empty( $default_name ) ) {
            $default_name = 'WordPress';
        }

        return $default_name;
    }

    /**
     * Process changes to the email settings
     */
    public function process_email_settings() {
        if ( isset( $_POST['email_base_subject_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['email_base_subject_nonce'] ) ), 'email_subject' ) ) {

            if ( isset( $_POST['email_address'] ) ) {
                $email_address = sanitize_text_field( wp_unslash( $_POST['email_address'] ) );

                $this->update_setting( 'email_address', $email_address );
            }

            if ( isset( $_POST['email_name'] ) ) {
                $email_name = sanitize_text_field( wp_unslash( $_POST['email_name'] ) );

                $this->update_setting( 'email_name', $email_name );
            }
        }
    }

    /**
     * Process changes to the porch settings
     *
     * ? Do we delete the porch settings if they deselect a porch? or save them to be restored later?
     * ? or does it even matter?
     */
    public function process_porch_settings() {
        if ( isset( $_POST['campaign_settings_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['campaign_settings_nonce'] ) ), 'campaign_settings' ) ) {

            if ( isset( $_POST['select_porch'] ) ) {
                $selected_porch = sanitize_text_field( wp_unslash( $_POST['select_porch'] ) );

                $this->update_setting( 'selected_porch', $selected_porch );

                $this->selected_porch_id = $selected_porch;
            }
        }
    }

    private function get_all_settings() {
        return get_option( $this->settings_key, [] );
    }

    public function get_setting( string $name ) {
        $settings = $this->get_all_settings();

        if ( isset( $settings[$name] ) ) {
            return $settings[$name];
        }

        return null;
    }

    private function update_setting( string $name, mixed $value ) {
        $settings = $this->get_all_settings();

        if ( !$value || empty( $value ) ) {
            unset( $settings[$name] );
        } else {
            $new_setting = [];

            $new_setting[$name] = $value;

            $settings = array_merge( $settings, $new_setting );
        }

        update_option( $this->settings_key, $settings );
    }

    public function get_porches() {
        return apply_filters( 'dt_register_prayer_campaign_porch', [] );
    }

    public function get_selected_porch_id() {
        return $this->selected_porch_id;
    }

    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">

                        <?php $this->main_column() ?>

                    </div>
                    <div id="postbox-container-1" class="postbox-container">

                        <?php $this->right_column() ?>

                    </div>
                    <div id="postbox-container-2" class="postbox-container">
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function main_column() {
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Header</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="email_base_subject_nonce" id="email_base_subject_nonce"
                                    value="<?php echo esc_attr( wp_create_nonce( 'email_subject' ) ) ?>"/>

                            <table class="widefat">
                                <tbody>
                                <tr>
                                    <td>
                                        <label
                                            for="email_address"><?php echo esc_html( sprintf( "Specify Prayer Campaigns from email address. Leave blank to use default (%s)", self::default_email_address() ) ) ?></label>
                                    </td>
                                    <td>
                                        <input name="email_address" id="email_address"
                                                value="<?php echo esc_html( $this->get_setting( "email_address" ) ) ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label
                                            for="email_name"><?php echo esc_html( sprintf( "Specify Prayer Campaigns from name. Leave blank to use default (%s)", self::default_email_name() ) ) ?></label>
                                    </td>
                                    <td>
                                        <input name="email_name" id="email_name"
                                                value="<?php echo esc_html( $this->get_setting( "email_name" ) ) ?>"/>
                                    </td>
                                </tr>

                                </tbody>
                            </table>

                            <br>
                            <span style="float:right;">
                                <button type="submit" class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></button>
                            </span>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>

        <?php

        $porches = $this->get_porches();
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Campaign Settings</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="campaign_settings_nonce" id="campaign_settings_nonce"
                                    value="<?php echo esc_attr( wp_create_nonce( 'campaign_settings' ) ) ?>"/>

                            <table class="widefat">
                                <tbody>
                                    <tr>
                                        <td>
                                            <label
                                                for="select_porch"><?php echo esc_html( "Select Porch" ) ?></label>
                                        </td>
                                        <td>
                                            <select name="select_porch" id="select_porch"
                                                    selected="<?php echo esc_html( $this->get_selected_porch_id() ? $this->get_selected_porch_id() : '' ) ?>">

                                                    <option
                                                        <?php echo !isset( $settings["selected_porch"] ) ? "selected" : "" ?>
                                                        value=""
                                                    >
                                                        <?php esc_html_e( 'None', 'disciple-tools-prayer-campaign' ) ?>
                                                    </option>

                                                <?php foreach ( $porches as $id => $porch ): ?>

                                                    <option
                                                        value="<?php echo esc_html( $id ) ?>"
                                                        <?php echo $this->get_selected_porch_id() && $this->get_selected_porch_id() === $id ? "selected" : "" ?>
                                                    >
                                                        <?php echo esc_html( $porch["label"] ) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <span style="float:right;">
                                <button type="submit" class="button float-right"><?php esc_html_e( "Update", 'disciple_tools' ) ?></button>
                            </span>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>

        <?php
    }

    public function right_column() {
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Information</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Content
                </td>
            </tr>
            </tbody>
        </table>
        <br>

        <?php
    }
}
