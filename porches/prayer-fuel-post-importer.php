<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Provides functionality to import the prayer posts into the DB.
 * Adjusting titles, dates, meta etc. during the import.
 */
class DT_Campaign_Prayer_Post_Importer {

    public $import_meta_key = "import_source";
    public $import_meta_value = "disciple-tools-prayer-campaigns";
    public static $instance;
    /**
     * In date string format
     */
    private $append_date;

    public static function instance() {
        if ( !self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_filter( 'wp_import_posts', array( $this, 'dt_import_prayer_posts' ) );
        add_filter( 'wp_import_existing_post', array( $this, 'dt_import_existing_post' ) );
    }

    /**
     * Forces the WP_Import to always import posts regardless of whether a post exists with the same name
     * and post_date
     */
    public function dt_import_existing_post() {
        return 0;
    }

    /**
     * Set the append date for when posts are imported
     *
     * @param string $date
     */
    public function set_append_date( string $date ) {
        $this->append_date = $date;
    }

    /**
     * Import the prayer posts, scheduling them to start at a particular date
     */
    public function dt_import_prayer_posts( $posts ) {
        // Copy the post. Recommended
        $new_posts = $posts;

        // TODO: get the list of languages for this campaign
        $available_languages = [ "en_US", "fr_FR", "ar_EG", "es_ES" ];
        $start_date = [];

        foreach ( $available_languages as $lang ) {
            if ( $this->append_date ) {
                $start_date[$lang] = $this->append_date;
            } else {
                $start_date[$lang] = $this->mysql_date( $this->next_day_timestamp( $this->find_latest_prayer_fuel_date( $lang ) ) );
            }
            $start_date[$lang] = $this->mysql_date( strtotime( $start_date[$lang] ) );
        }

        foreach ( $new_posts as $i => $post ) {
            $post_lang = $this->get_post_meta( $post["postmeta"], "post_language" );
            if ( empty( $post_lang ) || !in_array( $post_lang, $available_languages, true ) ) {
                continue;
            }

            $post_start_date = $start_date[$post_lang];

            $post["post_date"] = $post_start_date;
            $post["post_date_gmt"] = $post_start_date;

            $this->set_post_meta( $post["postmeta"], $this->import_meta_key, $this->import_meta_value );

            $campaign_day = DT_Campaign_Settings::what_day_in_campaign( $post_start_date );

            $this->set_post_meta( $post["postmeta"], "day", $campaign_day );

            // TODO: give each post the correct magic_link_key

            $new_posts[$i] = $post;

            $start_date[$post_lang] = $this->mysql_date( $this->next_day_timestamp( $start_date[$post_lang] ) );
        }

        $this->append_date = null;

        return $new_posts;
    }

    /**
     * Get the meta matching the $key from the $postmeta
     *
     * @param array postmeta
     * @param string $key
     *
     * @return string
     */
    private function get_post_meta( $postmeta, $key ) {
        foreach ( $postmeta as $i => $meta ) {
            if ( $meta["key"] === $key ) {
                return $meta["value"];
            }
        }
        return "";
    }

    /**
     * Set the meta with $key and $value in the $postmeta
     *
     * @param array postmeta
     * @param string $key
     * @param string $value
     */
    private function set_post_meta( &$postmeta, $key, $value ) {
        $is_meta_set = false;
        foreach ( $postmeta as $j => $meta ) {
            if ( $meta["key"] === $key ) {
                    $postmeta[$j]["value"] = $value;
                    $is_meta_set = true;
            }
        }

        if ( !$is_meta_set ) {
            $postmeta[] = [
                "key" => $key,
                "value" => $value,
            ];
        }
    }

    /**
     * Return the date of the latest prayer fuel post that was imported in mysql format
     *
     * @param string $lang
     *
     * @return string
     */
    public function find_latest_prayer_fuel_date( $lang = null ) {
        $latest_prayer_fuel = $this->find_latest_prayer_fuel_data( $lang );

        $today_timestamp = date_timestamp_get( new DateTime() );
        $campaign = DT_Campaign_Settings::get_campaign();
        $campaign_start = $campaign["start_date"]["timestamp"];

        $start_posting_from = max( $campaign_start, $today_timestamp );

        $latest_prayer_fuel_timestamp = $latest_prayer_fuel["post_date"] !== null ? strtotime( $latest_prayer_fuel["post_date"] ) : null;

        if ( !$latest_prayer_fuel_timestamp || $latest_prayer_fuel_timestamp < $start_posting_from ) {
            return $this->mysql_date( $start_posting_from );
        } else {
            return $latest_prayer_fuel["post_date"];
        }
    }

    /**
     * Return the campaign day of thetest prayer fuel post
     *
     * @param string $lang
     *
     * @return int
     */
    public function find_latest_prayer_fuel_day( $lang = null ) {
        $latest_prayer_fuel = $this->find_latest_prayer_fuel_data( $lang );

        if ( $latest_prayer_fuel["day"] === null ) {
            return 0;
        }

        return $latest_prayer_fuel["day"];
    }

    /**
     * Return the day and date of the latest prayer fuel
     *
     * @param string $lang If given will give the day and date of the latest prayer fuel in that language
     *
     * @return array
     */
    private function find_latest_prayer_fuel_data( $lang = null ) {
        global $wpdb;

        $query = "
            SELECT
                MAX( post_date ) as post_date,
                MAX( post_date_gmt ) as post_date_gmt,
                MAX( CAST( pm2.meta_value AS unsigned )  ) as day
            FROM
                $wpdb->postmeta AS pm
            JOIN
                $wpdb->posts AS p
            ON
                pm.post_id = p.ID
            JOIN
                $wpdb->postmeta AS pm2
            ON
                pm2.post_id = p.ID
            WHERE
               pm2.meta_key = 'day'
            AND
                p.post_type = %s
            AND
                (   p.post_status = 'publish'
                OR
                    p.post_status = 'draft'
                OR
                    p.post_status = 'future'
                )
            ";
        $args = [ PORCH_LANDING_POST_TYPE ];

        if ( $lang ) {
            $query .= "
            AND
                pm.meta_key = 'post_language'
            AND
                pm.meta_value = %s
            ";
            $args[] = $lang;
        }

        /* get the latest prayer fuel with the imported meta tag */
        $latest_prayer_fuel = $wpdb->get_row( $wpdb->prepare( $query, $args ), ARRAY_A );

        return $latest_prayer_fuel;
    }

    /**
     * Get the mysql date string from a timestamp
     */
    public function mysql_date( int $timestamp ) {
        return gmdate( 'Y-m-d H:i:s', $timestamp );
    }

    /**
     * get the date string in a 10 June 22 format
     *
     * @param int $timestamp
     *
     * @return string
     */
    public function day_month_year( int $timestamp ) {
        return gmdate( 'd M Y', $timestamp );
    }

    /**
     * Get the next day in unix time from the given date
     *
     * @param string $date
     *
     * @return int
     */
    public function next_day_timestamp( string $date ) {
        return strtotime( $date . ' +1 day' );
    }
}
DT_Campaign_Prayer_Post_Importer::instance();
