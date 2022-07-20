<?php

class DT_Campaign_Prayer_Fuel_Day_List extends WP_List_Table {

    private $languages_manager;

    public function __construct() {
        $this->languages_manager = new DT_Campaign_Languages();
        parent::__construct();
    }

    public function prepare_items() {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $per_page = 50;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;
        $max_days = 1000;

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $days = [];
        $campaign_length = DT_Campaign_Settings::campaign_length();
        for ( $i = $offset; $i < $offset + $per_page; $i++ ) {
            if ( $campaign_length > 0 && $i > $campaign_length - 1 ) {
                continue;
            }
            $days[] = $i + 1;
        }
        $days_string = implode( ', ', $days );

        global $wpdb;
        $query = "
            SELECT ID, CAST( meta_value as unsigned ) as day FROM $wpdb->posts p
            JOIN $wpdb->postmeta pm
            ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status IN ( 'draft', 'publish', 'future' )
            AND pm.meta_key = 'day'
            AND pm.meta_value IN ( %1s )
        ";
        $args = [ PORCH_LANDING_POST_TYPE, $days_string ];

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $query .= "
                ORDER BY %2s %3s
            ";
            $args[] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
            $args[] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
        }

        /* Get the prayer fuel posts with their language and day meta tags included */
        //phpcs:ignore
        $posts = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );

        $posts_sorted_by_campaign_day = [];

        foreach ( $posts as $post ) {
            $day = intval( $post["day"] );

            if ( !isset( $posts_sorted_by_campaign_day[$day] ) ) {
                $posts_sorted_by_campaign_day[$day] = [];
            }

            $posts_sorted_by_campaign_day[$day][] = $post;
        }

        /* flesh out the posts array to include empty days */
        foreach ( $days as $day ) {
            if ( !isset( $posts_sorted_by_campaign_day[$day] ) ) {
                $posts_sorted_by_campaign_day[$day] = [
                    [ "ID" => null, "day" => $day ],
                ];
            }
        }

        $sorted_posts = [];
        foreach ( $posts_sorted_by_campaign_day as $day => $days_posts ) {
            $sorted_posts[] = $days_posts;
        }


        $this->items = $sorted_posts;

        if ( $campaign_length > 0 ) {
            $total_days = $campaign_length;
        } else {
            $total_days = $max_days;
        }

        $this->set_pagination_args(
            array(
            'total_items' => $total_days,
            'per_page'    => $per_page,
            )
        );
    }

    public function get_sortable_columns() {
        return [
            "date" => array( "post_date", "asc" ),
            "day" => array( "day", "asc" ),
        ];
    }

    public function get_columns() {
        return [
            "cb" => "cb",
            "day" => "Campaign Day",
            "date" => "Date",
            "url" => "URL",
            "language" => "Languages",
        ];
    }

    public function column_cb( $items ) {
        $day = $items[0]["day"];

        ?>

        <input id="cb-select-<?php echo esc_html( $day ) ?>" type="checkbox" name="day[]" value="<?php echo esc_html( $day ) ?>" />

        <?php
    }

    public function column_default( $items, $column_name ) {
        $day = $items[0]["day"];

        if ( $items[0]["ID"] === null ) {
            unset( $items[0] );
        }

        switch ( $column_name ) {
            case 'day':
                echo esc_html( "Day " . $day );
                break;
            case 'date':
                $date = DT_Campaign_Settings::date_of_campaign_day( intval( $day ) );
                $date = gmdate( "Y/m/d", strtotime( $date ) );
                echo esc_html( $date );
                break;
            case 'language':
                $languages = $this->languages_manager->get_enabled_languages();

                $translated_languages = [];
                // TODO: at the moment this assumes one post per language per day, but what if there are 2 posts?
                // * one thought is to have 2 flags show up for them if that is the case.
                // * If there were only one way to add posts then you could forcibly restrict them to one post per day,
                // * Or maybe if there are posts which are set for a particular date, then they don't show up in this list
                foreach ( $items as $post ) {
                    $translated_languages[get_post_meta( $post["ID"], 'post_language', true )] = $post["ID"];
                }

                foreach ( $languages as $code => $language ) {
                    $button_on = in_array( $code, array_keys( $translated_languages ), true );
                    $id = $translated_languages[$code] ?? null;
                    $link = $button_on ? "post.php?post=$id&action=edit" : 'post-new.php?post_type=' . PORCH_LANDING_POST_TYPE . "&post_language=$code&day=$day";
                    ?>

                    <a
                        class="button language-button <?php echo $button_on ? '' : 'no-language' ?>"
                        href="<?php echo esc_html( $link ) ?>"
                        title="<?php echo esc_html( $language["native_name"] ) ?>"
                    >

                        <?php echo esc_html( $language["flag"] ); ?>

                    </a>

                    <?php
                }
                break;
            case 'url':
                if ( empty( $items ) ) {
                    break;
                }
                DT_Campaign_Prayer_Fuel_Post_Type::instance()->custom_column( $column_name, $items[0]["ID"] );
                break;
            default:
                if ( empty( $items ) ) {
                    break;
                }
                DT_Campaign_Prayer_Fuel_Post_Type::instance()->custom_column( $column_name, $items[0]["ID"] );
                break;
        }

    }

    public function get_bulk_actions() {
        return [
            "delete" => "Delete",
        ];
    }

    public function get_views() {
        $post_counts = wp_count_posts( PORCH_LANDING_POST_TYPE );

        $total = intval( $post_counts->future ) + intval( $post_counts->draft ) + intval( $post_counts->publish );

        return [
            "all" => "<a href='admin.php?page=dt_prayer_fuel'>All ($total)</a>",
            "draft" => "<a href='admin.php?page=dt_prayer_fuel&post_status=draft'>Draft ($post_counts->draft)</a>",
            "scheduled" => "<a href='admin.php?page=dt_prayer_fuel&post_status=future'>Scheduled ($post_counts->future)</a>",
        ];
    }
}
