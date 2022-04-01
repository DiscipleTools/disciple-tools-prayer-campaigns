<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0001
 */
class DT_Prayer_Campaign_Migration_0001 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        global $wpdb;
        $wpdb->query( "UPDATE $wpdb->postmeta pm
            JOIN $wpdb->postmeta pm2 ON (pm2.post_ID = pm.post_ID AND pm2.meta_key = 'tags' AND pm2.meta_value = 'sent-pre-signup' )
            JOIN $wpdb->posts p ON ( p.ID = pm.post_ID AND p.post_type = 'subscriptions' )
            set pm.meta_value = 1
            WHERE pm.meta_key = 'receive_prayer_time_notifications'
        ");
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
