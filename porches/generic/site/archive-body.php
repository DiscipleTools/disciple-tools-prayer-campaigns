<?php
$porch_fields = DT_Porch_Settings::settings();
$lang = dt_campaign_get_current_lang();

$todays_campaign_day = DT_Campaign_Settings::what_day_in_campaign( gmdate( 'Y-m-d' ) );

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

$meta_query = [
    "relation" => "AND",
    [
        "key" => "day",
        "value" => $todays_campaign_day,
        "compare" => "=",
    ],
    $lang_query,
];

//get latest published post
$today = new WP_Query( [
    'post_type' => PORCH_LANDING_POST_TYPE,
    'post_status' => [ 'publish', 'future' ] ,
    'posts_per_page' => -1,
    'orderby' => 'post_date',
    'order' => 'DESC',
    'meta_query' => $meta_query,
] );
if ( empty( $today->posts ) ){
    //get earliest unpublished post
    $today = new WP_Query( [
        'post_type' => PORCH_LANDING_POST_TYPE,
        'post_status' => 'future',
        'posts_per_page' => 1,
        'orderby' => 'post_date',
        'order' => 'ASC',
        'meta_query' => $lang_query,
    ] );
}
if ( empty( $today->posts ) ){
    //post in english
    $args = array(
        'post_type' => PORCH_LANDING_POST_TYPE,
        'post_status' => [ 'publish', 'future' ],
        'posts_per_page' => 1,
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
            [
                "key" => "day",
                "value" => $todays_campaign_day,
            ]
        ]
    );
    $today = new WP_Query( $args );
}
?>

<!-- TODAYS POST Section -->
<section id="contact" class="section">
    <div class="container">
        <div class="row justify-content-md-center">
            <div class="col-md-9 wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                <div class="fuel-block">
                    <div class="section-header">
                        <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'todays_fuel_title' ) ) ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>
                    <div class="">
                        <?php foreach ( $today->posts as $item ) :
                            $content = apply_filters( 'the_content', $item->post_content );
                            $content = str_replace( ']]>', ']]&gt;', $content );
                            echo wp_kses_post( $content );
                        endforeach; ?>
                    </div>
                    <?php
                    if ( !isset( $porch_fields['show_prayer_timer']['value'] ) || empty( $porch_fields['show_prayer_timer']['value'] ) || $porch_fields['show_prayer_timer']['value'] === 'yes' ) :
                        if ( function_exists( 'show_prayer_timer' ) ) : ?>
                            <div class="mt-5">
                                <?php echo do_shortcode( "[dt_prayer_timer color='" . PORCH_COLOR_SCHEME_HEX . "' duration='15' lang='" . $lang . "']" ); ?>
                            </div>
                        <?php endif;
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Contact Section End -->
