<?php
$lang = dt_campaign_get_current_lang();

$porch_fields = DT_Porch_Settings::settings();
$campaign_fields = DT_Campaign_Settings::get_campaign();

$campaign_root = 'campaign_app';
$campaign_type = $campaign_fields['type']['key'];
$key_name = 'public_key';
$key = '';
if ( method_exists( 'DT_Magic_URL', 'get_public_key_meta_key' ) ){
    $key_name = DT_Magic_URL::get_public_key_meta_key( $campaign_root, $campaign_type );
}
if ( isset( $campaign_fields[$key_name] ) ){
    $key = $campaign_fields[$key_name];
}
$atts = [
    'root' => $campaign_root,
    'type' => $campaign_type,
    'public_key' => $key,
    'meta_key' => $key_name,
    'post_id' => (int) $campaign_fields['ID'],
    'rest_url' => rest_url(),
    'lang' => $lang
];
$dt_campaign_selected_campaign_magic_link_settings = $atts;
$dt_campaign_selected_campaign_magic_link_settings['color'] = PORCH_COLOR_SCHEME_HEX;
if ( $dt_campaign_selected_campaign_magic_link_settings['color'] === 'preset' ){
    $dt_campaign_selected_campaign_magic_link_settings['color'] = '#4676fa';
}
?>

<!-- Vision -->
<section id="campaign-vision" class="section">
    <div class="container">
        <div class="section-header row">
            <div class="col-sm-12 col-md-8">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s">
                    <?php echo esc_html( dt_split_sentence( DT_Porch_Settings::get_field_translation( 'vision_title' ), 1, 2 ) ) ?> <span><?php echo esc_html( dt_split_sentence( DT_Porch_Settings::get_field_translation( 'vision_title' ), 2, 2 ) ) ?></span>
                </h2>
                <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                <div style="padding: 2em">
                <p class="section-subtitle wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s" style="padding:2em">
                    <?php echo nl2br( esc_html( DT_Porch_Settings::get_field_translation( 'vision' ) ) ); ?>
                </p>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <?php dt_generic_percentage_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                    <div class="icon">
                        <img class="color-img" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Generic_Porch::assets_dir() . 'img/pray.svg' ) ?>" alt="Praying hands icon"/>
                    </div>
                    <h4><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'pray_section_title' ) ) ?></h4>
                    <p><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'pray_section_text' ) ) ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.4s">
                    <div class="icon">
                        <img class="color-img" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Generic_Porch::assets_dir() . 'img/movement.svg' ) ?>" alt="a network icon indicating movement"/>
                    </div>
                    <h4><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'movement_section_title' ) ) ?></h4>
                    <p><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'movement_section_text' ) ) ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.6s">
                    <div class="icon">
                        <img class="color-img" style="height: 40px; margin-top:10px" src="<?php echo esc_html( DT_Generic_Porch::assets_dir() . 'img/24_7.svg' ) ?>" alt="clock icon"/>
                    </div>
                    <h4><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'time_section_title' ) ) ?></h4>
                    <p><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'time_section_text' ) ) ?></p>
                </div>
            </div>
        </div>
        <div class="row" style="justify-content: center">
            <a href="<?php echo esc_url( site_url() ) ?>#sign-up" class="btn btn-common"><?php esc_html_e( 'Sign Up to Pray', 'disciple-tools-prayer-campaigns' ); ?></a>
        </div>

    </div>
</section>
<!-- Services Section End -->


<!-- COUNTER ROW -->
<div id="days_until" class="counters section" data-stellar-background-ratio="0.5" >
    <div class="overlay"></div>
    <div class="container">
        <div id="counter_row" class="row">
            <div class="col-sm-12">
                <div class="wow fadeInUp" data-wow-delay=".3s">
                    <div class="facts-item">
                        <div class="fact-count">
                            <h2 id="counter_title" style="font-size:3em;"></h2>
                            <h3><span id="days"></span><span id="hours"></span><span id="mins"></span><span id="secs"></span><span id="end"></span></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 30px">
            <div class="col-sm-12 col-md-8" style="color: white">
                <?php echo nl2br( esc_html( DT_Porch_Settings::get_field_translation( 'what_content' ) ) ); ?>
            </div>
            <div class="col-sm-12 col-md-4">
                <?php dt_generic_calendar_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); ?>
            </div>
        </div>
    </div>
