<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();
$porch_fields = DT_Porch_Settings::settings();

$frequency = isset( $porch_fields['prayer_fuel_frequency']['value'] ) ? $porch_fields['prayer_fuel_frequency']['value'] : 'daily';
$todays_campaign_day = DT_Campaign_Settings::what_day_in_campaign( gmdate( 'Y-m-d' ), $frequency );

$today = DT_Campaign_Prayer_Fuel_Post_Type::instance()->get_days_posts( $todays_campaign_day );
?>

<!-- TODAY'S (or most recent POST Section) -->
<section id="contact" class="section">
    <div class="container">
        <div class="row">
            <div class="section-header col">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'todays_fuel_title' ) ) ?></h2>
                <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            </div>

        </div>

        <div class="row">
            <?php foreach ( $today->posts as $item ) :
                dt_campaign_post( $item );
            endforeach;
            if ( empty( $today->posts ) && is_numeric( $todays_campaign_day ) ):
                $most_recent = DT_Campaign_Prayer_Fuel_Post_Type::instance()->get_most_recent_post( $todays_campaign_day );
                if ( (int) $todays_campaign_day <= 0 ) : ?>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 blog-item">
                        <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
                            <div class="blog-item-text">
                                <?php echo esc_html( sprintf( __( 'Content will start in %s days', 'disciple-tools-prayer-campaigns' ), -$todays_campaign_day ) ); ?>
                            </div>
                        </div>
                    </div>
                <?php elseif ( !empty( $most_recent ) ) :
                    foreach ( $most_recent->posts as $item ) :
                        dt_campaign_post( $item );
                    endforeach;
                else : ?>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 blog-item">
                        <div class="blog-item-wrapper wow fadeInUp" data-wow-delay="0.3s">
                            <div class="blog-item-text">
                                <?php echo esc_html( __( 'This campaign is finished. See Fuel below', 'disciple-tools-prayer-campaigns' ) ); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ( $today->found_posts || $most_recent ) : ?>

            <div class="row" style="margin-top: 30px">
                <?php dt_campaign_user_record_prayed(); ?>
            </div>

            <div class='row'>
                <?php
                if ( !isset( $porch_fields['show_prayer_timer']['value'] ) || empty( $porch_fields['show_prayer_timer']['value'] ) || $porch_fields['show_prayer_timer']['value'] === 'yes' ) :
                    if ( function_exists( 'show_prayer_timer' ) ) : ?>
                        <div class="col">
                            <?php echo do_shortcode( "[dt_prayer_timer color='" . PORCH_COLOR_SCHEME_HEX . "' duration='15' lang='" . $lang . "']" ); ?>
                        </div>
                    <?php endif;
                endif; ?>

            </div>

        <?php endif; ?>
    </div>
</section>
<!-- Contact Section End -->
