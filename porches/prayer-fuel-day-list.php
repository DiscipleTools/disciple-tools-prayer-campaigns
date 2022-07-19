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

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $days = [];
        for ( $i = ( $current_page - 1 ) * $per_page; $i < $current_page * $per_page; $i++ ) {
            $days[] = $i + 1;
        }

        $args = array(
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        "key" => "day",
                        "value" => $days,
                        "compare" => "IN",
                    )
                ),
        );

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $args['orderby'] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
            $args['order'] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
        }

        $args["post_type"] = PORCH_LANDING_POST_TYPE;
        $args["post_status"] = array( "draft", "publish", "future" );

        // TODO: get the query args and get the posts based on the post_status given

        /* Get the prayer fuel posts with their language and day meta tags included */
        $posts = get_posts( $args );

        $posts_sorted_by_campaign_day = [];

        foreach ( $posts as $post ) {
            $day = intval( get_post_meta( $post->ID, "day", true ) );

            if ( !isset( $posts_sorted_by_campaign_day[$day] ) ) {
                $posts_sorted_by_campaign_day[$day] = [];
            }

            $posts_sorted_by_campaign_day[$day][] = $post;
        }

        $sorted_posts = [];
        foreach ( $posts_sorted_by_campaign_day as $day => $days_posts ) {
            $sorted_posts[] = $days_posts;
        }

        $this->items = $sorted_posts;

        /* Find the maximum day in the campaign posts */
        $last_imported_post_day = DT_Campaign_Prayer_Post_Importer::instance()->find_latest_prayer_fuel_day();

        $this->set_pagination_args(
            array(
            'total_items' => $last_imported_post_day,
            'per_page'    => $per_page,
            )
        );
    }

    public function get_sortable_columns() {
        return [
            "date" => array( "date", "asc" ),
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
        $a_post = $items[0];
        $day = get_post_meta( $a_post->ID, "day", true );

        ?>

        <input id="cb-select-<?php echo esc_html( $day ) ?>" type="checkbox" name="day[]" value="<?php echo esc_html( $day ) ?>" />

        <?php
    }

    public function column_default( $items, $column_name ) {
        $day = get_post_meta( $items[0]->ID, "day", true );

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
                    $translated_languages[get_post_meta( $post->ID, 'post_language', true )] = $post->ID;
                }

                foreach ( $languages as $code => $language ) {
                    $button_on = in_array( $code, array_keys( $translated_languages ), true );
                    $ID = $translated_languages[$code] ?? null;
                    $link = $button_on ? "post.php?post=$ID&action=edit" : 'post-new.php?post_type=' . PORCH_LANDING_POST_TYPE . "&post_language=$code&day=$day" ;
                    ?>

                    <a
                        class="button language-button <?php echo $button_on ? '' : 'no-language' ?>"
                        href="<?php echo esc_html( $link ) ?>"
                    >

                        <?php echo esc_html( $language["flag"] ); ?>

                    </a>

                    <?php
                }
                break;
            case 'url':
                DT_Campaign_Prayer_Fuel_Post_Type::instance()->custom_column( $column_name, $items[0]->ID );
                break;
            default:
                DT_Campaign_Prayer_Fuel_Post_Type::instance()->custom_column( $column_name, $items[0]->ID );
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
