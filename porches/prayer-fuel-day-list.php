<?php

class DT_Campaign_Prayer_Fuel_Day_List extends WP_List_Table {
    public function prepare_items() {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $this->_column_headers = array($columns, $hidden, $sortable);

        $args = array(
                'numberposts' => $per_page,
                'offset'      => $offset,
        );

        if( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $args['orderby'] = $_REQUEST['orderby'];
            $args['order'] = $_REQUEST['order'];
        }

        $args["post_type"] = PORCH_LANDING_POST_TYPE;
        $args["post_status"] = array( "draft", "publish", "future" );

        /* Get the prayer fuel posts with their language and day meta tags included */
        $this->items = get_posts( $args );
/*
        $posts_sorted_by_campaign_day = [];

        foreach ( $posts as $post ) {
            $day = get_post_meta( $post->ID, "day", true );

            if ( !isset( $posts_sorted_by_campaign_day[$day] ) ) {
                $posts_sorted_by_campaign_day[$day] = [];
            }

            $posts_sorted_by_campaign_day[$day][] = $post;
        }
        $this->items = $posts_sorted_by_campaign_day;
 */


        $this->set_pagination_args(
            array(
            'total_items' => count( $this->items ),
            'per_page'    => $per_page,
            )
        );
    }

    public function get_sortable_columns() {
        return [
            "day" => array( "orderby", false ),
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

    public function column_cb( $item ) {
        $day = 1; // $item["day"];

        ?>

        <input id="cb-select-<?php echo esc_html( $day ) ?>" type="checkbox" name="day[]" value="<?php echo esc_html( $day ) ?>" />

        <?php
    }

    public function column_default( $item, $column_name ) {
        $day = get_post_meta( $item->ID, "day", true );

        switch ( $column_name ) {
            case 'day':

                echo "Day " . $day;
                break;
            case 'date':
                $date = DT_Campaign_Settings::date_of_campaign_day( intval( $day ) );
                $date = gmdate( "Y/m/d", strtotime( $date ) );
                echo esc_html( $date );
                break;
            default:
                DT_Campaign_Prayer_Fuel_Post_Type::instance()->custom_column( $column_name, $item->ID );
                break;
        }

    }
}
