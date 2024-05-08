<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Class DT_Prayer_Campaigns_Tab_General
 */
class DT_Prayer_Campaigns_Campaigns {

    private $settings_manager;

    public static $no_campaign_key = 'none';

    public function __construct() {
        $this->settings_manager = new DT_Campaign_Global_Settings();

    }



    public static function setup_wizard_for_type( $wizard_type, $new_campaign_name = null ){
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
        $campaign_type = isset( $wizard_details['campaign_type'] ) ? $wizard_details['campaign_type'] : 'generic';

        if ( empty( $wizard_details ) ) {
            return;
        }

        $fields = [
            'name' => 'Prayer Campaign',
            'start_date' => dt_format_date( time(), 'Y-m-d' ),
            'status' => 'active',
            'porch_type' => $porch_type,
        ];

        if ( $porch_type === 'ramadan-porch' ) {
            $next_ramadan_start_date = strtotime( dt_get_next_ramadan_start_date() );
            $fields['start_date'] = $next_ramadan_start_date;
            $fields['end_date'] = $next_ramadan_start_date + 30 * DAY_IN_SECONDS;
            $fields['name'] = 'Ramadan Campaign';
        } else if ( $wizard_type === 'generic' ) {
            $fields['end_date'] = dt_format_date( time() + 30 * DAY_IN_SECONDS, 'Y-m-d' );
        }

        if ( $new_campaign_name ) {
            $fields['name'] = $new_campaign_name;
        }

        $default_campaign = get_option( 'dt_campaign_selected_campaign', false );

        $new_campaign = DT_Posts::create_post( 'campaigns', $fields, true, false );
        if ( is_wp_error( $new_campaign ) ){
            return;
        }

        if ( empty( $default_campaign ) ){
            update_option( 'dt_campaign_selected_campaign', $new_campaign['ID'] );
        }
        return $new_campaign['ID'];
    }