</div>
<!-- Counter Section End -->
<script>
    let myfunc = setInterval(function() {
        // The data/time we want to countdown to
        <?php
        $timezone = !empty( $campaign_fields['campaign_timezone'] ) ? $campaign_fields['campaign_timezone']['key'] : 'America/Chicago';
        $tz = new DateTimeZone( $timezone );
        $begin_date = new DateTime( '@'.$campaign_fields['start_date']['timestamp'] );
        $begin_date->setTimezone( $tz );
        $timezone_offset = $tz->getOffset( $begin_date );

        $timezone_adjusted_start_date = $campaign_fields['start_date']['timestamp'] - $timezone_offset;

        $has_end_date = isset( $campaign_fields['end_date'] );
        $timezone_adjusted_end_date = $has_end_date ? $campaign_fields['end_date']['timestamp'] - $timezone_offset : 0;
        ?>
        let countDownDate = '<?php echo esc_html( $timezone_adjusted_start_date )?>'
        let endCountDownDate = '<?php echo esc_html( $timezone_adjusted_end_date ) ?>'
        let hasEndDate = "<?php echo esc_html( $has_end_date ); ?>"


        let now = new Date().getTime() / 1000
        let timeLeft = countDownDate - now;
        let endTimeLeft = endCountDownDate - now ;

        let days = Math.floor(timeLeft / (60 * 60 * 24));
        let hours = Math.floor((timeLeft % (60 * 60 * 24)) / (60 * 60));
        let minutes = Math.floor((timeLeft % (60 * 60)) / 60);
        let seconds = Math.floor(timeLeft % 60);

        if ( hasEndDate && endTimeLeft < 0 ) {
            clearInterval(myfunc);
            document.getElementById("counter_title").innerHTML = "<?php echo esc_html__( 'Ramadan is Finished', 'pray4ramadan-porch' ); ?>"
            document.getElementById("days").innerHTML = ""
            document.getElementById("hours").innerHTML = ""
            document.getElementById("mins").innerHTML = ""
            document.getElementById("secs").innerHTML = ""
        }
        else if ( hasEndDate && timeLeft < 0 ) {

            days = Math.floor(endTimeLeft / (60 * 60 * 24));
            hours = Math.floor((endTimeLeft % (60 * 60 * 24)) / (60 * 60));
            minutes = Math.floor((endTimeLeft % (60 * 60)) / 60);
            seconds = Math.floor(endTimeLeft % 60);

            document.getElementById("counter_title").innerHTML = "<?php echo esc_html__( 'Ramadan Ends ...', 'pray4ramadan-porch' ); ?>"
            document.getElementById("days").innerHTML = days + " <?php echo esc_html__( 'days', 'disciple-tools-prayer-campaigns' ); ?>, "
            document.getElementById("hours").innerHTML = hours + " <?php echo esc_html__( 'hours', 'disciple-tools-prayer-campaigns' ); ?>, "
            document.getElementById("mins").innerHTML = minutes + " <?php echo esc_html__( 'minutes', 'disciple-tools-prayer-campaigns' ); ?>, "
            document.getElementById("secs").innerHTML = seconds + " <?php echo esc_html__( 'seconds', 'disciple-tools-prayer-campaigns' ); ?>"

        } else {
            document.getElementById("counter_title").innerHTML = "<?php echo esc_html__( 'Ramadan Begins ...', 'pray4ramadan-porch' ); ?>"
            document.getElementById("days").innerHTML = days + " <?php echo esc_html__( 'days', 'disciple-tools-prayer-campaigns' ); ?>, "
            document.getElementById("hours").innerHTML = hours + " <?php echo esc_html__( 'hours', 'disciple-tools-prayer-campaigns' ); ?>, "
            document.getElementById("mins").innerHTML = minutes + " <?php echo esc_html__( 'minutes', 'disciple-tools-prayer-campaigns' ); ?>, "
            document.getElementById("secs").innerHTML = seconds + " <?php echo esc_html__( 'seconds', 'disciple-tools-prayer-campaigns' ); ?>"
        }

        if ( !hasEndDate && timeLeft < 0 ) {
            document.getElementById("counter_row").style.display = 'none'
        }

    }, 1000)
</script>

