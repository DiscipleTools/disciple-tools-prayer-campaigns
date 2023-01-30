<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$porch_fields = DT_Porch_Settings::settings();

$todays_campaign_day = DT_Campaign_Settings::what_day_in_campaign( gmdate( 'Y-m-d' ) );

$today = DT_Campaign_Prayer_Fuel_Post_Type::instance()->get_days_posts( $todays_campaign_day );
?>

<!-- TODAYS POST Section -->
<section id="contact" class="section">
    <div class="container">
        <div class="row">

            <div class="section-header col">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'todays_fuel_title' ) ) ?></h2>
                <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            </div>

            <?php foreach ( $today->posts as $item ) : ?>

                <?php dt_campaign_post( $item ) ?>

            <?php endforeach; ?>

            <?php
            if ( !isset( $porch_fields['show_prayer_timer']['value'] ) || empty( $porch_fields['show_prayer_timer']['value'] ) || $porch_fields['show_prayer_timer']['value'] === 'yes' ) :
                if ( function_exists( 'show_prayer_timer' ) ) : ?>
                    <div class="col">
                        <?php echo do_shortcode( "[dt_prayer_timer color='" . PORCH_COLOR_SCHEME_HEX . "' duration='15' lang='" . $lang . "']" ); ?>
                    </div>
                <?php endif;
            endif; ?>

        </div>
    </div>
</section>
<!-- Contact Section End -->
