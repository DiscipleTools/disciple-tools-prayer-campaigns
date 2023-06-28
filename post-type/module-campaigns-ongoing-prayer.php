<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class DT_Campaign_Ongoing_Prayer extends DT_Module_Base {
    public $module = 'campaigns_ongoing_prayer';
    public $post_type = 'campaigns';
    public $magic_link_root = 'campaign_app';
    public $magic_link_type = 'ongoing';

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    } // End instance()

    public function __construct() {
        $module_enabled = dt_is_module_enabled( 'subscriptions_management', true );
        if ( !$module_enabled ){
            return;
        }
        parent::__construct();
        // register tiles if on details page
//        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 30, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
//        add_filter( 'dt_post_update_fields', [ $this, 'dt_post_update_fields' ], 20, 3 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
//        add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );

        if ( !wp_next_scheduled( 'dt_prayer_ongoing_resubscription' ) ) {
            wp_schedule_event( time(), 'daily', 'dt_prayer_ongoing_resubscription' );
        }
        add_action( 'dt_prayer_ongoing_resubscription', [ $this, 'dt_prayer_ongoing_resubscription' ] );
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === $this->post_type ){
            $fields['type']['default']['ongoing'] = [
                'label' => '24/7 Ongoing Campaign',
                'description' => __( '247 Prayer for months', 'disciple-tools-prayer-campaigns' ),
                'visibility' => __( 'Collaborators', 'disciple-tools-prayer-campaigns' ),
                'color' => '#4CAF50',
            ];
            $key_name = 'public_key';
            if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
                $key_name = DT_Magic_URL::get_public_key_meta_key( 'campaign_app', $this->magic_link_type );
            }
            $fields[$key_name] = [
                'name'   => 'Private Key',
                'description' => 'Private key for subscriber access',
                'type'   => 'hash',
                'default' => dt_create_unique_key(),
                'hidden' => true,
                'customizable' => false,
            ];
        }
        return $fields;
    }


    public function dt_details_additional_tiles( $tiles, $post_type = '' ){
        return $tiles;
    }

    public function dt_post_update_fields( $fields, $post_type, $post_id ){
        return $fields;
    }

    public function dt_details_additional_section( $section, $post_type ) {
        if ( $post_type === $this->post_type ) {
            $record = DT_Posts::get_post( $post_type, get_the_ID() );
            if ( !isset( $record['type']['key'] ) || 'ongoing' !== $record['type']['key'] ){
                return;
            }
            if ( $section === 'status' ){
                $link = DT_Magic_URL::get_link_url_for_post( $post_type, $record['ID'], $this->magic_link_root, $this->magic_link_type );
                ?>
                <div class="cell small-12 medium-4">
                    <div class="section-subheader">
                        <?php esc_html_e( 'Shortcodes', 'disciple-tools-prayer-campaigns' ); ?>
                    </div>
                    <a class="button hollow small" target="_blank" href="<?php echo esc_html( $link ); ?>"><?php esc_html_e( 'View Components', 'disciple-tools-prayer-campaigns' ); ?></a>
                </div>
                <?php
            }
        }

    }


    public function dt_prayer_ongoing_resubscription(){

        $active_ongoing_campaigns = DT_Posts::list_posts( 'campaigns', [ 'status' => [ 'active' ], 'type' => [ 'ongoing' ] ], false );

        if ( is_wp_error( $active_ongoing_campaigns ) ){
            return;
        }

        $campaign_ids = array_map( function ( $a ){
            return $a['ID'];
        }, $active_ongoing_campaigns['posts'] );

        $campaign_ids_sql = dt_array_to_sql( $campaign_ids );
        $two_weeks_from_now = time() + 14 * DAY_IN_SECONDS;
        $two_weeks_ago = time() - 14 * DAY_IN_SECONDS;
        $one_week_ago = time() - 7 * DAY_IN_SECONDS;
        $now = time();

        //select all the subscribers whose prayer time is less than 2 weeks away and who signed up more than 2 weeks ago.
        //and we didn't send a tickler email to in the last week and they have signed up to something since the last tickler.
        global $wpdb;
        //phpcs:disable
        $subscribers_ids = $wpdb->get_results( $wpdb->prepare( "
            SELECT r.post_id, r.parent_id
            FROM $wpdb->dt_reports r
            WHERE parent_id IN ( $campaign_ids_sql )
            AND r.time_begin = ( SELECT MAX( time_begin ) as time FROM $wpdb->dt_reports r2 WHERE r2.post_id = r.post_id )
            AND r.time_begin < %d
            AND r.time_begin > %d
            AND r.timestamp < %d
            AND r.subtype = 'ongoing'
            AND r.post_id NOT IN (
                SELECT post_id from $wpdb->dt_reports r3
                WHERE r3.post_id = r.post_id
                AND r3.type = '2_week_tickler'
                AND ( r3.timestamp > %d OR r3.timestamp > r.timestamp )
            )
            GROUP BY r.post_id
        ", $two_weeks_from_now, $now, $two_weeks_ago, $one_week_ago ), ARRAY_A );
        //phpcs:enable

        foreach ( $subscribers_ids as $row ){

            $sent = DT_Prayer_Campaigns_Send_Email::send_resubscribe_tickler( $row['post_id'] );
            if ( $sent ){
                $report = [
                    'post_type' => 'subscriptions',
                    'type' => '2_week_tickler',
                    'post_id' => $row['post_id'],
                    'parent_id' => $row['parent_id']
                ];
                Disciple_Tools_Reports::insert( $report, true, false );
            }
        }
    }
}