<!-- SIGN UP TO PRAY -->
<section id="features" class="section" data-stellar-background-ratio="0.2">
    <div id="sign-up" name="sign-up" class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Sign Up to Pray', 'disciple-tools-prayer-campaigns' ); ?></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
        </div>
        <div class="row">
            <?php
            if ( empty( $dt_campaign_selected_campaign_magic_link_settings ) ) :?>

            <p style="margin:auto">Choose campaign in settings <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_porch_template&tab=general' ) );?>"><?php esc_html_e( 'here', 'disciple-tools-prayer-campaigns' ); ?></a></p>

            <?php else : ?>

                <?php dt_generic_signup_shortcode( $dt_campaign_selected_campaign_magic_link_settings ); ?>

            <?php endif; ?>
        </div>
    </div>
</section>
<!-- Features Section End -->

<!-- COUNTER ROW -->
<div class="counters section" data-stellar-background-ratio="0.5" >
    <div class="overlay"></div>
    <div class="container">
        <div class="row">
            <?php $days_in_campaign = DT_Campaigns_Base::total_days_in_campaign() ?>
            <div class="col-sm-6 col-md-4 col-lg-4">
                <div class="wow fadeInUp" data-wow-delay=".2s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-calendar-full"></i>
                        </div>
                        <div class="fact-count">

                            <?php if ( $campaign_type === '24hour' ): ?>

                                <h3><span class="counter"><?php echo $days_in_campaign !== -1 ? esc_html( $days_in_campaign ) : '30' ?></span></h3>
                                <h4><?php esc_html_e( 'Days', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php elseif ( $campaign_type === 'ongoing' ): ?>

                                <h3><?php esc_html_e( 'Next', 'disciple-tools-prayer-campaigns' ) ?></h3>
                                <h4><?php esc_html_e( 'Month', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
            <?php $hours_next_month = DT_Campaigns_Base::number_of_hours_next_month() ?>
            <?php $more_hours_needed = $hours_next_month - ceil( DT_Campaigns_Base::query_scheduled_minutes_next_month( $campaign_fields['ID'] ) / 60 ) ?>
            <?php /* 96 daily prayer warriors would be needed to cover the campaign... Do I use that number? */ ?>
            <div class="col-sm-6 col-md-4 col-lg-4">
                <div class="wow fadeInUp" data-wow-delay=".6s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-user"></i>
                        </div>
                        <div class="fact-count">

                            <?php if ( $campaign_type === '24hour' ): ?>

                                <h3><?php echo $days_in_campaign !== -1 ? esc_html( $days_in_campaign * 24 ) : '720' ?></h3>
                                <h4><?php esc_html_e( 'Hours of Prayer', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php elseif ( $campaign_type === 'ongoing' ): ?>

                                <h3><?php echo esc_html( $hours_next_month ) ?></h3>
                                <h4><?php esc_html_e( 'Hours', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-4">
                <div class="wow fadeInUp" data-wow-delay=".8s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-heart"></i>
                        </div>
                        <div class="fact-count">

                            <?php if ( $campaign_type === '24hour' ): ?>

                                <h3><?php echo $days_in_campaign !== -1 ? esc_html( $days_in_campaign * 24 * 4 ) : '2880' ?></h3>
                                <h4><?php esc_html_e( 'Prayer Commitments Needed', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php elseif ( $campaign_type === 'ongoing' ): ?>

                                <h3><?php echo esc_html( $more_hours_needed ) ?></h3>
                                <h4><?php esc_html_e( 'More Hours to be covered', 'disciple-tools-prayer-campaigns' ); ?></h4>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Counter Section End -->

<!-- Blog Section -->
<section id="blog" class="section">
    <!-- Container Starts -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title split-color wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( dt_split_sentence( DT_Porch_Settings::get_field_translation( 'prayer_fuel_title' ), 1, 2 ) ) ?> <span><?php echo esc_html( dt_split_sentence( DT_Porch_Settings::get_field_translation( 'prayer_fuel_title' ), 2, 2 ) ) ?></span> </h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_description' ) ); ?></p>
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><a href="<?php echo esc_html( home_url( '/prayer/list' ) ); ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'View All', 'disciple-tools-prayer-campaigns' ); ?></a></p>
        </div>
    </div>
</section>
<!-- blog Section End -->

