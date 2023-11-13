<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class DT_Prayer_Campaign_Migration_0007
 */
class DT_Prayer_Campaign_Migration_0007 extends DT_Prayer_Campaign_Migration {
    /**
     * @throws \Exception  Got error when creating table $name.
     */
    public function up() {
        global $wpdb;

        if ( ! isset( $wpdb->dt_translations ) ) {
            $wpdb->dt_translations = $wpdb->prefix . 'dt_translations';
        }

        $charset_collate = $wpdb->get_charset_collate();
        $rv = $wpdb->query(
            "CREATE TABLE IF NOT EXISTS $wpdb->dt_translations (
                `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `post_id` BIGINT(20) UNSIGNED NOT NULL,
                `key` varchar(255) NOT NULL DEFAULT '',
                `language` varchar(20) NOT NULL DEFAULT '',
                `value` longtext NOT NULL,
                PRIMARY KEY (`id`)
            ) $charset_collate;"
        ); // WPCS: unprepared SQL OK
        if ( $rv == false ) {
            throw new Exception( "Got error when creating table $wpdb->dt_translations: $wpdb->last_error" );
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


