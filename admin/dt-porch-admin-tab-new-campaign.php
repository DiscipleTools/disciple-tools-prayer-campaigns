<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Generic_Porch_Landing_Tab_Starter_Content
 */
class DT_Porch_Admin_Tab_New_Campaign extends DT_Porch_Admin_Tab_Base {
    public $key = 'new_campaign';
    public $title = 'New Campaign';

    public function __construct() {
        parent::__construct( $this->key );

        add_action( 'dt_prayer_campaigns_tab_content', [ $this, 'dt_prayer_campaigns_tab_content' ], 10, 2 );
        add_action( 'init', [ $this, 'handle_new_campaign' ] );
    }

    public function handle_new_campaign(){
        if ( !isset( $_POST['dt_prayer_campaigns_new_campaign_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_prayer_campaigns_new_campaign_nonce'] ) ), 'dt_prayer_campaigns_new_campaign' ) ) {
            return;
        }
        $post_args = dt_recursive_sanitize_array( $_POST );
        if ( !isset( $post_args['campaign_name'], $post_args['wizard_type'], $post_args['campaign_start_date'], $post_args['campaign_languages'] ) ){
            return;
        }
        $fields = [
            'name' => $post_args['campaign_name'],
            'start_date' => dt_format_date( time(), 'Y-m-d' ),
            'status' => 'active',
            'enabled_languages' => [ 'values' => [] ],
            'porch_type' => 'generic-porch',
        ];

        if ( $post_args['wizard_type'] === 'ramadan-porch' ) {
            $fields['porch_type'] = 'ramadan-porch';
            $next_ramadan_start_date = strtotime( dt_get_next_ramadan_start_date() );
            $fields['start_date'] = $next_ramadan_start_date;
            $fields['end_date'] = $next_ramadan_start_date + 30 * DAY_IN_SECONDS;
            $fields['name'] = $fields['name'] ?: 'Ramadan Campaign';
        }

        //start date
        $start_time = strtotime( $post_args['campaign_start_date'] );
        if ( $start_time > time() ){
            $fields['start_date'] = $post_args['campaign_start_date'];
        }
        //end date
        if ( !empty( $post_args['campaign_end_date'] ) ){
            if ( strtotime( $post_args['campaign_end_date'] ) > $start_time ){
                $fields['end_date'] = $post_args['campaign_end_date'];
            }
        }
        //goal
        if ( !empty( $post_args['campaign_goal'] ) ){
            $fields['campaign_goal'] = $post_args['campaign_goal'];
        }
        //languages
        if ( !empty( $post_args['campaign_languages'] ) ){
            foreach ( $post_args['campaign_languages'] as $language ){
                $fields['enabled_languages']['values'][] = [ 'value' => $language ];
            }
        } else {
            $fields['enabled_languages']['values'][] = [ 'value' => 'en_US' ];
        }


        $new_campaign = DT_Posts::create_post( 'campaigns', $fields, true, false );
        if ( is_wp_error( $new_campaign ) ){
            return;
        }

        $default_campaign = get_option( 'dt_campaign_selected_campaign', false );
        if ( empty( $default_campaign ) || isset( $post_args['default_campaign'] ) ){
            update_option( 'dt_campaign_selected_campaign', $new_campaign['ID'] );
        }
        wp_safe_redirect( admin_url( 'admin.php?page=dt_prayer_campaigns&tab=campaign_landing&campaign=' . $new_campaign['ID'] ) );
        exit;
    }

    public function dt_prayer_campaigns_tab_content( $tab, $campaign_id ) {
        if ( $tab !== $this->key ) {
            return;
        }
        $campaign_fields = DT_Posts::get_post_field_settings( 'campaigns' );
        $wizard_types = apply_filters( 'dt_campaigns_wizard_types', [] );
        $languages = DT_Campaign_Languages::get_installed_languages( null );
        $enabled_languages = [ 'en_US' ];

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
            label {
                font-weight: bold;
            }
            .form-table th {
                padding-left: 20px;
                font-weight: 200;
            }
            .form-table td {
                vertical-align: top;
            }
        </style>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->
                        <table class="widefat striped">
                            <thead>
                            <tr>
                                <th><h3>New Campaign</h3></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td style="padding: 0">
                        <form method="post">
                            <input type="hidden" name="action" value="dt_prayer_campaigns_new_campaign">
                            <input type="hidden" name="dt_prayer_campaigns_new_campaign_nonce" value="<?php echo esc_html( wp_create_nonce( 'dt_prayer_campaigns_new_campaign' ) ); ?>">

                            <table class="form-table striped widefat" style="margin: 0; border: none">
                                <tr>
                                    <th scope="row"><label for="campaign_name">Campaign Name</label></th>
                                    <td><input type="text" name="campaign_name" id="campaign_name" class="regular-text" required></td>
                                    <td>
                                        Examples: Pray4France, Pray4Morocco Ramadan 2024 etc
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="wizard_type">Type</label></th>
                                    <td>
                                        <?php foreach ( $wizard_types as $wizard_type => $wizard_details ): ?>

                                            <label style="display: block; padding: 8px">
                                                <input type="radio" name="wizard_type" value="<?php echo esc_html( $wizard_type ); ?>" required>
                                                <?php echo isset( $wizard_details['label'] ) ? esc_html( $wizard_details['label'] ) : esc_html( "$wizard_type" ) ?>
                                            </label>

                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        See <a href="https://prayer.tools/docs/campaign-types/">documentation</a> for types.
                                    </td>
                                </tr>

                                <tr>
                                    <th><label for="campaign_start_date">Start Date</label></th>
                                    <td><input type="date" name="campaign_start_date" id="campaign_start_date" class="regular-text" required></td>
                                    <td>When the campaign will start</td>
                                </tr>
                                <tr>
                                    <th><label for="campaign_end_date">End Date (optional)</label>

                                    </th>
                                    <td>
                                        <input type="date" name="campaign_end_date" id="campaign_end_date" class="regular-text">
                                        <br>
                                        <span id="end-date-msg"></span>
                                    </td>
                                    <td>
                                        When the campaign will end. Leave empty for ongoing campaigns.
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="campaign_goal">Goal</label></th>
                                    <td>
                                        <?php foreach ( $campaign_fields['campaign_goal']['default'] ?? [] as $key => $value ): ?>

                                            <label style="display: block; padding: 8px">
                                                <input type="radio" name="campaign_goal" value="<?php echo esc_html( $key ); ?>" required>
                                                <?php echo isset( $value['label'] ) ? esc_html( $value['label'] ) : esc_html( $key ) ?>
                                            </label>

                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        Whether you aim for covering each day with 24/7 prayer or aim for a number of hours of cumulative prayer every day.
                                        See <a href="https://prayer.tools/docs/campaign-goals/">Campaign Goals</a> for more details
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="campaign_languages">Languages</label></th>
                                    <td>
                                        <?php foreach ( $languages as $language_key => $language ):
                                            $lang_enabled = in_array( $language_key, $enabled_languages, true );
                                            ?>
                                            <label style="display: block; padding: 8px">
                                                <input type="checkbox"
                                                       name="campaign_languages[]"
                                                       <?php checked( $lang_enabled ) ?>
                                                       value="<?php echo esc_html( $language_key ) ?>">
                                                <?php echo esc_html( $language['label'] ) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        Show the campaign interface and prayer fuel in these languages.
                                        <br>
                                        Please don't enable languages you don't plan on creation or installing prayer fuel for.
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="default_campaign">Default Campaign</label></th>
                                    <td>
                                        <input type="checkbox" name="default_campaign" id="default_campaign">
                                    </td>
                                    <td>Make this new campaign the default campaign that is show on the landing page: <a href="<?php echo esc_html( home_url() ); ?>"><?php echo esc_html( home_url() ); ?></a></td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <td>
                                        <button type="submit" class="button button-primary">Create Campaign</button>
                                    </td>
                                    <td></td>
                                </tr>
                            </table>
                        </form>
                                </td>
                            </tr>
                        </tbody>
                    </div>
                </div>
            </div>
        </div>

        <script>
          document.getElementById('campaign_start_date').valueAsDate = new Date();

          jQuery(document).ready(function($){
            $('input[name="wizard_type"]').on('change', function(){
              $('#end-date-msg').html('');
              $('#campaign_end_date').prop('disabled', false);
              if ( $(this).val() === 'ramadan-porch' ){
                $('#campaign_start_date').val('<?php echo esc_js( dt_get_next_ramadan_start_date() ); ?>');
                $('#campaign_end_date').val('<?php echo esc_js( dt_get_next_ramadan_end_date() ); ?>');
                $('#end-date-msg').html('Please make sure these dates match the start end of ramadan in your target region.').css('color', 'red');
              }
              if ( $(this).val() === 'ongoing' ){
                $('#campaign_end_date').val('').prop('disabled', true);
              }
            });
          });
        </script>
        <?php
    }
}
new DT_Porch_Admin_Tab_New_Campaign();