<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();
$porch_fields = DT_Porch_Settings::settings();
$content = 'No post found';
$url = dt_get_url_path( true );
$url_parts = explode( '/', $url );
$day = end( $url_parts );
if ( !is_numeric( $day ) ){
    die( 'Invalid day' );
}

$today = DT_Campaign_Prayer_Fuel_Post_Type::instance()->get_days_posts( $day );
?>


<!-- Single Prayer Fuel Section Start -->
<section id="contact" class="section">
    <div class="container">
        <div class="row">

            <div class="section-header col">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_name' ) ) ?></h2>
                <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            </div>

            <?php foreach ( $today->posts as $item ) :

                dt_campaign_post( $item );

            endforeach; ?>

        </div>

        <div class="row" style="margin-top: 30px">
            <?php dt_campaign_user_record_prayed(); ?>
        </div>

        <?php
        do_action( 'dt_campaigns_after_prayer_fuel', 'after_record_prayed', $today->posts, $day );

        if ( !isset( $porch_fields['show_prayer_timer']['value'] ) || empty( $porch_fields['show_prayer_timer']['value'] ) || $porch_fields['show_prayer_timer']['value'] !== 'no' ) :
            if ( function_exists( 'show_prayer_timer' ) ) : ?>
                <div style="margin-top: 30px">
                    <div class='section-header'>
                        <h2 class='section-title wow fadeIn' data-wow-duration='1000ms'
                            data-wow-delay='0.3s'><?php echo esc_html( __( 'Prayer Timer', 'disciple-tools-prayer-campaigns' ) ); ?></h2>
                        <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                    </div>
                    <div>
                        <?php echo do_shortcode( "[dt_prayer_timer color='" . CAMPAIGN_LANDING_COLOR_SCHEME_HEX . "' duration='15' lang='" . $lang . "']" ); ?>
                    </div>
                </div>
            <?php endif;
        endif; ?>
    </div>
</section>
<!-- Single Prayer Fuel Section End -->
