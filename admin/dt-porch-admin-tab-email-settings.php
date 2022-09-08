<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Generic_Porch_Landing_Tab_Starter_Content
 */
class DT_Porch_Admin_Tab_Email_Settings {
    public $key = 'email-settings';
    public $title = 'Email Settings';
    private $selected_campaign;

    public function __construct() {
        $campaign = DT_Campaign_Settings::get_campaign();

        if ( !empty( $campaign ) ) {
            $this->selected_campaign = $campaign['ID'];
        }
    }

    public function content() {

        ?>
        <style>
            .metabox-table input {
                width: 100%;
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

    public function main_column() {

        /* If no campaign is selected then show the message */
        /* If a campaign is selected */

        $this->process_select_campaign();
        $this->process_campaign_email_settings();

        $this->campaign_selector();

        if ( !$this->selected_campaign || $this->selected_campaign === DT_Prayer_Campaigns_Campaigns::$no_campaign_key ) {
            DT_Porch_Admin_Tab_Base::message_box( 'Email Settings', 'You need to select a campaign above to start editing email settings' );
        } else {
            $this->email_settings_box();
        }
    }

    private function process_select_campaign() {
        if ( isset( $_POST['campaign_selection_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['campaign_selection_nonce'] ) ), 'campaign_selection' ) ) {
            if ( isset( $_POST['campaign-selection'] ) ) {
                $this->selected_campaign = sanitize_text_field( wp_unslash( $_POST['campaign-selection'] ) );
            }
        }
    }

    private function process_campaign_email_settings() {
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
                    $updates[$make_campaign_strings_key( $key, $lang )] = sanitize_text_field( wp_unslash( $value ) );
                }
            }
            $default_language = PORCH_DEFAULT_LANGUAGE;
            if ( isset( $_POST['default_language'] ) ) {
                $default_language = sanitize_text_field( wp_unslash( $_POST['default_language'] ) );
            }

            if ( isset( $_POST['form-update-button'] ) ) {
                if ( isset( $_POST['list'] ) ) {
                    $list = dt_recursive_sanitize_array( $_POST['list'] );
                    foreach ( $list as $key => $translation ) {
                        $updates[$make_campaign_strings_key( $key, $default_language )] = sanitize_text_field( wp_unslash( $translation ) );
                    }
                }
            }

            DT_Posts::update_post( 'campaigns', $this->selected_campaign, $updates );
        }
    }

    private function campaign_selector() {
        ?>
        <form method="post" id="campaign-selection-form">
            <?php wp_nonce_field( 'campaign_selection', 'campaign_selection_nonce' ) ?>
            <table class="widefat striped metabox-table">
                <thead>
                    <tr>
                        <th>Select Campaign to edit the email settings</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="campaign-selection" id="campaign-selection" onchange="this.form.submit()">
                                <?php DT_Prayer_Campaigns_Campaigns::campaign_options( $this->selected_campaign ) ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <br>
        <?php
    }

    public function email_settings_box() {

        $langs = dt_campaign_list_languages();
        $campaign = DT_Campaign_Settings::get_campaign( $this->selected_campaign );
        $settings = DT_Porch_Settings::settings( 'settings' );
        $default_language = isset( $settings['default_language'] ) ? $settings['default_language']['value'] : PORCH_DEFAULT_LANGUAGE;

        $email_fields = [
            'signup_content' => [
                'label' => __( 'Content to be added to the signup email', 'disciple-tools-prayer-campaigns' ),
                'translations' => [],
            ],
            'reminder_content' => [
                'label' => __( 'Content to be added to the reminder email', 'disciple-tools-prayer-campaigns' ),
                'translations' => [],
            ],
        ];

        /* turn the translations array into the shape [ $key => [ $code => $translation ] ] */
        if ( isset( $campaign['campaign_strings'] ) && is_array( $campaign['campaign_strings'] ) ) {
            foreach ( $campaign['campaign_strings'] as $code => $strings ) {
                if ( is_array( $strings ) ) {
                    foreach ( $strings as $key => $translation ) {
                        if ( isset( $email_fields[$key] ) ) {
                            $email_fields[$key]['translations'][$code] = $translation;

                            if ( $code === $default_language ) {
                                $email_fields[$key]['value'] = $translation;
                            }
                        }
                    }
                }
            }
        }

        $form_name = 'email_settings';
        ?>
        <form method="post" enctype="multipart/form-data" name="<?php echo esc_attr( $form_name ) ?>">
            <?php wp_nonce_field( 'email_settings', 'email_settings_nonce' ) ?>
        <!-- Box -->
            <table class="widefat striped metabox-table">
                <thead>
                <tr>
                    <th>Email Settings</th>
                    <th></th> <!-- extends the header bottom border across the right hand column -->
                    <th></th>
                </tr>
                </thead>
                <tbody>

                    <?php

                    foreach ( $email_fields as $key => $field ) {
                        DT_Porch_Admin_Tab_Base::textarea( $langs, $key, $field, $form_name );
                    }

                    ?>
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
