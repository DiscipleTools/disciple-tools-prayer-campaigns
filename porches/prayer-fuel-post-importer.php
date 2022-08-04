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

        $language_settings = new DT_Campaign_Languages();
        $available_languages = $language_settings->get();
        $post_scheduled_start_date = [];

        $start_posting_from_date = $this->start_posting_from_date();
        foreach ( $available_languages as $lang => $lang_details ) {
            if ( $this->append_date ) {
                $post_scheduled_start_date[$lang] = $this->append_date;
            } else {
                $post_scheduled_start_date[$lang] = $start_posting_from_date;
            }
            $post_scheduled_start_date[$lang] = $this->mysql_date( strtotime( $post_scheduled_start_date[$lang] ) );
        }

        foreach ( $new_posts as $i => $post ) {
            $post_lang = $this->get_post_meta( $post["postmeta"], "post_language" );
            if ( empty( $post_lang ) || !in_array( $post_lang, array_keys( $available_languages ), true ) ) {
                continue;
            }

            $post_start_date = $post_scheduled_start_date[$post_lang];

            $post["post_date"] = $this->mysql_date( time() );
            $post["post_date_gmt"] = $this->mysql_date( time() );
            $post["post_status"] = "publish";

            $campaign_day = DT_Campaign_Settings::what_day_in_campaign( $post_start_date );

            $post["postmeta"] = [
                [
                    "key" => "post_language",
                    "value" => $post_lang,
                ],
                [
                    "key" => $this->import_meta_key,
                    "value" => $this->import_meta_value,
                ],
                [
                    "key" => "day",
                    "value" => $campaign_day,
                ],
                [
                    "key" => PORCH_LANDING_META_KEY,
                    "value" => $campaign_day,
                ]
            ];

            $new_posts[$i] = $post;

            $post_scheduled_start_date[$post_lang] = $this->mysql_date( $this->next_day_timestamp( $post_scheduled_start_date[$post_lang] ) );
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
    private function set_post_meta( &$post, $key, $value ) {
        $is_meta_set = false;
        $postmeta = $post["postmeta"];
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

        $post["postmeta"] = $postmeta;
    }

    /**
     * Return the date of the latest prayer fuel post that was imported in mysql format
     *
     * @param string $lang
     *
     * @return string
     */
    public function find_latest_prayer_fuel_date( $lang = null ) {
        $latest_prayer_fuel_day = $this->find_latest_prayer_fuel_data( $lang );
        $latest_prayer_fuel_date = DT_Campaign_Settings::date_of_campaign_day( $latest_prayer_fuel_day );

        return $latest_prayer_fuel_date;
    }

    public function start_posting_from_date( $lang = null ) {
        $latest_prayer_fuel_date = $this->find_latest_prayer_fuel_date( $lang );

        $today_timestamp = date_timestamp_get( new DateTime() );
        $campaign = DT_Campaign_Settings::get_campaign();
        $campaign_start = $campaign["start_date"]["timestamp"];

        $start_posting_from = max( $campaign_start, $today_timestamp );

        $latest_prayer_fuel_timestamp = $latest_prayer_fuel_date !== null ? strtotime( $latest_prayer_fuel_date ) : null;

        if ( !$latest_prayer_fuel_timestamp || $latest_prayer_fuel_timestamp < $start_posting_from ) {
            return $this->mysql_date( $start_posting_from );
        } else {
            return $this->mysql_date( $this->next_day_timestamp( $latest_prayer_fuel_date ) );
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
        return $this->find_latest_prayer_fuel_data( $lang );
    }

    /**
     * Return the day and date of the latest prayer fuel
     *
     * @param string $lang If given will give the day and date of the latest prayer fuel in that language
     *
     * @return int
     */
    private function find_latest_prayer_fuel_data( $lang = null ) {
        global $wpdb;

        $query = "
            SELECT
                pm.meta_value as day
            FROM
                $wpdb->postmeta AS pm
            JOIN
                $wpdb->posts AS p
            ON
                pm.post_id = p.ID
            ";

        if ( $lang ) {
            $query .= "JOIN
                    $wpdb->postmeta AS pm2
                ON
                    pm2.post_id = p.ID
                ";
        }

        $query .= "WHERE
               pm.meta_key = 'day'
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
                pm2.meta_key = 'post_language'
            AND
                pm2.meta_value = %s
            ";
            $args[] = $lang;
        }

        $query .= "
            ORDER BY CAST( pm.meta_value as signed ) DESC
            LIMIT 1
        ";

        /* get the latest prayer fuel with the imported meta tag */
        //phpcs:ignore
        $latest_prayer_fuel = $wpdb->get_row( $wpdb->prepare( $query, $args ), ARRAY_A );

        if ( $latest_prayer_fuel === null || $latest_prayer_fuel["day"] === null ) {
            return 1;
        }

        return intval( $latest_prayer_fuel["day"] );
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
