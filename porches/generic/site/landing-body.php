<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();
$porch_fields = DT_Porch_Settings::settings();
$content = 'No post found';
$day = isset( $this->parts['public_key'] ) && ! empty( $this->parts['public_key'] ) ? $this->parts['public_key'] : 0;

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
        <div class="row">
            <?php if ( !isset( $porch_fields['show_prayer_timer']['value'] ) || empty( $porch_fields['show_prayer_timer']['value'] ) || $porch_fields['show_prayer_timer']['value'] === 'yes' ) :
                if ( function_exists( 'show_prayer_timer' ) ) : ?>
                    <div class="col">
                        <?php echo do_shortcode( "[dt_prayer_timer color='" . PORCH_COLOR_SCHEME_HEX . "' duration='15' lang='" . $lang . "']" ); ?>
                    </div>
                <?php endif;
            endif; ?>

        </div>

        <div class="row" style="margin-top: 20px">
            <p>
                Are you multiple people praying together? How many? <input type="number" name="group_size" value="1" id="prayer_group_size" style="width: 50px; margin: 0 10px"><button>Submit</button>
            </p>
        </div>
    </div>
</section>
<!-- Contact Section End -->
