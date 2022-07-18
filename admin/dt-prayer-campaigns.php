<?php
/**
 * Class DT_Prayer_Campaigns_Tab_General
 */
class DT_Prayer_Campaigns_Campaigns {

    private $settings_manager;
    private $languages_manager;

    public function __construct() {
        $this->settings_manager = new DT_Campaign_Settings();
        $this->languages_manager = new DT_Campaign_Languages();
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

                $this->settings_manager->update( 'email_address', $email_address );
            }

            if ( isset( $_POST['email_name'] ) ) {
                $email_name = sanitize_text_field( wp_unslash( $_POST['email_name'] ) );

                $this->settings_manager->update( 'email_name', $email_name );
            }
        }
    }

    /**
     * Process changes to the porch settings
     */
    public function process_porch_settings() {
        if ( isset( $_POST['campaign_settings_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['campaign_settings_nonce'] ) ), 'campaign_settings' ) ) {

            if ( isset( $_POST['select_porch'] ) ) {
                $selected_porch = sanitize_text_field( wp_unslash( $_POST['select_porch'] ) );

                $this->settings_manager->update( 'selected_porch', $selected_porch );

                DT_Prayer_Campaigns::instance()->set_selected_porch_id( $selected_porch );

                /* Make sure that the prayer fuel custom post type is flushed or set up straight after the porch has been changed */
                header( "Refresh:0.01" );
            }
        }
    }

    /**
     * Process changes to the language settings
     */
    public function process_language_settings() {
        if ( isset( $_POST['language_settings_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['language_settings_nonce'] ) ), 'language_settings' ) ) {

            if ( isset( $_POST['language_settings_disable'] ) ) {
                $this->languages_manager->disable( $this->sanitized_post_field( $_POST, 'language_settings_disable' ) );
            }

            if ( isset( $_POST['language_settings_enable'] ) ) {
                $this->languages_manager->enable( $this->sanitized_post_field( $_POST, 'language_settings_enable' ) );
            }

            if ( isset( $_POST['language_settings_remove'] ) ) {
                $this->languages_manager->remove( $this->sanitized_post_field( $_POST, 'language_settings_remove' ) );
            }
        }
    }

    /**
     * Process changes to the language settings
     */
    public function process_new_language() {
        if ( isset( $_POST['add_language_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['add_language_nonce'] ) ), 'add_language' ) ) {
            if ( isset( $_POST['new_language'] ) ) {
                $this->languages_manager->add_from_code( $this->sanitized_post_field( $_POST, 'new_language' ) );
            }
        }
    }

    private function sanitized_post_field( $post, $key ) {
        if ( !isset( $post[$key] ) ) {
            return '';
        }
        return sanitize_text_field( wp_unslash( $post[$key] ) );
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
                                                value="<?php echo esc_html( $this->settings_manager->get( "email_address" ) ) ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label
                                            for="email_name"><?php echo esc_html( sprintf( "Specify Prayer Campaigns from name. Leave blank to use default (%s)", self::default_email_name() ) ) ?></label>
                                    </td>
                                    <td>
                                        <input name="email_name" id="email_name"
                                                value="<?php echo esc_html( $this->settings_manager->get( "email_name" ) ) ?>"/>
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
        $porches = DT_Prayer_Campaigns::instance()->get_porch_loaders();
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
                                                    selected="<?php echo esc_html( DT_Prayer_Campaigns::instance()->get_selected_porch_id() ? DT_Prayer_Campaigns::instance()->get_selected_porch_id() : '' ) ?>">

                                                    <option
                                                        <?php echo !isset( $settings["selected_porch"] ) ? "selected" : "" ?>
                                                        value=""
                                                    >
                                                        <?php esc_html_e( 'None', 'disciple-tools-prayer-campaign' ) ?>
                                                    </option>

                                                <?php foreach ( $porches as $id => $porch ): ?>

                                                    <option
                                                        value="<?php echo esc_html( $id ) ?>"
                                                        <?php echo DT_Prayer_Campaigns::instance()->get_selected_porch_id() && DT_Prayer_Campaigns::instance()->get_selected_porch_id() === $id ? "selected" : "" ?>
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
            $languages = $this->languages_manager->get();
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Language Settings</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2">
                        <form method="POST">
                            <input type="hidden" name="language_settings_nonce" id="language_settings_nonce"
                                    value="<?php echo esc_attr( wp_create_nonce( 'language_settings' ) ) ?>"/>

                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Flag</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $enabled_column = array_column( $languages, 'enabled' );
                                        array_multisort( $enabled_column, SORT_DESC, $languages );
                                    ?>

                                    <style>
                                        .disabled-language {
                                            background-color: darkgrey;
                                        }
                                        .widefat .disabled-language td {
                                            color: white;
                                        }
                                    </style>

                                    <?php foreach ( $languages as $code => $language ): ?>

                                        <tr class="<?php echo $language["enabled"] === false ? 'disabled-language' : '' ?>">
                                            <td><?php echo esc_html( $language["english_name"] ) ?></td>
                                            <td><?php echo esc_html( $code ) ?></td>
                                            <td><?php echo esc_html( $language["flag"] ) ?></td>
                                            <td>

                                                <?php if ( isset( $language["enabled"] ) && $language["enabled"] === true ): ?>

                                                <button class="button" name="language_settings_disable" value="<?php echo esc_html( $code ) ?>">
                                                    Disable
                                                </button>

                                                <?php else : ?>

                                                <button class="button button-primary" name="language_settings_enable" value="<?php echo esc_html( $code ) ?>">
                                                    Enable
                                                </button>

                                                <?php endif; ?>

                                                <?php if ( !isset( $language["default"] ) || $language["default"] !== true ): ?>

                                                <button class="button" name="language_settings_remove" value="<?php echo esc_html( $code ) ?>">
                                                    Remove
                                                </button>

                                                <?php endif; ?>

                                            </td>
                                        </tr>

                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        Add existing language
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="add_language_nonce" id="add_language_nonce"
                                    value="<?php echo esc_attr( wp_create_nonce( 'add_language' ) ) ?>"/>

                            <?php $language_list = $this->languages_manager->language_list() ?>

                            <select name="new_language" id="language_list">

                            <?php foreach ( $language_list as $code => $language ): ?>

                                <option value="<?php echo esc_html( $code ) ?>"><?php echo esc_html( $language["flag"] . " " . $language["label"] ) ?></option>

                            <?php endforeach; ?>

                            </select>
                            <button class="button">
                                Add
                            </button>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>

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
