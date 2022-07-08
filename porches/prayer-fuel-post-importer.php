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
     * Import the prayer posts, scheduling them to start at a particular date
     */
    public function dt_import_prayer_posts( $posts ) {
        // Copy the post. Recommended
        $counter = 1;

        $new_posts = $posts;

        foreach ( $new_posts as $i => $post ) {
            $post_exists = post_exists( $post["post_title"], "", $post["post_date"] );
            if ( $post_exists && get_post_type( $post_exists ) == $post["post_type"] ) {
                $new_posts[$i]["post_title"] = $post["post_title"] . ' ' . $counter;
            }

            $new_posts[$i]["postmeta"][] = [
                "key" => $this->import_meta_key,
                "value" => $this->import_meta_value,
            ];

            /*
            If the meta-key has got the date in it, then it's going to look wierd having the date be different to the
            actual date in the URL. But I'm not sure there is much that can be done about it in the code without parsing
            everything to catch those kinds of things. So best thing is to tell people not to name their posts certain dates
            */

            $counter++;
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
        return $new_posts;
    }

    public static function dt_find_latest_prayer_fuel_date() {
        /* get the latest prayer fuel with the imported meta tag */
        $latest_prayer_fuel = null;

        if ( !$latest_prayer_fuel ) {
            $today_timestamp = date_timestamp_get( new DateTime() );
            $campaign = DT_Campaign_Settings::get_campaign();
            $campaign_start = $campaign["start_date"]["timestamp"];

            if ( $today_timestamp > $campaign_start ) {
                return $today_timestamp;
            } else {
                return $campaign_start;
            }
        } else {
            /* else return the date of the next day after it */
            return "";
        }
    }
}
DT_Campaign_Prayer_Post_Importer::instance();
