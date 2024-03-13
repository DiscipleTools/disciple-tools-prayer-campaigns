<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function dt_campaign_post( $post ) {
    $content = apply_filters( 'the_content', $post->post_content );
    $content = str_replace( ']]>', ']]&gt;', $content );
    ?>

    <div class="col-md-12 mb-5 wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
        <div class="fuel-block">
            <div class="section-header">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( $post->post_title ) ?></h2>
            </div>
            <div class="">
                <?php echo wp_kses_post( $content ) ?>
            </div>
        </div>
    </div>

    <?php
}


function dt_campaign_user_record_prayed(){
    $campaign = DT_Campaign_Settings::get_campaign();
    $minutes_scheduled = isset( $campaign['ID'] ) ? DT_Campaigns_Base::get_minutes_prayed_and_scheduled( $campaign['ID'] ) : 0;
    $days_scheduled = round( !empty( $minutes_scheduled ) ? ( $minutes_scheduled / 24 / 60 ) : 0, 1 );
    ?>
        <form onsubmit='submit_group_count();return false;' id='form-content'>
            <div class='section-header col'>
                <h2 class='section-title wow fadeIn' data-wow-duration='1000ms'
                    data-wow-delay='0.3s'><?php echo esc_html( __( 'Praying as a group?', 'disciple-tools-prayer-campaigns' ) ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            </div>
            <div class='content-paragraph-padding'>
                <p >
                    <?php echo esc_html( sprintf( __( 'Leading others in prayer is a great step in making disciples. We want to celebrate your and their faithfulness.  For each person that prays with you, we will add 15 minutes to the %s days of prayer committed so far.', 'disciple-tools-prayer-campaigns' ), $days_scheduled ) ); ?>
                </p>
                <div style="text-align: start">
                    <span><?php esc_html_e( 'How many prayed today (including yourself)?', 'disciple-tools-prayer-campaigns' ); ?></span>
                    <input type='number' name='group_size' value='1' id='prayer_group_size'
                           style='width: 50px; margin-left: 10px; padding: 5px'>
                    <input type='email' id='email' style='display: none'>
                    <button id="prayer-group-size-button" class="btn btn-common btn-rm">
                        <?php esc_html_e( 'Submit', 'disciple-tools-prayer-campaigns' ); ?>
                        <img id='prayer_group_size-spinner' style='display: none; margin-left: 10px'
                             src='<?php echo esc_url( trailingslashit( get_stylesheet_directory_uri() ) ) ?>spinner.svg'
                             width='22px;' alt='spinner '/>
                    </button>
                    <span id="group-size-thank-you" style="display: none">
                                        <?php esc_html_e( 'Thank you', 'disciple-tools-prayer-campaigns' ); ?>
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
            return window.makeRequest('POST', '/group-count', {
              parts: jsObject.parts,
              number,
            }, jsObject.parts.root + '/v1/fuel').done(function (data) {
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
    <?php
}