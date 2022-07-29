<?php
$lang = dt_campaign_get_current_lang();
//  for getting posts in the selected language.
$lang_query = [
    [
        'key'     => 'post_language',
        'value'   => $lang,
        'compare' => '=',
    ]
];
if ( $lang === "en_US" ){
    $lang_query[] = [
        'key'     => 'post_language',
        'compare' => 'NOT EXISTS',
    ];
    $lang_query["relation"] = "OR";
}

$todays_day_in_campaign = DT_Campaign_Settings::what_day_in_campaign( gmdate( 'Y/m/d' ) );

$meta_query = [
   "relation" => "AND",
    [
        "key" => "day",
        "value" => $todays_day_in_campaign,
        "compare" => "<=",
        "type" => "numeric",
    ],
    $lang_query,
];
$list = new WP_Query( [
    'post_type' => PORCH_LANDING_POST_TYPE,
    'post_status' => [ 'publish' ],
    'posts_per_page' => -1,
    'meta_key' => 'day',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'meta_query' => $meta_query
] );

if ( empty( $list->posts ) ){
    $args = array(
        'post_type' => PORCH_LANDING_POST_TYPE,
        'post_status' => [ 'publish' ],
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'meta_query' => [
            "relation" => "AND",
            [
                "key" => "day",
                "value" => $todays_day_in_campaign,
                "compare" => "<=",
                "type" => "numeric",
            ],
            [
                'relation' => 'OR',
                [
                    'key'     => 'post_language',
                    'value'   => 'en_US',
                    'compare' => '=',
                ],
                [
                    'key'     => 'post_language',
                    'compare' => 'NOT EXISTS',
                ],
            ]
        ]
    );
    $list = new WP_Query( $args );
}
?>

<!-- LIST OF POSTS Section -->
<section id="blog" class="section">
    <!-- Container Starts -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'all_fuel_title' ) ) ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_description' ) )  ?></p>
        </div>
        <div class="row">
            <?php $days_displayed = [] ?>
            <?php foreach ( $list->posts as $item ) :

                $campaign_day = get_post_meta( $item->ID, "day", true );
                $date = DT_Campaign_Settings::date_of_campaign_day( $campaign_day );

                if ( in_array( $campaign_day, $days_displayed ) || ( isset( $todays_campaign_day ) && $campaign_day === $todays_campaign_day ) ) {
                    continue;
                }

                $days_displayed[] = $campaign_day;
                $url = site_url( "/prayer/fuel/$campaign_day" );
                ?>

            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12 blog-item">
                <!-- Blog Item Starts -->
                <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
                    <div class="blog-item-img">
                        <a href="<?php echo esc_url( $url ) ?>">
                            <img src="<?php echo esc_url( trailingslashit( plugin_dir_url( __DIR__ ) ) ) ?>landing-pages/img/300x1.png" alt="">
                        </a>
                    </div>
                    <div class="blog-item-text">
                        <h3>
                            <a href="<?php echo esc_url( $url ) ?>"><?php echo esc_html( $item->post_title ) ?></a>
                        </h3>
                        <div class="meta-tags">
                            <span class="date"><i class="lnr lnr-calendar-full"></i>on <?php echo esc_html( gmdate( 'Y-m-d', strtotime( $date ) ) )  ?></span>
                        </div>
                        <p>
                            <?php echo wp_kses_post( esc_html( $item->post_excerpt ) ) ?>
                        </p>
                        <a href="<?php echo esc_url( $url ) ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'Read', 'disciple-tools-prayer-campaigns' ); ?></a>
                    </div>
                </div>
                <!-- Blog Item Wrapper Ends-->
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<!-- blog Section End -->
