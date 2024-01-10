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


<!-- Contact Section Start -->
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
    </div>
</section>
<!-- Contact Section End -->
