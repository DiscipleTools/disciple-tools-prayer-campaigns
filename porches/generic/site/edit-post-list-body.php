<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$landing_post_type = CAMPAIGN_LANDING_POST_TYPE;

if ( !current_user_can( 'edit_' . $landing_post_type ) ) { exit; }

$campaign = DT_Campaign_Landing_Settings::get_campaign();

$lang = dt_campaign_get_current_lang();

$formatter = new IntlDateFormatter(
    $lang,  // the locale to use, e.g. 'en_GB'
    IntlDateFormatter::LONG, // how the date should be formatted, e.g. IntlDateFormatter::FULL
    IntlDateFormatter::NONE,  // how the time should be formatted, e.g. IntlDateFormatter::FULL
);

$days = [];
$campaign_length = DT_Campaign_Fuel::total_days_in_campaign();
$languages = DT_Campaign_Languages::get_enabled_languages( $campaign['ID'] );

for ( $i = 0; $i < 50; $i++ ) {
    if ( $campaign_length > 0 && $i > $campaign_length - 1 ) {
        continue;
    }
    $days[] = $i + 1;
}

$days_string = implode( ', ', $days );

global $wpdb;
$query = "
    SELECT ID, post_title, CAST( pm.meta_value as unsigned ) as day FROM $wpdb->posts p
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
$list = $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A );

foreach ( $list as $prayer_fuel_post ) {
    $day = intval( $prayer_fuel_post['day'] );

    if ( !isset( $posts_sorted_by_campaign_day[$day] ) ) {
        $posts_sorted_by_campaign_day[$day] = [];
    }

    $posts_sorted_by_campaign_day[$day][] = $prayer_fuel_post;
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

?>
<!-- LIST OF ALL PRAYER FUEL IN ALL LANGUAGES -->
<section id="blog" class="section">
    <!-- Container Starts -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'scheduled_prayer_fuel_title' ) ) ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'scheduled_prayer_fuel_description' ) )  ?></p>
        </div>
        <div class="row">
            <?php
            $count = count( $sorted_posts );

            for ( $i = 0; $i < $count; $i++ ) {
                $day = $i + 1;
                $days_posts = $sorted_posts[$i];
                $campaign_day = get_post_meta( $days_posts[0]['ID'], 'day', true );
                if ( !$campaign_day ) {
                    $campaign_day = $day;
                }
                $date = DT_Campaign_Fuel::date_of_campaign_day( $campaign_day );

                $translated_languages = $languages;
                ?>

                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12 blog-item">
                    <!-- Blog Item Starts -->
                    <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
                        <div class="blog-item-text">
                            <h3>
                                <?php echo esc_html( sprintf( __( 'Day %s', 'disciple-tools-prayer-campaigns' ), $campaign_day ) )?>
                            </h3>
                            <div class="meta-tags">
                                <span class="date"><i class="lnr lnr-calendar-full"></i><?php echo esc_html( $formatter->format( strtotime( $date ) ) )  ?></span>
                            </div>


                            <?php
                            foreach ( $days_posts as $day_post ) {
                                $post_obj = get_post( $day_post['ID'] );
                                $lang = get_post_meta( $day_post['ID'], 'post_language', true );
                                if ( isset( $lang ) ) {
                                    unset( $translated_languages[$lang] );
                                }
                                $day_post_id = $day_post['ID'];
                                $url = site_url() . "/wp-admin/post.php?post=$day_post_id&action=edit&campaign=" . $campaign['ID'];
                                ?>
                                <?php if ( $day_post['ID'] ) { ?>
                                    <h4 class="edit-post-titles">
                                        <?php echo esc_html( $day_post['post_title'] ) ?> -
                                        <?php echo esc_html( $languages[$lang]['flag'] ) ?>

                                        <a href="<?php echo esc_url( $url ) ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'Edit', 'disciple-tools-prayer-campaigns' ); ?></a>
                                    </h4>
                                <?php } ?>
                            <?php } ?>
                            <?php
                            foreach ( $translated_languages as $lang => $language ) {
                                // $url = add_query_arg( [ 'lang' => $lang ], $url );
                                $url = site_url() . "/wp-admin/post-new.php?post_type=landing&post_language=$lang&day=$day&campaign=" . $campaign['ID'];
                                ?>
                                    <h4 class="edit-post-titles">
                                        <?php echo esc_html( $language['flag'] ) ?>

                                        <a href="<?php echo esc_url( $url ) ?>" class="">
                                            <span class="plus-icon">+</span>
                                        </a>
                                    </>
                            <?php } ?>
                        </div>
                    </div>
                    <!-- Blog Item Wrapper Ends-->
                </div>
            <?php } ?>
        </div>
    </div>
 </section>
<!-- blog Section End -->
