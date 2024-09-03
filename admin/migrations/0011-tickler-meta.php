<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0011
 */
class DT_Prayer_Campaign_Migration_0011 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        //create a dt_reportmeta for each tickler in the dt_reports table
        global $wpdb;
        $wpdb->query( "
            INSERT INTO $wpdb->dt_reportmeta (report_id, meta_key, meta_value)
            SELECT r.id, 'resubscribe_tickler', r2.timestamp
            FROM $wpdb->dt_reports r
            INNER JOIN $wpdb->dt_reports r2 ON ( 
                r2.post_type = 'subscriptions'
                AND r2.type LIKE '%tickler%'
                AND r2.parent_id = r.parent_id 
                AND r2.post_id = r.post_id 
                AND r2.time_end = r.time_end
            )
            WHERE r.post_type = 'subscriptions'
            AND r.type = 'recurring_signup'
        " );
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
