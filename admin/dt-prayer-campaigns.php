<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Class DT_Prayer_Campaigns_Tab_General
 */
class DT_Prayer_Campaigns_Campaigns {

    private $settings_manager;

    public static $no_campaign_key = 'none';

    public function __construct() {
        $this->settings_manager = new DT_Campaign_Settings();

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

    public static function setup_wizard_for_type( $wizard_type ){
        /**
         * Filter that contains the wizard types that can be used to create a campaign and choose an appropriate porch
         * automatically
         *
         * The array of wizards looks like
         *
         * [
         *     'wizard_type' => [
         *          'campaign_type' => 'campaign_type_key',
         *          'porch' => 'porch_key',
         *          'label' => 'Text to display in the wizard button',
         *     ]
         * ]
         */
        $wizard_types = apply_filters( 'dt_campaigns_wizard_types', [] );

        $wizard_details = isset( $wizard_types[$wizard_type] ) ? $wizard_types[$wizard_type] : [];
        $porch_type = isset( $wizard_details['porch'] ) ? $wizard_details['porch'] : 'generic-porch';
        $campaign_type = isset( $wizard_details['campaign_type'] ) ? $wizard_details['campaign_type'] : 'ongoing';

        if ( empty( $wizard_details ) ) {
            return;
        }

        $fields = [
            'name' => 'Campaign',
            'type' => $campaign_type,
            'start_date' => time(),
            'status' => 'active',
        ];

        if ( $porch_type === 'ramadan-porch' ) {
            $next_ramadan_start_date = strtotime( dt_get_next_ramadan_start_date() );
            $fields['start_date'] = $next_ramadan_start_date;
            $fields['end_date'] = $next_ramadan_start_date + 30 * DAY_IN_SECONDS;
            $fields['name'] = 'Ramadan Campaign';
        } else if ( $campaign_type === '24hour' ) {
            $fields['end_date'] = time() + 30 * DAY_IN_SECONDS;
            $fields['name'] = 'Fixed Dates Campaign';
        } elseif ( $campaign_type === 'ongoing' ){
            $fields['name'] = 'Ongoing Campaign';
        }

        $new_campaign = DT_Posts::create_post( 'campaigns', $fields, true, false );
        if ( is_wp_error( $new_campaign ) ){
            return;
        }
        update_option( 'dt_campaign_selected_campaign', $new_campaign['ID'] );
        $settings_manager = new DT_Campaign_Settings();
        $settings_manager->update( 'selected_porch', $porch_type );
        DT_Porch_Selector::instance()->set_selected_porch_id( $porch_type );

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

            if ( isset( $_POST['select_porch'], $_POST['porch_type_submit'] ) ) {
                $selected_porch = sanitize_text_field( wp_unslash( $_POST['select_porch'] ) );

                $this->settings_manager->update( 'selected_porch', $selected_porch );

                DT_Porch_Selector::instance()->set_selected_porch_id( $selected_porch );

                /* Make sure that the prayer fuel custom post type is flushed or set up straight after the porch has been changed */
                header( 'Refresh:0.01' );
            }
            if ( isset( $_POST['setup_wizard_submit'], $_POST['setup_wizard_type'] ) ){
                $wizard_type = sanitize_text_field( wp_unslash( $_POST['setup_wizard_type'] ) );
                self::setup_wizard_for_type( $wizard_type );
                return wp_redirect( home_url() );

            }
        }
    }
    public static function sanitized_post_field( $post, $key ) {
        if ( !isset( $post[$key] ) ) {
            return '';
        }
        return sanitize_text_field( wp_unslash( $post[$key] ) );
    }

    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <?php $is_wizard_open = !DT_Porch_Selector::instance()->has_selected_porch() || $this->no_campaigns(); ?>

                <button
                    class="button"
                    id="campaign-wizard-toggle"
                    style="display: <?php echo esc_attr( $is_wizard_open ? 'none' : 'inline-block' ) ?>; margin-bottom: 1rem;"
                >
                    Show Campaign Wizard
                </button>

                <a class="button" href="<?php echo esc_html( home_url() ); ?>" target="_blank">Go to Landing Page</a>
                <a class="button" href="https://pray4movement.org/docs/overview/" target="_blank">See Help Documentation</a>
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">


                        <div id="campaign-wizard" style="display: <?php echo esc_attr( $is_wizard_open ? 'block' : 'none' ) ?>;">
                            <?php $this->setup_wizard(); ?>
                        </div>


                        <?php

                        $this->box_select_porch();

                        if ( DT_Porch_Selector::instance()->has_selected_porch() ) {
                            $this->box_campaign();
                        }

                        $this->box_email_settings();

                        ?>
                     </div>
                    <div id="postbox-container-1" class="postbox-container">
                        <!-- Right Column -->

                        <?php $this->right_column() ?>

