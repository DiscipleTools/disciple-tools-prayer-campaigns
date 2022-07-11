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

        if ( $this->append_date ) {
            $start_date = $this->append_date;
        } else {
            $start_date = $this->find_latest_prayer_fuel_date();
        }

        $start_date = $this->mysql_date( strtotime( $start_date ) );

        foreach ( $new_posts as $i => $post ) {
            $post_exists = post_exists( $post["post_title"], "", $post["post_date"] );
            if ( $post_exists && get_post_type( $post_exists ) == $post["post_type"] ) {
                $post["post_title"] = $post["post_title"] . ' - ' . $this->day_month_year( strtotime( $start_date ) );
            }

            $post["post_date"] = $start_date;
            $post["post_date_gmt"] = $start_date;

            $post["postmeta"][] = [
                "key" => $this->import_meta_key,
                "value" => $this->import_meta_value,
            ];

            $campaign_day = DT_Campaign_Settings::what_day_in_campaign( $start_date );
            $is_meta_day_set = false;
            foreach ( $post["postmeta"] as $i => $meta ) {
                if ( $meta["key"] === "day" ) {
                    $post["postmeta"][$i]["value"] = $campaign_day;
                    $is_meta_day_set = true;
                }
            }

            if ( !$is_meta_day_set ) {
                $post["postmeta"][] = [
                    "key" => "day",
                    "value" => $campaign_day,
                ];
            }

            $new_posts[$i] = $post;

            $start_date = $this->mysql_date( $this->next_day_timestamp( $start_date ) );
        }

        /*
            If the posts coming in have the day meta tag, then at least we can connect translated
            posts together with that

            How does doing translations of posts work with this.

            Try installing one of the post translator plugins and see how that deals with linking posts
            and what happens when you export those  .
        */
        $this->append_date = null;

        return $new_posts;
    }

    /**
     * Return the date of the latest prayer fuel post that was imported in mysql format
     *
     * @return string
     */
    public function find_latest_prayer_fuel_date() {
        global $wpdb;

        /* get the latest prayer fuel with the imported meta tag */
        $latest_prayer_fuel = $wpdb->get_row( $wpdb->prepare( "
            SELECT
                MAX( post_date ) as post_date,
                MAX( post_date_gmt ) as post_date_gmt
            FROM
                $wpdb->postmeta AS pm
            JOIN
                $wpdb->posts AS p
            ON
                pm.post_id = p.ID
            WHERE
                pm.meta_key = %s
            AND
                pm.meta_value = %s
            AND
                p.post_type = %s
            AND
                ( p.post_status = 'publish' OR p.post_status = 'draft' )

        ", [ $this->import_meta_key, $this->import_meta_value, PORCH_LANDING_POST_TYPE ] ), ARRAY_A );

        $today_timestamp = date_timestamp_get( new DateTime() );
        $campaign = DT_Campaign_Settings::get_campaign();
        $campaign_start = $campaign["start_date"]["timestamp"];

        $start_posting_from = max( $campaign_start, $today_timestamp );

        $latest_prayer_fuel_timestamp = $latest_prayer_fuel["post_date"] !== null ? strtotime( $latest_prayer_fuel["post_date"] ) : null;

        if ( !$latest_prayer_fuel_timestamp || $latest_prayer_fuel_timestamp < $start_posting_from ) {
            return $this->mysql_date( $start_posting_from );
        } else {
            return $this->mysql_date( $this->next_day_timestamp( $latest_prayer_fuel["post_date"] ) );
        }
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
