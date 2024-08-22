<?php
if ( !defined( 'ABSPATH' ) ){
    exit;
} // Exit if accessed directly

/**
 * Class DT_Generic_Porch_Landing_Tab_Starter_Content
 */
class DT_Porch_Admin_Tab_Scripts extends DT_Porch_Admin_Tab_Base{
    public $key = 'scripts';
    public $title = 'Scripts';

    public function __construct(){
        parent::__construct( $this->key );

        add_action( 'dt_prayer_campaigns_tab_content', [ $this, 'dt_prayer_campaigns_tab_content' ], 10, 2 );
        add_action( 'init', [ $this, 'handle_scripts' ] );
    }

    public function handle_scripts(){
        if ( !isset( $_POST['dt_prayer_campaigns_scripts_nonce'] ) || !wp_verify_nonce( sanitize_key( wp_unslash( $_POST['dt_prayer_campaigns_scripts_nonce'] ) ), 'dt_prayer_campaigns_scripts' ) ){
            return;
        }
        $post_args = dt_recursive_sanitize_array( $_POST );

        global $wpdb;


        /**
         * Remove duplicate recurring signups
         */
        if ( isset( $post_args['remove_duplicate_signups'] ) ){
            $dups = $wpdb->get_results( "
                SELECT r2.id, r.post_id, r.parent_id
                FROM $wpdb->dt_reports r
                INNER JOIN $wpdb->dt_reports r2 ON ( 
                    r2.post_id = r.post_id
                    AND r2.parent_id = r.parent_id
                    AND r2.time_begin = r.time_begin
                    AND r2.time_end = r.time_end
                    AND r2.value = r.value
                    AND r2.id > r.id
                )
                WHERE r.type = 'recurring_signup'
            ", ARRAY_A );

            foreach ( $dups as $dup ){
                Disciple_Tools_Reports::delete( $dup['id'] );
                $wpdb->query( $wpdb->prepare(
                    "DELETE r
                        FROM $wpdb->dt_reports r
                        WHERE parent_id = %s
                        AND post_id = %s
                        AND type = 'campaign_app'
                        AND subtype = 'recurring_signup'
                        AND value = %s
                        ",
                    $dup['parent_id'], $dup['post_id'], $dup['id']
                ) );
            }
        }

        /**
         * Fixes the duration of signups that overlap. 10am for 30 mins. 10:15am for 30mins. etc
         */
        if ( isset( $post_args['fix_durations'] ) ){
            $to_fix = $wpdb->get_results( "
                SELECT r.post_id, r.parent_id FROM $wpdb->dt_reports r 
                INNER JOIN $wpdb->dt_reports r2 ON 
                  ( r2.post_id = r.post_id 
                    AND r2.type = r.type AND r2.subtype = r.subtype
                    AND r2.time_begin > r.time_begin
                    AND r2.time_begin < r.time_end
                  )
                WHERE r.subtype = 'recurring_signup'
                GROUP BY r.post_id, r.parent_id
            ", ARRAY_A );

            foreach ( $to_fix as $row ){
                $subscriber = DT_Posts::get_post( 'subscriptions', $row['post_id'], true, false );
                dt_campaign_set_translation( $subscriber['lang'] ?? 'en_US' );
                $timezone = !empty( $subscriber['timezone'] ) ? $subscriber['timezone'] : 'America/Chicago';

                $rc = new Recurring_Signups( $row['post_id'], $row['parent_id'] );
                $r_signups = $rc->get_recurring_signups();
                //order by first
                usort( $r_signups, function ( $a, $b ){
                    return $a['first'] <=> $b['first'];
                } );
                $signups = [];
                foreach ( $r_signups as $signup ){
                    $signups[$signup['first']] = $signup;
                }

                foreach ( $signups as $signup_time => $signup ){
                    $next_signup = null;
                    foreach ( $signups as $s ){
                        if ( $signup['first'] < $s['first'] && $signup['first'] + $signup['duration'] * 60 > $s['first'] ){
                            $next_signup = $s;
                            break;
                        }
                    }
                    if ( $next_signup ){
                        $different = ( $next_signup['first'] - $signup['first'] ) / 60;
                        $new_duration = $different;
                        //change duration
                        $recurring_signup = Disciple_Tools_Reports::get( $signup['report_id'], 'id' );
                        $label = DT_Subscriptions::get_recurring_signup_label( $signup, $timezone, $subscriber['lang'] ?? 'en_US' );
                        $recurring_signup['payload']['duration'] = $new_duration;
                        $recurring_signup['payload']['label'] = $label;
                        $recurring_signup['label'] = $label;
                        Disciple_Tools_Reports::update( $recurring_signup );
                        //updates prayer times
                        //adjust the duration on each signup
                        $wpdb->query( $wpdb->prepare( "
                            UPDATE $wpdb->dt_reports
                            SET time_end = time_begin + %d
                            WHERE post_id = %d
                            AND type = 'campaign_app'
                            AND subtype = 'recurring_signup'
                            AND value = %d
                        ", $new_duration * 60, $signup['post_id'], $signup['report_id'] ) );
                    }
                }
            }
            dt_campaign_set_translation( 'en_US' );
        }


        if ( isset( $post_args['combine_prayer_campaigns'] ) ){
            $to_combine = $wpdb->get_results( "
                SELECT r.post_id, r.parent_id FROM $wpdb->dt_reports r 
                INNER JOIN $wpdb->dt_reports r2 ON 
                  ( r2.post_id = r.post_id 
                    AND r2.type = r.type AND r2.subtype = r.subtype
                    AND r2.time_begin = r.time_begin + 900
                  )
                WHERE r.type = 'recurring_signup'
                GROUP BY r.post_id, r.parent_id
            ", ARRAY_A );
            foreach ( $to_combine as $row ){
                $rc = new Recurring_Signups( $row['post_id'], $row['parent_id'] );
                $r_signups = $rc->get_recurring_signups();
                $subscriber = DT_Posts::get_post( 'subscriptions', $row['post_id'], true, false );
                dt_campaign_set_translation( $subscriber['lang'] ?? 'en_US' );
                $timezone = !empty( $subscriber['timezone'] ) ? $subscriber['timezone'] : 'America/Chicago';
                //order by first
                usort( $r_signups, function ( $a, $b ){
                    return $a['first'] <=> $b['first'];
                } );
                $signups = [];
                foreach ( $r_signups as $signup ){
                    $signups[$signup['first']] = $signup;
                }

                foreach ( $signups as $signup_time => $signup ){
                    $previous_signup = null;
                    foreach ( $signups as $s ){
                        if ( $s['first'] + $s['duration'] * 60 === $signup['first'] && !isset( $s['combined'] ) ){
                            $previous_signup = $s;
                            break;
                        }
                    }

                    if ( $previous_signup && isset( $signups[$previous_signup['first']] ) ){
                        $signups[$previous_signup['first']]['duration'] += $signup['duration'];
                        $signups[$previous_signup['first']]['last'] = $signup['last'];
                        $signups[$previous_signup['first']]['label'] = '';
                        $signups[$previous_signup['first']]['update'] = true;
                        $signups[$signup['first']]['combined'] = $previous_signup['report_id'];
                    }
                }

                foreach ( $signups as $signup ){
                    if ( isset( $signup['update'] ) ){
                        $recurring_signup = Disciple_Tools_Reports::get( $signup['report_id'], 'id' );
                        $recurring_signup['time_begin'] = $signup['first'];
                        $recurring_signup['time_end'] = $signup['last'];
                        $label = DT_Subscriptions::get_recurring_signup_label( $signup, $timezone, $subscriber['lang'] ?? 'en_US' );
                        $recurring_signup['label'] = $label;
                        $recurring_signup['payload']['label'] = $label;
                        $recurring_signup['payload']['duration'] = $signup['duration'];
                        Disciple_Tools_Reports::update( $recurring_signup );

                        //adjust the duration on each signup
                        $wpdb->query( $wpdb->prepare( "
                            UPDATE $wpdb->dt_reports
                            SET time_end = time_begin + %d
                            WHERE post_id = %d
                            AND type = 'campaign_app'
                            AND subtype = 'recurring_signup'
                            AND value = %d
                        ", $signup['duration'] * 60, $signup['post_id'], $signup['report_id'] ) );
                    }
                }
                foreach ( $signups as $signup ){
                    if ( isset( $signup['combined'] ) ){
                        //delete the report
                        Disciple_Tools_Reports::delete( $signup['report_id'] );
                        $wpdb->query( $wpdb->prepare(
                            "DELETE r
                                FROM $wpdb->dt_reports r
                                WHERE parent_id = %s
                                AND post_id = %s
                                AND type = 'campaign_app'
                                AND subtype = 'recurring_signup'
                                AND value = %s
                                ",
                            $signup['campaign_id'], $signup['post_id'], $signup['report_id']
                        ) );
                    }
                }
            }
            dt_campaign_set_translation( 'en_US' );
        }
    }

    public function dt_prayer_campaigns_tab_content( $tab, $campaign_id ){
        if ( $tab !== $this->key ){
            return;
        }

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
                                <th><h3>Scripts</h3></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td style="padding: 0">
                                    <form method="post">
                                        <input type="hidden" name="action" value="dt_prayer_campaigns_scripts">
                                        <input type="hidden" name="dt_prayer_campaigns_scripts_nonce"
                                               value="<?php echo esc_html( wp_create_nonce( 'dt_prayer_campaigns_scripts' ) ); ?>">

                                        <table class="form-table striped widefat" style="margin: 0; border: none">
                                            <tr>
                                                <th>Remove Duplicate Recurring Signups</th>
                                                <td>
                                                    <button type="submit" name="remove_duplicate_signups"
                                                            value="remove_duplicate_signups">Remove
                                                    </button>
                                                </td>
                                                <td>
                                                    <p>10am for 15mins and 10am for 15mins</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Fix Singunp Durations</th>
                                                <td>
                                                    <button type="submit" name="fix_durations" value="fix_durations">
                                                        Fix
                                                    </button>
                                                </td>
                                                <td>
                                                    <p>Fixes the duration of signups that overlap. 10am for 30 mins. 10:15am for 30mins. etc </p>
                                                </td>
                                            <tr>
                                                <th>Combine Adjacent Prayer Times</th>
                                                <td>
                                                    <button type="submit" name="combine_prayer_campaigns"
                                                            value="combine_prayer_campaigns">Combine
                                                    </button>
                                                </td>
                                                <td>
                                                    <p>These become one prayer time: 10am for 15mins and 10:15am for 15mins</p>
                                                </td>
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
        <?php
    }
}

new DT_Porch_Admin_Tab_Scripts();