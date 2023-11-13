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
$campaign = DT_Campaign_Landing_Settings::get_campaign();
$minutes_scheduled = isset( $campaign['ID'] ) ? DT_Campaigns_Base::get_minutes_prayed_and_scheduled( $campaign['ID'] ) : 0;
$days_scheduled = round( !empty( $minutes_scheduled ) ? ( $minutes_scheduled / 24 / 60 ) : 0, 1 );
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
            <form onsubmit="event.preventDefault(); submit_group_count(); return false;" id='form-content'>
                <div class='section-header col'>
                    <h2 class='section-title wow fadeIn' data-wow-duration='1000ms'
                        data-wow-delay='0.3s'><?php echo esc_html( __( 'Praying as a group?', 'disciple-tools-prayer-campaigns' ) ); ?></h2>
                    <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                </div>
                <div class='leading-others'>
                    <p>
                        <?php echo esc_html( sprintf( __( 'Leading others in prayer is a great step in making disciples. We want to celebrate your and their faithfulness.  For each person that prays with you, we will add 15 minutes to the %s days of prayer committed so far.', 'disciple-tools-prayer-campaigns' ), $days_scheduled ) ); ?>
                    </p>

                    <div style="text-align: start">
                        <span><?php esc_html_e( 'How many prayed today (including yourself)?', 'disciple-tools-prayer-campaigns' ); ?></span>
                        <input type='number' name='group_size' value='1' id='prayer_group_size'
                               style='width: 70px; margin-left: 10px; padding: 5px'>
                        <input type='email' id='email' style='display: none'>
                        <button id="prayer-group-size-button" class="btn btn-common btn-rm" >
                            <?php esc_html_e( 'Submit', 'disciple-tools-prayer-campaigns' ); ?>
                            <img id='prayer_group_size-spinner' style='display: none; margin-left: 10px'
                                 src='<?php echo esc_url( trailingslashit( get_stylesheet_directory_uri() ) ) ?>spinner.svg'
                                 width='22px;' alt='spinner '/>
                        </button>
                        <span id="group-size-thank-you" style="display: none">
                            <?php esc_html_e( 'Thank You', 'disciple-tools-prayer-campaigns' ); ?>
                        </span>
                    </div>
                </div>
            </form>
            <script>

                let submit_group_count = function () {
                    $('#prayer_group_size-spinner').show()
                    let honey = $('#email').val();
                    if (honey) {
                        return false;
                    }
                    let number = $('#prayer_group_size').val() || 1
                    let options = {
                      type: 'POST',
                      contentType: 'application/json; charset=utf-8',
                      dataType: 'json',
                      data: JSON.stringify({
                        parts: jsObject.parts,
                        number,
                        campaign_id: <?php echo esc_html( $campaign['ID'] ) ?>,
                      }),
                      url: '<?php echo esc_html( rest_url() ) ?>campaign_app/v1/group-count',
                    }
                    jQuery.ajax(options).done(function (data) {
                        $('#prayer_group_size-spinner').hide()
                        $('#group-size-thank-you').show()
                        $('#prayer-group-size-button').attr('disabled', true)
                    })
                    .fail(function (e) {
                        // jQuery('#error').html(e)
                        console.log(e)
                    })
                }
            </script>
        </div>

        <div class='row'>
            <?php if ( !isset( $porch_fields['show_prayer_timer']['value'] ) || empty( $porch_fields['show_prayer_timer']['value'] ) || $porch_fields['show_prayer_timer']['value'] === 'yes' ) :
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
