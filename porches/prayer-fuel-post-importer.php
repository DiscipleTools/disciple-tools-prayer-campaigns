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

            /*
            If the meta-key has got the date in it, then it's going to look wierd having the date be different to the
            actual date in the URL. But I'm not sure there is much that can be done about it in the code without parsing
            everything to catch those kinds of things. So best thing is to tell people not to name their posts certain dates
            */

            $new_posts[$i] = $post;

            $start_date = $this->mysql_date( $this->next_day_timestamp( $start_date ) );
        }

        /*
            So we can change the title, dates for publishing, day meta key
            absolutely anything that we want to do.

            So then the code part of it would be to get the latest post in the db
            and start adjusting these posts, publish dates to go be published after these ones.

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
     * Return the timestamp of the latest prayer fuel post that was imported
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
                `wp_postmeta` AS pm
            JOIN
                `wp_posts` AS p
            ON
                pm.post_id = p.ID
            WHERE
                meta_key = %s
            AND
                meta_value = %s
        ", [ $this->import_meta_key, $this->import_meta_value ] ), ARRAY_A );

        if ( !$latest_prayer_fuel["post_date"] || !$latest_prayer_fuel["post_date_gmt"] ) {
            $today_timestamp = date_timestamp_get( new DateTime() );
            $campaign = DT_Campaign_Settings::get_campaign();
            $campaign_start = $campaign["start_date"]["timestamp"];

            if ( $today_timestamp > $campaign_start ) {
                return $today_timestamp;
            } else {
                return $campaign_start;
            }
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
