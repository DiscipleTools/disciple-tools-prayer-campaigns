<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class DT_Campaign_Prayer_Fuel_Day_List extends WP_List_Table {

    public function __construct() {
        parent::__construct();
    }

    public function prepare_items() {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $per_page = 50;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;
        $max_days = 10000;

        $campaign = DT_Campaign_Landing_Settings::get_campaign();

        $this->_column_headers = array( $columns, $hidden, $sortable );

        $days = [];
        $campaign_length = DT_Campaign_Fuel::total_days_in_campaign();
        for ( $i = $offset; $i < $offset + $per_page; $i++ ) {
            if ( $campaign_length > 0 && $i > $campaign_length - 1 ) {
                continue;
            }
            $days[] = $i + 1;
        }
        $days_string = implode( ', ', $days );

        global $wpdb;
        $query = "
            SELECT ID, post_title, CAST( pm.meta_value as unsigned ) as day, post_status, post_date
            FROM $wpdb->posts p
            JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id AND pm.meta_key = 'day' )
            JOIN $wpdb->postmeta pm2 ON ( p.ID = pm2.post_id AND pm2.meta_key = 'linked_campaign' AND pm2.meta_value = %d)
            WHERE p.post_type = %s
            AND p.post_status IN ( 'draft', 'publish', 'future' )
            AND pm.meta_value IN ( %1s )
        ";
        $args = [ $campaign['ID'], CAMPAIGN_LANDING_POST_TYPE, $days_string ];

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
            $query .= '
                ORDER BY %2s %3s
            ';
            $args[] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
            $args[] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
        }

        /* Get the prayer fuel posts with their language and day meta tags included */
        //phpcs:ignore
        $posts = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );

        $posts_sorted_by_campaign_day = [];

        foreach ( $posts as $post ) {
            $day = intval( $post['day'] );

            if ( !isset( $posts_sorted_by_campaign_day[$day] ) ) {
                $posts_sorted_by_campaign_day[$day] = [];
            }

            $posts_sorted_by_campaign_day[$day][] = $post;
        }

        /* flesh out the posts array to include empty days */
        foreach ( $days as $day ) {
            if ( !isset( $posts_sorted_by_campaign_day[$day] ) ) {
                $posts_sorted_by_campaign_day[$day] = [
                    [ 'ID' => null, 'day' => $day, 'campaign' => $campaign['ID'] ],
                ];
            }
        }
        ksort( $posts_sorted_by_campaign_day );

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
            'date' => array( 'post_date', 'asc' ),
            'day' => array( 'day', 'asc' ),
        ];
    }

    public function get_columns() {
        return [
            /* "cb" => "cb", */
            'day' => 'Campaign Day',
            'date' => 'Date',
            'titles' => 'Titles',
            'language' => 'Create and Edit by Language',
            'url' => 'URL',
        ];
    }

    public function column_cb( $items ) {
        $day = $items[0]['day'];

        ?>

        <input id="cb-select-<?php echo esc_html( $day ) ?>" type="checkbox" name="day[]" value="<?php echo esc_html( $day ) ?>" />

        <?php
    }

    public function column_default( $items, $column_name ) {
        $day = $items[0]['day'];

        if ( $items[0]['ID'] === null ) {
            unset( $items[0] );
        }


        switch ( $column_name ) {
            case 'day':
                //$todays_campaign_day = DT_Campaign_Fuel::what_day_in_campaign( gmdate( 'Y-m-d' ) );
                $porch_fields = DT_Porch_Settings::settings();
                $frequency = $porch_fields['prayer_fuel_frequency']['value'];
                if ( empty( $frequency ) ) {
                    $frequency = 'daily';
                }
                $frequency_options = [
                    'daily' => 'Day',
                    'weekly' => 'Week',
                    'monthly' => 'Month',
                ]
                ?>

                <span class="row-title">

                    <?php echo esc_html( $frequency_options[$frequency] ) ?> <?php echo esc_html( $day ) ?>

<!--                    --><?php //if ( $day > $todays_campaign_day ): ?>
<!---->
<!--                     — <span class="post-status">Scheduled</span>-->
<!---->
<!--                    --><?php //endif; ?>

                </span>

                <?php
                break;
            case 'date':
                $date = DT_Campaign_Fuel::date_of_campaign_day( intval( $day ) );
                $date = gmdate( 'Y/m/d', strtotime( $date ) );
                echo esc_html( $date );
                break;
            case 'titles':
                $titles = implode( ', ', array_map( function( $item ) {
                    return $item['post_title'];
                }, $items ) );

                ?>

                <p class="post-titles" title="<?php echo esc_html( $titles ) ?>"><?php echo esc_html( $titles ) ?></p>

                <?php
                break;
            case 'language':
                $campaign = DT_Campaign_Landing_Settings::get_campaign();
                $languages = DT_Campaign_Languages::get_enabled_languages( $campaign['ID'] );

                $translated_languages = [];
                foreach ( $items as $post ) {
                    $lang = get_post_meta( $post['ID'], 'post_language', true );
                    if ( !isset( $translated_languages[$lang] ) ) {
                        $translated_languages[$lang] = [];
                    }

                    $translated_languages[$lang][] = $post;
                }


                foreach ( $languages as $code => $language ) {
                    $button_on = in_array( $code, array_keys( $translated_languages ), true );
                    $posts_in_language = $translated_languages[$code] ?? [];

                    $add_link = 'post-new.php?post_type=' . CAMPAIGN_LANDING_POST_TYPE . "&day=$day";


                    if ( count( $posts_in_language ) === 0 ) {
                        $link = $add_link . "&post_language=$code";
                        $link .= '&campaign=' . $campaign['ID'];
                    } else if ( count( $posts_in_language ) === 1 ) {
                        $id = $posts_in_language[0]['ID'];
                        $link = "post.php?post=$id&action=edit";
                        $link .= '&campaign=' . $campaign['ID'];
                    }
                    $date = DT_Campaign_Fuel::date_of_campaign_day( intval( $day ) );
                    ?>

                    <?php if ( count( $posts_in_language ) < 2 ): ?>

                        <a
                            class="button language-button <?php echo $button_on ? '' : 'no-language' ?> "
                            href="<?php echo esc_attr( $link ) ?>"
                            title="<?php echo esc_attr( $language['native_name'] ) ?>"
                        >
                            <?php if ( isset( $posts_in_language[0]['post_status'] ) && $posts_in_language[0]['post_status'] === 'draft' ): ?>
                                <span class="prayer-fuel-draft-status" title="Draft Post">!</span>
                            <?php elseif ( isset( $posts_in_language[0]['post_date'] ) && strtotime( $posts_in_language[0]['post_date'] ) > strtotime( $date ) ): ?>
                                <span class="prayer-fuel-draft-status" title="Incorrect Publish Date" style="background-color: red">!</span>
                            <?php endif;?>

                            <?php echo esc_html( $language['flag'] ); ?>

                        </a>

                    <?php else : ?>

                        <span
                            class="button language-button dropdown-button"
                            title="<?php echo esc_attr( $language['native_name'] ) ?>"
                        >

                            <?php echo esc_html( $language['flag'] ); ?>

                            <span class="badge-count"><?php echo count( $posts_in_language ) ?></span>

                            <div class="dropdown">

                                <?php foreach ( $posts_in_language as $post ): ?>

                                    <a class="dropdown__link" href="post.php?post=<?php echo esc_attr( $post['ID'] ) ?>&action=edit"><?php echo esc_html( $post['post_title'] ) ?></a>

                                <?php endforeach; ?>
                            </div>
                        </span>


                    <?php endif; ?>

                    <?php
                }
                ?>

                <a class="button language-button" href="<?php echo esc_attr( $add_link ) ?>" title="Add new post">
                    <span class="plus-icon"></span>
                </a>

                <?php
                break;
            case 'url':
                if ( empty( $items ) ) {
                    break;
                }
                $campaign_url = DT_Campaign_Landing_Settings::get_landing_page_url();
                $url = trailingslashit( $campaign_url ) . CAMPAIGN_LANDING_TYPE . '/' . $day;
                echo '<a href="' . esc_url( $url ) . '">'. esc_html( $url ) .'</a>';
                break;
            default:
                if ( empty( $items ) ) {
                    break;
                }
                DT_Campaign_Prayer_Fuel_Post_Type::instance()->custom_column( $column_name, $items[0]['ID'] );
                break;
        }
    }

    public function get_views() {
        $post_counts = wp_count_posts( CAMPAIGN_LANDING_POST_TYPE );

        $total = intval( $post_counts->future ) + intval( $post_counts->draft ) + intval( $post_counts->publish );

        return [
            'all' => "<a href='admin.php?page=dt_prayer_fuel'>All ($total)</a>",
            'draft' => "<a href='admin.php?page=dt_prayer_fuel&post_status=draft'>Draft ($post_counts->draft)</a>",
            'scheduled' => "<a href='admin.php?page=dt_prayer_fuel&post_status=future'>Scheduled ($post_counts->future)</a>",
        ];
    }
}
