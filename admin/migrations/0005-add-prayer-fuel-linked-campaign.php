<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0001
 */
class DT_Prayer_Campaign_Migration_0005 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        $campaign = DT_Campaign_Landing_Settings::get_campaign();

        if ( !isset( $campaign['start_date']['formatted'] ) ) {
            return;
        }
        $campaign_id = $campaign['ID'] ?? null;

        $prayer_fuel_posts = new WP_Query( [
            'post_type' => CAMPAIGN_LANDING_POST_TYPE,
            'post_status' => [ 'publish', 'future' ] ,
            'posts_per_page' => -1,
            'orderby' => 'post_date',
            'order' => 'DESC',
        ] );

        foreach ( $prayer_fuel_posts->posts as $post ) {
            if ( strtotime( $post->post_date ) >= $campaign['start_date']['timestamp'] - 5 * MONTH_IN_SECONDS ) {
                update_post_meta( $post->ID, 'linked_campaign', $campaign_id );
            } elseif ( strtotime( $post->post_title ) && strtotime( $post->post_title ) >= $campaign['start_date']['timestamp'] - MONTH_IN_SECONDS ) {
                update_post_meta( $post->ID, 'linked_campaign', $campaign_id );
            }
        }
    }

    /**
     * @throws \Exception  Got error when dropping table $name.
     */
    public function down() {
    }

    /**
     * Test function
     */
    public function test() {
    }

}


