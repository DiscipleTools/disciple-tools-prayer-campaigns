<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Generic_Porch_Landing_Tab_Starter_Content
 */
class DT_Porch_Admin_Tab_Email_Settings extends DT_Porch_Admin_Tab_Base {
    public $key = 'campaign_email';
    public $title = 'Email Settings';
    private $selected_campaign;

    public function __construct( string $porch_dir ) {
        parent::__construct( $this->key, $porch_dir );
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $this->selected_campaign = $campaign['ID'];
    }

    public function body_content() {

//        $this->process_language_settings();
//        $this->process_new_language();

        $this->main_column();
//        $this->box_languages();

    }

    public function content() {

        ?>
        <style>
            .metabox-table input {
                /*width: 100%;*/
            }
            .metabox-table select {
                width: 100%;
            }
            .metabox-table textarea {
                width: 100%;
                height: 100px;
            }
        </style>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column(); ?>

                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function main_column2() {

        /* If no campaign is selected then show the message */
        /* If a campaign is selected */

        $this->process_email_settings();
        $this->process_campaign_email_content_settings();

        $this->box_email_settings();

        if ( !$this->selected_campaign || $this->selected_campaign === DT_Prayer_Campaigns_Campaigns::$no_campaign_key ) {
            DT_Porch_Admin_Tab_Base::message_box( 'Email Settings', 'You need to select a campaign above to start editing email settings' );
        } else {
            $this->email_content_settings_box();
        }
    }

    private function process_campaign_email_content_settings() {
        $make_campaign_strings_key = function( $key, $lang ) {
            return "hack-campaign_strings-$key--$lang";
        };

        if ( isset( $_POST['email_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['email_settings_nonce'] ) ), 'email_settings' ) ) {
            $updates = [];
            foreach ( $_POST as $name => $value ) {
                if ( strpos( $name, 'field_key_' ) === 0 ) {
                    $key_lang = explode( '-', $name );
                    $lang = $key_lang[1];
                    $key = str_replace( '_translation', '', str_replace( 'field_key_', '', $key_lang[0] ) );
                    $updates[$make_campaign_strings_key( $key, $lang )] = wp_kses_post( $value );
                }
            }

            if ( isset( $_POST['list'] ) ) {
                $list = dt_recursive_sanitize_array( $_POST['list'] );
                foreach ( $list as $key => $translation ) {
                    if ( isset( $_POST['list'][$key] ) ){
                        $updates[$make_campaign_strings_key( $key, 'default' )] = wp_kses_post( wp_unslash( $_POST['list'][$key] ) );
                    }
                }
            }

            DT_Posts::update_post( 'campaigns', $this->selected_campaign, $updates );

            $disable_fuel = isset( $_POST['reminder_content_disable_fuel'] );
            DT_Porch_Settings::update_values( [ 'reminder_content_disable_fuel' => $disable_fuel ] );

        }
    }

    /**
     * Process changes to the email settings
     */
    public function process_email_settings() {
        if ( isset( $_POST['email_base_subject_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['email_base_subject_nonce'] ) ), 'email_subject' ) ) {

            if ( isset( $_POST['submit'] ) ){
                if ( isset( $_POST['email_address'] ) ){
                    $email_address = sanitize_text_field( wp_unslash( $_POST['email_address'] ) );

                    $this->settings_manager->update( 'email_address', $email_address );
                }

                if ( isset( $_POST['email_name'] ) ){
                    $email_name = sanitize_text_field( wp_unslash( $_POST['email_name'] ) );

                    $this->settings_manager->update( 'email_name', $email_name );
                }
                if ( isset( $_POST['email_logo'] ) ){
                    $email_logo = sanitize_text_field( wp_unslash( $_POST['email_logo'] ) );

                    $this->settings_manager->update( 'email_logo', $email_logo );
                }
            }

            if ( isset( $_POST['tickler_email'] ) ){
                //subscriber_id @todo
                $subscriber_id = 16;
                DT_Prayer_Campaigns_Send_Email::send_resubscribe_tickler( $subscriber_id );
            }
        }
    }

    public function box_email_settings() {
        ?>

        <table class="widefat striped">
            <thead>
            <tr>
                <th>Sign Up and Notification Email Settings</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <form method="POST">
                        <input type="hidden" name="email_base_subject_nonce" id="email_base_subject_nonce"
                               value="<?php echo esc_attr( wp_create_nonce( 'email_subject' ) ) ?>"/>

                        <table class="widefat">
                            <thead>
                            <tr>
                                <th></th>
                                <th>Current</th>
                                <th>Custom Value</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    <label for="email_address">
                                        The email address campaign emails will be sent from</label>
                                </td>
                                <td>
                                    <?php echo esc_html( $this->settings_manager->get( 'email_address' ) ?: DT_Prayer_Campaigns_Send_Email::default_email_address() ) ?>
                                </td>
                                <td>
                                    <input name="email_address" id="email_address" type="email"
                                           placeholder=""
                                           value="<?php echo esc_html( $this->settings_manager->get( 'email_address' ) ) ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="email_name">
                                        The name campaign emails will be sent from</label>
                                </td>
                                <td>
                                    <?php echo esc_html( $this->settings_manager->get( 'email_name' ) ?: DT_Prayer_Campaigns_Send_Email::default_email_name() ) ?>
                                </td>
                                <td>
                                    <input name="email_name" id="email_name" type="text"
                                           placeholder=""
                                           value="<?php echo esc_html( $this->settings_manager->get( 'email_name' ) ) ?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <label for="email_logo">
                                        Email Header Logo</label>
                                </td>
                                <td>
                                    <img src="<?php echo esc_html( Campaigns_Email_Template::get_email_logo_url() ) ?>" style="max-width: 100px;"/>
                                </td>
                                <td>
                                    <input name="email_logo" id="email_logo" type="text"
                                           placeholder=""
                                           value="<?php echo esc_html( $this->settings_manager->get( 'email_logo' ) ) ?>"/>
                                </td>
                            </tr>

                            </tbody>
                        </table>

                        <br>
<!--                        <span style="float:left">-->
<!--                            <button type="submit" name="tickler_email" class="button">Test 2 week notice email</button>-->
<!--                        </span>-->
                        <span style="float:right;">
                            <button type="submit" name="submit" class="button"><?php esc_html_e( 'Update', 'disciple-tools-prayer-campaigns' ) ?></button>
                        </span>
                    </form>
                </td>
            </tr>
            </tbody>
        </table>
        <br>

        <?php
    }

    public function email_content_settings_box() {

        $languages_manager = new DT_Campaign_Languages();
        $langs = $languages_manager->get_enabled_languages();

        $campaign = DT_Campaign_Landing_Settings::get_campaign( $this->selected_campaign );
        $settings = DT_Porch_Settings::settings( 'settings' );
        $default_language = isset( $settings['default_language'] ) ? $settings['default_language']['value'] : PORCH_DEFAULT_LANGUAGE;

        $email_fields = [
            'signup_content' => [
                'label' => __( 'Content to be added to the signup email', 'disciple-tools-prayer-campaigns' ),
                'type' => 'textarea',
                'translations' => [],
            ],
            'reminder_content' => [
                'label' => __( 'Content to be added to the reminder email', 'disciple-tools-prayer-campaigns' ),
                'type' => 'textarea',
                'translations' => [],
            ],
        ];

        /* turn the translations array into the shape [ $key => [ $code => $translation ] ] */
        if ( isset( $campaign['campaign_strings'] ) && is_array( $campaign['campaign_strings'] ) ) {
            foreach ( $campaign['campaign_strings'] as $code => $strings ) {
                if ( is_array( $strings ) ) {
                    foreach ( $strings as $key => $translation ) {
                        if ( isset( $email_fields[$key] ) ) {
                            if ( $code === 'default' ) {
                                $email_fields[$key]['value'] = $translation;
                                continue;
                            }

                            $email_fields[$key]['translations'][$code] = $translation;
                        }
                    }
                }
            }
        }

        $form_name = 'email_settings';
        $porch_settings = DT_Porch_Settings::settings( 'email-settings' );
        $reminder_content_disable_fuel_checked = !empty( $porch_settings['reminder_content_disable_fuel']['value'] ); ?>

        <form method="post" enctype="multipart/form-data" name="<?php echo esc_attr( $form_name ) ?>">
            <?php wp_nonce_field( 'email_settings', 'email_settings_nonce' ) ?>
        <!-- Box -->
            <table class="widefat striped metabox-table">
                <thead>
                <tr>
                    <th>Email Content</th>
                    <th></th> <!-- extends the header bottom border across the right hand column -->
                    <th></th>
                </tr>
                </thead>
                <tbody>

                    <?php

                    foreach ( $email_fields as $key => $field ) {
                        if ( $field['type'] === 'textarea' ) {
                            DT_Porch_Admin_Tab_Base::textarea( $langs, $key, $field, $form_name, 'post' );
                        }
                    }

                    ?>
                    <tr>
                        <td>
                            <?php echo esc_html( $porch_settings['reminder_content_disable_fuel']['label'] ); ?>
                        </td>
                        <td>
                            <input type='checkbox' name="reminder_content_disable_fuel" <?php checked( $reminder_content_disable_fuel_checked ) ?>>
                        </td>
                        <td></td>
                    </tr>
                    <input type="hidden" name="default_language" value="<?php echo esc_attr( $default_language ) ?>">
                    <tr>
                        <td colspan="2">
                            <button class="button float-right" name="form-update-button" type="submit">Update</button>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </form>

        <?php dt_display_translation_dialog() ?>

        <?php
    }
}