    /**
     * Process changes to the porch settings
     */
    public function process_porch_settings() {
        if ( isset( $_POST['campaign_settings_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['campaign_settings_nonce'] ) ), 'campaign_settings' ) ) {

            if ( isset( $_POST['select_porch'], $_POST['porch_type_submit'] ) ) {
                $selected_porch = sanitize_text_field( wp_unslash( $_POST['select_porch'] ) );

                $this->settings_manager->update( 'selected_porch', $selected_porch );

                DT_Posts::update_post( 'campaigns', DT_Campaign_Landing_Settings::get_campaign_id(), [ 'porch_type' => $selected_porch ] );

                /* Make sure that the prayer fuel custom post type is flushed or set up straight after the porch has been changed */
                header( 'Refresh:0.01' );
            }
            if ( isset( $_POST['setup_wizard_submit'], $_POST['setup_wizard_type'], $_POST['new-campaign-name'] ) ){
                $wizard_type = sanitize_text_field( wp_unslash( $_POST['setup_wizard_type'] ) );
                $new_campaign_name = sanitize_text_field( wp_unslash( $_POST['new-campaign-name'] ) );
                $new_campaign_id = self::setup_wizard_for_type( $wizard_type, $new_campaign_name );


                if ( !empty( $new_campaign_id ) && !is_wp_error( $new_campaign_id ) ){
                    $default_campaign = get_option( 'dt_campaign_selected_campaign', false );
                    if ( (int) $default_campaign === (int) $new_campaign_id ){
                        return wp_redirect( home_url() );
                    } else {
                        return wp_redirect( DT_Campaign_Landing_Settings::get_landing_page_url( $new_campaign_id ) );
                    }
                }
            }
        }
    }

    public function process_default_campaign_setting(){
        if ( isset( $_POST['default_campaign_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['default_campaign_nonce'] ) ), 'default_campaign_nonce' ) ) {

            if ( isset( $_POST['default_campaign_submit'], $_POST['default_campaign'] ) ) {
                $selected_campaign = sanitize_text_field( wp_unslash( $_POST['default_campaign'] ) );
                update_option( 'dt_campaign_selected_campaign', $selected_campaign === self::$no_campaign_key ? null : $selected_campaign );
            }
        }
    }

    public function process_p4m_participation_settings(){
        if ( isset( $_POST['p4m_participation_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['p4m_participation_nonce'] ) ), 'p4m_participation' ) ){

            if ( isset( $_POST['p4m_participation_submit'] ) ){
                $participation = isset( $_POST['p4m_participation'] );
                $this->settings_manager->update( 'p4m_participation', $participation );
            }
            if ( isset( $_POST['p4m_participation_send_now'] ) ){
                DT_Campaigns_Base::send_campaign_info();
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
                <a
                    class="button"
                    href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=new_campaign' ) ); ?>"
                >
                    Create a new Campaign
                </a>

                <a class="button" href="https://pray4movement.org/docs/overview/" target="_blank">See Help Documentation</a>

                <br>
                <br>
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">

                        <?php $this->campaign_selector(); ?>

                        <?php

//                        $this->box_select_porch();

//                        $this->box_campaign();

                        $this->box_default_campaign();

                        $this->box_p4m_participation();

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

    public function campaign_selector(){
        $campaigns = DT_Posts::list_posts( 'campaigns', [] );
        if ( is_wp_error( $campaigns ) ){
            $campaigns = [ 'posts' => [] ];
        }
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Select Campaign to edit settings</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php if ( empty( $campaigns['posts'] ) ) : ?>
                            <p>Let's Get Started!</p>
                            <p>
                                <a class="button" href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=new_campaign' ) ); ?>">
                                    Create a new Campaign
                                </a>
                            </p>
                        <?php else :

                            foreach ( $campaigns['posts'] as $campaign ) : ?>
                                <a class="button" href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=campaign_landing&campaign=' . $campaign['ID'] ) ) ?>">
                                    <?php echo esc_html( $campaign['name'] ) ?>
                                </a>
                            <?php endforeach;
                        endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>

        <?php

    }


    public function box_default_campaign(){
        if ( !current_user_can( 'update_any_campaigns' ) ){
            return;
        }

        $home_url = home_url();
        $default_campaign = get_option( 'dt_campaign_selected_campaign', false );
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        if ( empty( $campaign ) ){
            return;
        }

        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Default Campaign</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <form method="POST">
                            <?php wp_nonce_field( 'default_campaign_nonce', 'default_campaign_nonce' ) ?>

                            <p>Which campaign should be shown on this page: <a href="<?php echo esc_html( $home_url ); ?>"><?php echo esc_html( $home_url ); ?></a></p>

                            <table class="widefat">
                                <tbody>
                                    <tr>
                                        <td>
                                            <label for="default_campaign">Select Campaign</label>
                                        </td>
                                        <td>
                                            <select name="default_campaign" id="default_campaign">
                                                <option value="none">None</option>
                                                <?php $this->echo_my_campaigns_select_options( $default_campaign ) ?>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="submit" class="button float-right" name="default_campaign_submit">Update</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <?php

    }

    public function box_p4m_participation() {
        if ( !current_user_can( 'update_any_campaigns' ) ){
            return;
        }

        $participation = $this->settings_manager->get( 'p4m_participation', true );
        $participation_force = apply_filters( 'p4m_participation', false ) && !is_super_admin();

        $campaign_id = DT_Campaign_Landing_Settings::get_campaign_id();
        if ( empty( $campaign_id ) ){
            return;
        }

        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>P4M Participation</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <form method="POST" style='max-width: 1000px'>
                            <input type="hidden" name="p4m_participation_nonce" id="p4m_participation_nonce"
                                    value="<?php echo esc_attr( wp_create_nonce( 'p4m_participation' ) ) ?>"/>

                            <table class="widefat">
                                <tbody>
                                    <tr>
                                        <td style="width: min-content">
                                            <input name="p4m_participation" id="p4m_participation" type="checkbox"
                                                   <?php checked( $participation || $participation_force ); disabled( $participation_force ) ?> />
                                            <label for="p4m_participation">
                                                List my campaigns on <a href="https://pray4movement.org/" target="_blank">https://pray4movement.org</a>.
                                                <br>
                                                This allows other users to see your campaign and join in prayer. And shows the progress towards global prayer coverage.
                                            </label>
                                        </td>
                                        <td style="min-width: 100px; vertical-align: middle">
                                            <button type='submit' name='p4m_participation_submit' class='button' <?php disabled( $participation_force ) ?>>
                                                <?php esc_html_e( 'Update', 'disciple-tools-prayer-campaigns' ) ?>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <button type="submit" name="p4m_participation_send_now">Send</button> Stats Now
                                        </td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>

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
        $selected_porch = DT_Porch_Selector::instance()->get_selected_porch_id();
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        if ( empty( $campaign ) ) {
            return;
        }
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>
                        Landing page Selection
                        <?php if ( DT_Porch_Selector::instance()->get_selected_porch_id() ) : ?>
                            <img style="width: 20px; vertical-align: sub" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/verified.svg' ) ?>"/>
                        <?php else : ?>
                            <img style="width: 20px; vertical-align: sub" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/broken.svg' ) ?>"/>
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

                                        <td>Campaign Title</td>
                                        <td>
                                            <strong><?php echo esc_html( $campaign['name'] ); ?></strong>
                                            <a style="margin-inline-start: 10px" href="<?php echo esc_html( site_url( 'campaigns/' . $campaign['ID'] ) ); ?>">change</a>
                                        </td>
                                    </tr>
                                    <?php do_action( 'dt_campaigns_landing_page_selection' ) ?>
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

    public function box_campaign() {
        $fields = DT_Campaign_Landing_Settings::get_campaign( null, true );
        if ( empty( $fields ) ) {
            return;
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
                        <?php else : ?>
                            <img style="width: 20px; vertical-align: sub" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/broken.svg' ) ?>"/>
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
                                    <?php if ( ! empty( $fields['ID'] ) ) : ?>
                                        <tr>
                                            <td>Status</td>
                                            <td><?php echo esc_html( isset( $fields['status']['label'] ) ? $fields['status']['label'] : '' ); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Porch Type</td>
                                            <td><?php echo esc_html( isset( $fields['porch_type']['label'] ) ? $fields['porch_type']['label'] : '' ); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Start Date</td>
                                            <td>
                                                <?php echo esc_html( isset( $fields['start_date']['formatted'] ) ? $fields['start_date']['formatted'] : '' ); ?>
                                                <a style="margin: 0 10px" href="<?php echo esc_html( site_url() . '/campaigns/' . $fields['ID'] ); ?>">edit campaign</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>End Date</td>
                                            <td>
                                                <?php echo esc_html( isset( $fields['end_date']['formatted'] ) ? $fields['end_date']['formatted'] : 'No end date' ); ?>
                                                <a style="margin: 0 10px" href="<?php echo esc_html( site_url() . '/campaigns/' . $fields['ID'] ); ?>">edit campaign</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Timezone</td>
                                            <td>
                                                <?php if ( isset( $fields['campaign_timezone']['label'] ) ){
                                                    echo esc_html( $fields['campaign_timezone']['label'] );
                                                } else {
                                                    echo esc_html( 'No Campaign Timezone Set' ); ?>
                                                    <img class="dt-icon" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/broken.svg' ) ?>"/>
                                                <?php } ?>
                                                <a style="margin: 0 10px" href="<?php echo esc_html( site_url() . '/campaigns/' . $fields['ID'] ); ?>">edit campaign</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Locations</td>
                                            <td>
                                                <?php
                                                if ( isset( $fields['location_grid_meta'] ) ){
                                                    echo esc_html( join( ' | ', array_map( function ( $a ){
                                                        return $a['label'];
                                                    }, $fields['location_grid_meta'] ) ) );
                                                } elseif ( isset( $fields['location_grid'][0] ) ){
                                                    echo esc_html( join( ' | ', array_map( function ( $a ){
                                                        return $a['label'];
                                                    }, $fields['location_grid'] ) ) );
                                                } else {
                                                    echo esc_html( 'No Campaign Locations set' ); ?>
                                                    <img class='dt-icon' src = "<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/broken.svg' ) ?>" />
                                                <?php } ?>
                                                <a style="margin: 0 10px" href="<?php echo esc_html( site_url() . '/campaigns/' . $fields['ID'] ); ?>">edit campaign</a>
                                            </td>
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
<!--                                        <tr>-->
<!--                                            <td>Export Campaign Subscribers</td>-->
<!--                                            <td>-->
<!--                                                Download a CSV file of this campaign's subscribers on the subscribers list:-->
<!--                                                <a href="--><?php //echo esc_html( site_url() . '/subscriptions/' ); ?><!--" target="_blank">See Subscribers</a>-->
<!--                                            </td>-->
<!--                                        </tr>-->
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

    public static function echo_my_campaigns_select_options( $selected_id ) {
        $campaigns = DT_Posts::list_posts( 'campaigns', [ 'status' => [ 'active', 'pre_signup', 'inactive' ] ] );
        if ( is_wp_error( $campaigns ) ){
            $campaigns = [ 'posts' => [] ];
        }
        ?>

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
        $campaign_url = home_url();
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
                        <li><a href="<?php echo esc_html( $campaign_url ); ?>" target="_blank">Landing Page</a></li>
                        <li><a href="<?php echo esc_html( home_url( '/campaigns' ) ); ?>" target="_blank">Campaigns</a></li>
                        <li><a href="<?php echo esc_html( home_url( '/subscriptions' ) ); ?>" target="_blank">Prayer Warriors (Subscribers)</a></li>
                        <li><a href="https://pray4movement.org/docs/overview/" target="_blank">Documentation</a></li>
                        <li><a href="https://pray4movement.org/docs/translation/" target="_blank">Translate Pages and Emails</a></li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td>
                    <br>
                    QR code to: <a href="<?php echo esc_html( $campaign_url ); ?>"> <?php echo esc_html( $campaign_url ) ?></a>
                    <br>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&color=323a68&data=<?php echo esc_url( $campaign_url ) ?>"
                        title="<?php echo esc_url( $campaign_url ) ?>" alt="<?php echo esc_url( $campaign_url ) ?>"
                        style='width:100%;'/>
                        <br>
                        <a target="_blank" href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&color=323a68&data=<?php echo esc_url( $campaign_url ) ?>">QR code image link</a>
                    <br>
                </td>
            </tr>
            </tbody>
        </table>
        <br>

        <?php
    }
}
