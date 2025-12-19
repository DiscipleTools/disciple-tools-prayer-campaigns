<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Class DT_Prayer_Campaigns_Tab_General
 */
class DT_Prayer_Campaigns_Campaigns extends DT_Porch_Admin_Tab_Base {

    private $settings_manager;

    public static $no_campaign_key = 'none';
    public $key = 'campaigns';

    public function __construct() {
        parent::__construct( $this->key );
        $this->settings_manager = new DT_Campaign_Global_Settings();
        add_action( 'dt_prayer_campaigns_tab_content', [ $this, 'dt_prayer_campaigns_tab_content' ], 10, 2 );
    }

    public function dt_prayer_campaigns_tab_content( $tab, $campaign_id ){
        if ( $tab !== $this->key ){
            return;
        }
        $this->process_default_campaign_setting();
        $this->process_p4m_participation_settings();
        $this->content( true );
    }

    public function body_content() {
        $this->campaign_selector();
        $this->box_default_campaign();
        $this->box_p4m_participation();
    }



    public static function setup_wizard_for_type( $wizard_type, $new_campaign_name = null, $args = [] ){
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

        $default_values = [
            'start_date' => dt_format_date( time(), 'Y-m-d' ),
            'languages' => [ 'en_US' ],
        ];
        $default_values = array_merge( $default_values, $args );

        $wizard_details = isset( $wizard_types[$wizard_type] ) ? $wizard_types[$wizard_type] : [];
        $porch_type = isset( $wizard_details['porch'] ) ? $wizard_details['porch'] : 'generic-porch';
        $campaign_type = isset( $wizard_details['campaign_type'] ) ? $wizard_details['campaign_type'] : 'generic';

        if ( empty( $wizard_details ) ) {
            return;
        }

        $fields = [
            'name' => 'Prayer Campaign',
            'start_date' => $default_values['start_date'],
            'status' => 'active',
            'porch_type' => $porch_type,
        ];

        if ( $porch_type === 'ramadan-porch' ) {
            $fields['start_date'] = $default_values['start_date'] ?: dt_get_next_ramadan_start_date();
            $fields['end_date'] = $default_values['end_date'] ?: dt_get_next_ramadan_end_date();
            $fields['name'] = 'Ramadan Campaign';
        } else if ( $wizard_type === 'generic' ) {
            $fields['end_date'] = $default_values['end_date'] ?: dt_format_date( time() + 30 * DAY_IN_SECONDS, 'Y-m-d' );
        }

        if ( $new_campaign_name ) {
            $fields['name'] = $new_campaign_name;
        }
        $fields['enabled_languages'] = [ 'values' => [] ];
        foreach ( $default_values['languages'] as $lang ){
            $fields['enabled_languages']['values'][] = [ 'value' => $lang ];
        }
        if ( !empty( $default_values['campaign_goal'] ) ){
            $fields['campaign_goal'] = $default_values['campaign_goal'];
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


    public function campaign_selector(){
        $campaigns = DT_Posts::list_posts( 'campaigns', [] );
        if ( is_wp_error( $campaigns ) ){
            $campaigns = [ 'posts' => [] ];
        }
        $default_campaign = get_option( 'dt_campaign_selected_campaign', false );
        ?>
        <style>
            .campaigns-list tr:hover {
                background-color: rgba(186, 185, 185, 0.73);
            }
        </style>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><h3>Select a campaign to edit settings</h3></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php if ( empty( $campaigns['posts'] ) ) : ?>
                        <td>
                            <p>Let's Get Started!</p>
                            <p>
                                <a class="button" href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=new_campaign' ) ); ?>">
                                    Create a New Campaign
                                </a>
                            </p>
                         </td>
                    <?php else : ?>
                        <td style="padding: 0">
                            <table class="widefat striped campaigns-list" style="margin: 0; border: none">
                                <thead>
                                    <tr>
                                        <th>Campaign Name</th>
                                        <th>Edit</th>
                                        <th>Landing Page Url</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ( $campaigns['posts'] as $index => $campaign ) :
                                    $url = $campaign['ID'] === $default_campaign ? home_url() : DT_Campaign_Landing_Settings::get_landing_page_url( $campaign['ID'] );
                                    $color = DT_Campaign_Landing_Settings::get_campaign_color( $campaign['ID'] );
                                    ?>
                                    <tr>
                                        <td>
                                            <span style="background-color: <?php echo esc_html( $color ) ?>; width: 15px; height: 15px; display: inline-block; vertical-align: middle; border-radius: 5px;"></span>
                                            <a    style="display: inline-block" href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=campaign_landing&campaign=' . $campaign['ID'] ) ) ?>">
                                                <?php echo esc_html( $campaign['name'] ) ?>
                                            </a>

                                        </td>
                                        <td>
                                            <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=campaign_landing&campaign=' . $campaign['ID'] ) ) ?>">
                                                Edit
                                            </a>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_html( $url ) ?>" target="_blank">
                                                <?php echo esc_html( $url ) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a class="button clone-campaign-but" data-campaign_id="<?php echo esc_attr( $campaign['ID'] ) ?>">
                                                Clone
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    <?php endif; ?>
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
                    <th><h3>Default Campaign</h3></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <form method="POST">
                            <?php wp_nonce_field( 'default_campaign_nonce', 'default_campaign_nonce' ) ?>

                            <p>Which campaign should be shown on the landing page: <a href="<?php echo esc_html( $home_url ); ?>"><?php echo esc_html( $home_url ); ?></a></p>

                            <select name="default_campaign" id="default_campaign">
                                <option value="none">None</option>
                                <?php $this->echo_my_campaigns_select_options( $default_campaign ) ?>
                            </select>
                            <button type="submit" class="button float-right" name="default_campaign_submit">Update</button>

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
                    <th><h3>Prayer.Tools Participation</h3></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <form method="POST" style='max-width: 1000px'>
                            <input type="hidden" name="p4m_participation_nonce" id="p4m_participation_nonce"
                                    value="<?php echo esc_attr( wp_create_nonce( 'p4m_participation' ) ) ?>"/>

                            <input name="p4m_participation" id="p4m_participation" type="checkbox"
                                   <?php checked( $participation || $participation_force ); disabled( $participation_force ) ?> />
                            <label for="p4m_participation">
                                List my campaigns on <a href="https://prayer.tools/" target="_blank">https://prayer.tools</a>. See the <a href="https://prayer.tools/campaigns" target="_blank">Global campaign list</a>
                                <br>
                                This allows other users to see your campaign and join in prayer. And shows the progress towards global prayer coverage.
                            </label>
                            <br>
                            <button type='submit' name='p4m_participation_submit' class='button' <?php disabled( $participation_force ) ?>>
                                <?php esc_html_e( 'Update', 'disciple-tools-prayer-campaigns' ) ?>
                            </button>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>

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
<!--                        <li><a href="--><?php //echo esc_html( $campaign_url ); ?><!--" target="_blank">Landing Page</a></li>-->
<!--                        <li><a href="--><?php //echo esc_html( home_url( '/campaigns' ) ); ?><!--" target="_blank">Campaigns</a></li>-->
                        <li><a href="https://prayer.tools/docs/overview/" target="_blank">Documentation</a></li>
                        <li><a href="<?php echo esc_html( home_url( '/subscriptions' ) ); ?>" target="_blank">Prayer Warriors (Subscribers)</a></li>
                        <li><a href="https://prayer.tools/docs/translation/" target="_blank">Translate Campaign Tool</a></li>
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
new DT_Prayer_Campaigns_Campaigns();
