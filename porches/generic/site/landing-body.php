<?php
$lang = dt_campaign_get_current_lang();
$porch_fields = DT_Porch_Settings::settings();
$content = 'No post found';
if ( isset( $this->parts['post_id'] ) && ! empty( $this->parts['post_id'] ) ) {
    $my_postid = $this->parts['post_id'];//This is page id or post id
    global $wpdb;
    $content_post_id = $wpdb->get_var( $wpdb->prepare( "
        SELECT p.ID
        FROM $wpdb->posts p
        JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_ID AND pm.meta_key = 'prayer_fuel_magic_key' AND pm.meta_value = %s )
        JOIN $wpdb->postmeta lang ON ( p.ID = lang.post_ID AND lang.meta_key = 'post_language' AND lang.meta_value = %s )
        WHERE post_type = %s
        AND ( post_status = 'publish' OR post_status = 'future' )
    ", $this->parts['public_key'], $lang, PORCH_LANDING_POST_TYPE ) );

    if ( empty( $content_post_id ) ){
        $content_post_id = $wpdb->get_var( $wpdb->prepare( "
            SELECT p.ID
            FROM $wpdb->posts p
            JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_ID AND pm.meta_key = 'prayer_fuel_magic_key' AND pm.meta_value = %s )
            LEFT JOIN $wpdb->postmeta lang ON ( p.ID = lang.post_ID AND lang.meta_key = 'post_language' )
            WHERE post_type = %s
            AND ( post_status = 'publish' OR post_status = 'future' )
            AND ( lang.meta_value = 'en_US' OR lang.meta_value IS NULL )
        ", $this->parts['public_key'], PORCH_LANDING_POST_TYPE ) );
    }

    if ( !empty( $content_post_id ) ){
        $my_postid = $content_post_id;
    }


    $post_status = get_post_status( $my_postid );
    $content_post = get_post( $my_postid );
    if ( !empty( $content_post ) ){
        $content = $content_post->post_content;
        $content = apply_filters( 'the_content', $content );
        $content = str_replace( ']]>', ']]&gt;', $content );
    }
}
// query for getting posts in the selected language.
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
$list = new WP_Query( [
    'post_type' => PORCH_LANDING_POST_TYPE,
    'post_status' => [ 'publish' ],
    'posts_per_page' => -1,
    'orderby' => 'post_date',
    'order' => 'DESC',
    'meta_query' => $lang_query
] );

if ( empty( $list->posts ) ){
    $args = array(
        'post_type' => PORCH_LANDING_POST_TYPE,
        'post_status' => [ 'publish' ],
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC',
        'meta_query' => [
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
    );
    $list = new WP_Query( $args );
}
?>


<!-- Contact Section Start -->
<section id="contact" class="section">
    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col-md-12 wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                <div class="fuel-block">
                    <div class="section-header">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Prayer Fuel', 'dt-campaign-generic-porch' ); ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>
                    <div class="">
                        <?php echo wp_kses_post( $content ) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Contact Section End -->


<!-- LIST OF POSTS Section -->
<section id="blog" class="section">
    <!-- Container Starts -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'all_fuel_title' ) ) ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_description' ) ) ?></p>
        </div>
        <div class="row">
            <?php foreach ( $list->posts as $item ) : ?>
                <?php $public_key = get_post_meta( $item->ID, PORCH_LANDING_META_KEY, true ); ?>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12 blog-item">
                    <!-- Blog Item Starts -->
                    <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
                        <div class="blog-item-img">
                            <a href="/prayer/fuel/<?php echo esc_attr( $public_key ) ?>">
                                <img src="<?php echo esc_url( trailingslashit( plugin_dir_url( __DIR__ ) ) ) ?>landing-pages/img/300x1.png" alt="">
                            </a>
                        </div>
                        <div class="blog-item-text">
                            <h3>
                                <a href="#"><?php echo esc_html( $item->post_title ) ?></a>
                            </h3>
                            <div class="meta-tags">
                                <span class="date"><i class="lnr lnr-calendar-full"></i>on <?php echo esc_html( gmdate( 'Y-m-d', strtotime( $item->post_date ) ) )  ?></span>
                            </div>
                            <p>
                                <?php echo wp_kses_post( $item->post_excerpt ) ?>
                            </p>
                            <a href="/prayer/fuel/<?php echo esc_attr( $public_key ) ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'Read', 'dt-campaign-generic-porch' ); ?></a>
                        </div>
                    </div>
                    <!-- Blog Item Wrapper Ends-->
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<!-- blog Section End -->