                        <!-- End Right Column -->
                    </div><!-- postbox-container 1 -->
                </div>
            </div>
        </div>
        <?php
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
                                <tbody>
                                <tr>
                                    <td>
                                        <label for="email_address">
                                            The email address campaign emails will be sent from</label>
                                    </td>
                                    <td>
                                        <input name="email_address" id="email_address" type="email"
                                               placeholder="<?php echo esc_html( self::default_email_address() ); ?>"
                                               value="<?php echo esc_html( $this->settings_manager->get( 'email_address' ) ) ?>"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="email_name">
                                            The name campaign emails will be sent from</label>
                                    </td>
                                    <td>
                                        <input name="email_name" id="email_name" type="text"
                                               placeholder="<?php echo esc_html( self::default_email_name() ); ?>"
                                               value="<?php echo esc_html( $this->settings_manager->get( 'email_name' ) ) ?>"/>
                                    </td>
                                </tr>

                                </tbody>
                            </table>

                            <br>
                            <span style="float:right;">
                                <button type="submit" class="button float-right"><?php esc_html_e( 'Update', 'disciple-tools-prayer-campaigns' ) ?></button>
                            </span>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>

        <?php
    }

    private function setup_wizard(){
        ?>

        <table class="widefat striped">
            <thead>
            <tr>
                <th>Setup Wizard</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <form method="POST">
                        <input type="hidden" name="campaign_settings_nonce" id="campaign_settings_nonce"
                               value="<?php echo esc_attr( wp_create_nonce( 'campaign_settings' ) ) ?>"/>

                        <h2>Setup up the landing page for this campaign type:</h2>
                        <p>
                            <?php $this->display_wizard_buttons() ?>
                        </p>

                        <p>
                            <button type="submit" class="button" name="setup_wizard_submit">Continue</button>
                        </p>
                        <p>This selects landing page and creates a corresponding campaign</p>
                    </form>
                </td>
            </tr>
            </tbody>
        </table>

        <br>
        <?php
    }

    private function box_select_porch() {
        $porches = DT_Porch_Selector::instance()->get_porch_loaders();
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>
                        Landing page Selection
                        <?php if ( DT_Porch_Selector::instance()->get_selected_porch_id() ) : ?>
                            <img style="width: 20px; vertical-align: sub" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/verified.svg' ) ?>"/>
                        <?php endif;?>
                    </th>
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
                                            <label for="select_porch"><?php echo esc_html( 'Select Landing Page Type' ) ?></label>
                                        </td>
                                        <td>
                                            <select name="select_porch" id="select_porch" <?php selected( DT_Porch_Selector::instance()->get_selected_porch_id() ) ?>>
                                                    <option
                                                        <?php echo !isset( $settings['selected_porch'] ) ? 'selected' : '' ?>
                                                        value=""
                                                    >
                                                        <?php esc_html_e( 'None', 'disciple-tools-prayer-campaign' ) ?>
                                                    </option>

                                                <?php foreach ( $porches as $id => $porch ): ?>

                                                    <option
                                                        value="<?php echo esc_html( $id ) ?>"
                                                        <?php echo DT_Porch_Selector::instance()->get_selected_porch_id() && DT_Porch_Selector::instance()->get_selected_porch_id() === $id ? 'selected' : '' ?>
                                                    >
                                                        <?php echo esc_html( $porch['label'] ) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>
                                        </td>
                                    </tr>
                                    <tr>

                                        <td>Landing Page Title</td>
                                        <td>
                                            <strong><?php echo esc_html( isset( DT_Porch_Settings::settings()['title']['value'] ) ? DT_Porch_Settings::settings()['title']['value'] : '' ); ?></strong>
                                            <a style="margin-inline-start: 10px" href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=translations' ) ); ?>">change</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <br>
                            <span style="float:right;">
                                <button type="submit" name="porch_type_submit" class="button float-right"><?php esc_html_e( 'Update', 'disciple-tools-prayer-campaigns' ) ?></button>
                            </span>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>

        <?php
    }

    private function display_wizard_buttons() {
        $wizard_types = apply_filters( 'dt_campaigns_wizard_types', [] )
        ?>

        <?php foreach ( $wizard_types as $wizard_type => $wizard_details ): ?>

            <label style="display: block; padding: 8px">
                <input type="radio" name="setup_wizard_type" value="<?php echo esc_html( $wizard_type ); ?>" required>
                <?php echo isset( $wizard_details['label'] ) ? esc_html( $wizard_details['label'] ) : esc_html( "$wizard_type" ) ?>
            </label>

        <?php endforeach; ?>

        <?php
    }

    public function box_campaign() {

        if ( isset( $_POST['install_campaign_nonce'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['install_campaign_nonce'] ) ), 'install_campaign_nonce' )
            && isset( $_POST['selected_campaign'] )
        ) {
            $campaign_id = sanitize_text_field( wp_unslash( $_POST['selected_campaign'] ) );
            update_option( 'dt_campaign_selected_campaign', $campaign_id === self::$no_campaign_key ? null : $campaign_id );
        }
        $fields = DT_Campaign_Settings::get_campaign();
        if ( empty( $fields ) ) {
            $fields = [ 'ID' => 0 ];
        }


        ?>
        <style>
            .metabox-table select {
                width: 100%;
            }
        </style>
       <table class="widefat striped">
            <thead>
                <tr>
                    <th>
                        Campaign Selection
                        <?php if ( $fields['ID'] ) : ?>
                            <img style="width: 20px; vertical-align: sub" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/verified.svg' ) ?>"/>
                        <?php endif;?>
                    </th>
                </tr>
            </thead>
           <tbody>
               <tr>
                   <td>
                        <form method="post" class="metabox-table">
                            <?php wp_nonce_field( 'install_campaign_nonce', 'install_campaign_nonce' ) ?>
                            <!-- Box -->
                            <table class="widefat striped">
                                <tbody>
                                    <tr>
                                        <td>
                                            Select Campaign
                                        </td>
                                        <td>
                                            <select name="selected_campaign">
                                                <?php $this->campaign_options( $fields['ID'] ) ?>
                                            </select>
                                            <button class="button float-right" type="submit">Update</button>
                                            <br>
                                            <br>
                                            <a href="<?php echo esc_html( site_url( '/campaigns' ) ); ?>" target="_blank" style="margin: 10px">See campaigns list</a>
                                            <a href="<?php echo esc_html( site_url( '/campaigns/new' ) ); ?>"  target="_blank" >Create a campaign</a>
                                            <br>
                                        </td>
                                    </tr>
                                    <?php if ( ! empty( $fields['ID'] ) ) : ?>
                                        <?php foreach ( $fields as $key => $value ) :
                                            if ( in_array( $key, [ 'start_date', 'end_date', 'status' ] ) ) :
                                                ?>
                                                <tr>
                                                    <td><?php echo esc_attr( ucwords( str_replace( '_', ' ', $key ) ) ) ?></td>
                                                    <td><?php echo esc_html( ( is_array( $value ) ) ? $value['formatted'] ?? $value['label'] : $value ); ?></td>
                                                </tr>
                                            <?php endif;
                                        endforeach; ?>
                                        <tr>
                                            <td>Campaign Type</td>
                                            <td><?php echo esc_html( $fields['type']['label'] ) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Edit Campaign Details</td>
                                            <td>
                                                <a href="<?php echo esc_html( site_url() . '/campaigns/' . $fields['ID'] ); ?>" target="_blank">Edit Campaign</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>See subscribers</td>
                                            <td>
                                                <a href="<?php echo esc_html( site_url() . '/subscriptions/' ); ?>" target="_blank">See Subscribers</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Export Campaign Subscribers</td>
                                            <td><button type="submit" name="download_csv">Download CSV</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <br>
                            <!-- End Box -->
                        </form>
                   </td>
               </tr>
           </tbody>
       </table>
       <br/>
        <?php
    }

    public static function campaign_options( $selected_id ) {
        $campaigns = DT_Posts::list_posts( 'campaigns', [ 'status' => [ 'active', 'pre_signup', 'inactive' ] ] );
        if ( is_wp_error( $campaigns ) ){
            $campaigns = [ 'posts' => [] ];
        }
        ?>

        <option value=<?php echo esc_html( self::$no_campaign_key ) ?>></option>
        <?php foreach ( $campaigns['posts'] as $campaign ) :?>
            <option value="<?php echo esc_html( $campaign['ID'] ) ?>"
                <?php selected( (int) $campaign['ID'] === (int) $selected_id ) ?>
            >
                <?php echo esc_html( $campaign['name'] ) ?>

            </option>
        <?php endforeach; ?>

        <?php
    }

    private function no_campaigns() {
        return empty( DT_Posts::list_posts( 'campaigns', [], false )['posts'] );
    }

    public function right_column() {
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Links</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <ul>
                        <li><a href="<?php echo esc_html( home_url() ); ?>" target="_blank">Landing Page</a></li>
                        <li><a href="<?php echo esc_html( home_url( '/campaigns' ) ); ?>" target="_blank">Campaigns</a></li>
                        <li><a href="<?php echo esc_html( home_url( '/subscriptions' ) ); ?>" target="_blank">Prayer Warriors (Subscribers)</a></li>
                        <li><a href="https://pray4movement.org/docs/overview/" target="_blank">Documentation</a></li>
                        <li><a href="https://poeditor.com/join/project/yik32Z3OEf" target="_blank">Translate Pages and Emails</a></li>
                    </ul>
                </td>
            </tr>
            </tbody>
        </table>
        <br>

        <?php
    }
}
