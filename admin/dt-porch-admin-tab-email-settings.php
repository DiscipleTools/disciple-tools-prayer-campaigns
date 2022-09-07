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

    private function process_campaign_email_settings() {}

    private function campaign_selector() {
        ?>
        <form method="post" id="campaign-selection-form">
            <?php wp_nonce_field( 'campaign_selection', 'campaign_selection_nonce' ) ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Select Campaign to edit the email settings</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <label for="campaign-selection">Select Campaign</label>
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
        ?>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'email_settings', 'install_from_file_nonce' ) ?>
        <!-- Box -->
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Email Settings</th>
                    <th></th> <!-- extends the header bottom border across the right hand column -->
                </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>
                            <label for="campaign-description">Campaign Description</label>
                        </td>
                        <td>
                            <input type="text" name="campaign-description" id="campaign-description">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="sign-up-content">Extra content in sign up email</label>
                        </td>
                        <td>
                            <input type="text" name="sign-up-content" id="sign-up-content">
                        </input>
                    </tr>
                    <tr>
                        <td>
                            <label for="reminder-content">Extra content in prayer reminder email</label>
                        </td>
                        <td>
                            <input type="text" name="reminder-content" id="reminder-content">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <button class="button">Upload</button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </form>


        <?php
    }
}
