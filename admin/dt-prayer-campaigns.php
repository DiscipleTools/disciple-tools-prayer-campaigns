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
        $campaign_type = isset( $wizard_details['campaign_type'] ) ? $wizard_details['campaign_type'] : 'ongoing';

        if ( empty( $wizard_details ) ) {
            return;
        }

        $fields = [
            'name' => 'Campaign',
            'type' => $campaign_type,
            'start_date' => dt_format_date( time(), 'Y-m-d' ),
            'status' => 'active',
        ];

        if ( $porch_type === 'ramadan-porch' ) {
            $next_ramadan_start_date = strtotime( dt_get_next_ramadan_start_date() );
            $fields['start_date'] = $next_ramadan_start_date;
            $fields['end_date'] = $next_ramadan_start_date + 30 * DAY_IN_SECONDS;
            $fields['name'] = 'Ramadan Campaign';
        } else if ( $campaign_type === '24hour' ) {
            $fields['end_date'] = dt_format_date( time() + 30 * DAY_IN_SECONDS, 'Y-m-d' );
            $fields['name'] = 'Fixed Dates Campaign';
        } elseif ( $campaign_type === 'ongoing' ){
            $fields['name'] = 'Ongoing Campaign';
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

                DT_Porch_Selector::instance()->set_selected_porch_id( $selected_porch );

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

//                        $this->box_select_porch();

                        if ( DT_Porch_Selector::instance()->has_selected_porch() ) {
                            $this->box_campaign();
                        }

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


    public function box_default_campaign(){
        $home_url = home_url();
        $default_campaign = get_option( 'dt_campaign_selected_campaign', false );

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

                            <p>Which campaign should be shown on this page: <?php echo esc_html( $home_url ); ?></p>

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

        $participation = $this->settings_manager->get( 'p4m_participation', true );
        $participation_force = apply_filters( 'p4m_participation', false ) && !is_super_admin();


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
                                                List my campaign on <a href="https://pray4movement.org/" target="_blank">https://pray4movement.org</a>.
                                                <br>
                                                This allows other users to see your campaign and join in prayer. And shows the progress towards global prayer coverage.
                                            </label>
                                        </td>
                                        <td style="min-width: 100px; vertical-align: middle">
                                            <button type='submit' name='p4m_participation_submit' class='button'>
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

    private function setup_wizard(){
        ?>

        <table class="widefat striped">
            <thead>
            <tr>
                <th>Campaign Wizard</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <form method="POST">
                        <input type="hidden" name="campaign_settings_nonce" id="campaign_settings_nonce"
                               value="<?php echo esc_attr( wp_create_nonce( 'campaign_settings' ) ) ?>"/>

                        <h2>Choose the type of campaign to create:</h2>
                        <p>
                            <?php $this->display_wizard_buttons() ?>
                        </p>
                        <p>
                            <label for="new-campaign-name">Campaign Name</label>
                            <input id="new-campaign-name" name="new-campaign-name" required type="text" placeholder="My next prayer campaign name">
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
        $editing_campaign = isset( $_GET['campaign'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign'] ) ) : null;

        $selected_porch = DT_Porch_Selector::instance()->get_selected_porch_id();
//         if ( empty( $editing_campaign ) ){
// //            return;
//         }
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
//        $campaign = DT_Posts::get_post( 'campaigns', $editing_campaign );
        $campaign_field_settings = DT_Posts::get_post_field_settings( 'campaigns' )
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
<!--                                    <tr>-->
<!--                                        <td>-->
<!--                                            <label for="select_porch">--><?php //echo esc_html( 'Select Landing Page Type' ) ?><!--</label>-->
<!--                                        </td>-->
<!--                                        <td>-->
<!--                                            <select name="select_porch" id="select_porch" --><?php //selected( DT_Porch_Selector::instance()->get_selected_porch_id() ) ?><!-->-->
<!--                                                    <option-->
<!--                                                        --><?php //echo !isset( $settings['selected_porch'] ) ? 'selected' : '' ?>
<!--                                                        value=""-->
<!--                                                    >-->
<!--                                                        --><?php //esc_html_e( 'None', 'disciple-tools-prayer-campaigns' ) ?>
<!--                                                    </option>-->
<!---->
<!--                                                --><?php //foreach ( $porches as $id => $porch ): ?>
<!---->
<!--                                                    <option-->
<!--                                                        value="--><?php //echo esc_html( $id ) ?><!--"-->
<!--                                                        --><?php //echo DT_Porch_Selector::instance()->get_selected_porch_id() && DT_Porch_Selector::instance()->get_selected_porch_id() === $id ? 'selected' : '' ?>
<!--                                                    >-->
<!--                                                        --><?php //echo esc_html( $porch['label'] ) ?>
<!--                                                    </option>-->
<!---->
<!--                                                --><?php //endforeach; ?>
<!---->
<!--                                            </select>-->
<!--                                        </td>-->
<!--                                    </tr>-->
                                    <tr>

                                        <td>Landing Page Title</td>
                                        <td>
                                            <strong><?php echo esc_html( isset( DT_Porch_Settings::settings()['title']['value'] ) ? DT_Porch_Settings::settings()['title']['value'] : '' ); ?></strong>
                                            <a style="margin-inline-start: 10px" href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=translations' ) ); ?>">change</a>
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
        $fields = DT_Campaign_Landing_Settings::get_campaign( null, true );
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
                                        <tr>
                                            <td>Export Campaign Subscribers</td>
                                            <td><button type="submit" class="button" name="download_csv">Download CSV</td>
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
                        <li><a href="https://pray4movement.org/docs/translation/" target="_blank">Translate Pages and Emails</a></li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td>
                    <br>
                    Landing Page QR code:
                    <br>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&color=323a68&data=<?php echo esc_url( home_url() ) ?>"
                        title="<?php echo esc_url( home_url() ) ?>" alt="<?php echo esc_url( home_url() ) ?>"
                        style='width:100%;'/>
                        <br>
                        <a target="_blank" href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&color=323a68&data=<?php echo esc_url( home_url() ) ?>">direct link</a>
                    <br>
                </td>
            </tr>
            </tbody>
        </table>
        <br>

        <?php
    }
}
