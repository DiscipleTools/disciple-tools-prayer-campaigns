<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Provides functionality to import the prayer posts into the DB.
 * Adjusting titles, dates, meta etc. during the import.
 */
class DT_Campaign_Prayer_Post_Importer {

    public $import_meta_key = 'import_source';
    public $import_meta_value = 'disciple-tools-prayer-campaigns';
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

        $campaign = DT_Campaign_Landing_Settings::get_campaign();

        if ( empty( $campaign ) ) {
            return [];
        }

        $languages_manager = new DT_Campaign_Languages();
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $available_languages = $languages_manager->get_enabled_languages( $campaign['ID'] );
        $start_posting_from_date = $this->start_posting_from_date();
        $post_campaign_day = [];
        foreach ( $available_languages as $lang => $lang_details ) {
            $start_date = $this->append_date ? $this->append_date : $start_posting_from_date;
            $post_campaign_day[$lang] = max( 1, (int) DT_Campaign_Fuel::what_day_in_campaign( $this->calendar_date( $start_date ), $campaign['ID'] ) );
        }

        foreach ( $new_posts as $i => $post ) {
            $post_lang = $this->get_post_meta( $post['postmeta'], 'post_language' );
            if ( empty( $post_lang ) || !in_array( $post_lang, array_keys( $available_languages ), true ) ) {
                continue;
            }

            $post['post_date'] = $this->mysql_date( time() );
            $post['post_date_gmt'] = $this->mysql_date( time() );
            $post['post_status'] = 'publish';

            $campaign_day = $post_campaign_day[$post_lang];

            $post['postmeta'] = [
                [
                    'key' => 'post_language',
                    'value' => $post_lang,
                ],
                [
                    'key' => $this->import_meta_key,
                    'value' => $this->import_meta_value,
                ],
                [
                    'key' => 'day',
                    'value' => $campaign_day,
                ],
                [
                    'key' => CAMPAIGN_LANDING_META_KEY,
                    'value' => $campaign_day,
                ]
            ];

            $new_posts[$i] = $post;

            $post_campaign_day[$post_lang]++;
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
            if ( $meta['key'] === $key ) {
                return $meta['value'];
            }
        }
        return '';
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
        $postmeta = $post['postmeta'];
        foreach ( $postmeta as $j => $meta ) {
            if ( $meta['key'] === $key ) {
                    $postmeta[$j]['value'] = $value;
                    $is_meta_set = true;
            }
        }

        if ( !$is_meta_set ) {
            $postmeta[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        $post['postmeta'] = $postmeta;
    }

    /**
     * Return the date (Y-m-d, in the campaign's timezone) of the latest prayer fuel post
     *
     * @param string $lang
     *
     * @return string|null
     */
    public function find_latest_prayer_fuel_date( $lang = null ) {
        $latest_prayer_fuel_day = $this->find_latest_prayer_fuel_data( $lang );
        if ( $latest_prayer_fuel_day === null ) {
            return null;
        }

        return DT_Campaign_Fuel::formatted_date_of_campaign_day( $latest_prayer_fuel_day, 'Y-m-d' );
    }

    /**
     * Return the date (Y-m-d, in the campaign's timezone) that newly imported prayer fuel should start from:
     * the day after the latest prayer fuel, but no earlier than today or the start of the campaign.
     *
     * @param string $lang
     *
     * @return string
     */
    public function start_posting_from_date( $lang = null ) {
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $today = DT_Campaign_Fuel::today_in_campaign_timezone( $campaign );
        $campaign_start = $campaign['start_date']['formatted'] ?? $today;

        $start_posting_from = max( $campaign_start, $today );

        $latest_prayer_fuel_day = $this->find_latest_prayer_fuel_data( $lang );
        if ( $latest_prayer_fuel_day !== null ) {
            $start_posting_from = max( $start_posting_from, $this->first_date_of_campaign_day( $latest_prayer_fuel_day + 1, $campaign ) );
        }

        return $start_posting_from;
    }

    /**
     * Return the first date (Y-m-d) that DT_Campaign_Fuel::what_day_in_campaign() maps to the given day
     *
     * @param int $day
     * @param array $campaign
     *
     * @return string
     */
    private function first_date_of_campaign_day( int $day, array $campaign ) {
        $frequency = $campaign['prayer_fuel_frequency']['key'] ?? 'daily';
        $interval_days = [ 'weekly' => 7, 'monthly' => 30 ][ $frequency ] ?? 1;

        $date = new DateTimeImmutable( $campaign['start_date']['formatted'], new DateTimeZone( 'UTC' ) );
        return $date->modify( '+' . ( ( max( 1, $day ) - 1 ) * $interval_days ) . ' days' )->format( 'Y-m-d' );
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
        $campaign = DT_Campaign_Landing_Settings::get_campaign();
        $args = [];

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

        if ( isset( $campaign['ID'] ) ) {
            $query .= "JOIN $wpdb->postmeta AS pm3
                ON ( pm3.post_id = p.ID AND pm3.meta_key = 'linked_campaign' AND pm3.meta_value = %d )
            ";
            $args[] = $campaign['ID'];
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
        $args[] = CAMPAIGN_LANDING_POST_TYPE;

        if ( $lang ) {
            $query .= "
            AND
                pm2.meta_key = 'post_language'
            AND
                pm2.meta_value = %s
            ";
            $args[] = $lang;
        }

        $query .= '
            ORDER BY CAST( pm.meta_value as signed ) DESC
            LIMIT 1
        ';

        /* get the latest prayer fuel with the imported meta tag */
        //phpcs:ignore
        $latest_prayer_fuel = $wpdb->get_row( $wpdb->prepare( $query, $args ), ARRAY_A );

        if ( $latest_prayer_fuel === null || $latest_prayer_fuel['day'] === null ) {
            return null;
        }

        return intval( $latest_prayer_fuel['day'] );
    }

    /**
     * Get the mysql date string from a timestamp
     */
    public function mysql_date( int $timestamp ) {
        return gmdate( 'Y-m-d H:i:s', $timestamp );
    }

    /**
     * Normalise a date string to a Y-m-d calendar date without shifting it between timezones
     *
     * @param string $date
     *
     * @return string
     */
    private function calendar_date( string $date ) {
        $parsed = date_create( $date, new DateTimeZone( 'UTC' ) );
        return $parsed ? $parsed->format( 'Y-m-d' ) : $date;
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
