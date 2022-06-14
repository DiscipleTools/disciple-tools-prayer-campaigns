<?php
$lang = dt_campaign_get_current_lang();

$porch_fields = DT_Porch_Settings::porch_fields();
$campaign_fields = DT_Campaign_Settings::get_campaign();

$campaign_root = "campaign_app";
$campaign_type = $campaign_fields["type"]["key"];
$key_name = 'public_key';
$key = "";
if ( method_exists( "DT_Magic_URL", "get_public_key_meta_key" ) ){
    $key_name = DT_Magic_URL::get_public_key_meta_key( $campaign_root, $campaign_type );
}
if ( isset( $campaign_fields[$key_name] ) ){
    $key = $campaign_fields[$key_name];
}
$atts = [
    "root" => $campaign_root,
    "type" => $campaign_type,
    "public_key" => $key,
    "meta_key" => $key_name,
    "post_id" => (int) $campaign_fields["ID"],
    "rest_url" => rest_url(),
    "lang" => $lang
];
$dt_ramadan_selected_campaign_magic_link_settings = $atts;
$dt_ramadan_selected_campaign_magic_link_settings["color"] = PORCH_COLOR_SCHEME_HEX;
if ( $dt_ramadan_selected_campaign_magic_link_settings["color"] === "preset" ){
    $dt_ramadan_selected_campaign_magic_link_settings["color"] = '#4676fa';
}
?>

<!-- Vision -->
<section id="campaign-vision" class="section">
    <div class="container">
        <div class="section-header row">
            <div class="col-sm-12 col-md-8">
                <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Our', 'dt-campaign-generic-porch' ); ?> <span><?php esc_html_e( 'Vision', 'dt-campaign-generic-porch' ); ?></span></h2>
                <hr class="lines wow zoomIn" data-wow-delay="0.3s">
                <div style="padding: 2em">
                <p class="section-subtitle wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s" style="padding:2em">
                    <?php echo nl2br( esc_html( DT_Porch_Settings::get_field_translation( $porch_fields["goal"], $lang ) ) ); ?>
                </p>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <?php
                $dt_ramadan_selected_campaign_magic_link_settings["section"] = "percentage";
                echo dt_24hour_campaign_shortcode( //phpcs:ignore
                    $dt_ramadan_selected_campaign_magic_link_settings
                );
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.2s">
                    <div class="icon">
                        <img class="color-img" style="height: 40px; margin-top:10px" src="<?php echo esc_html( plugin_dir_url( __File__ ) . 'img/pray.svg' ) ?>" alt="Praying hands icon"/>
                    </div>
                    <h4><?php esc_html_e( 'Extraordinary Prayer', 'dt-campaign-generic-porch' ); ?></h4>
                    <p><?php esc_html_e( 'Every disciple making movement in history has happened in the context of extraordinary prayer.', 'dt-campaign-generic-porch' ); ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.4s">
                    <div class="icon">
                        <img class="color-img" style="height: 40px; margin-top:10px" src="<?php echo esc_html( plugin_dir_url( __File__ ) . 'img/movement.svg' ) ?>" alt="a network icon indicating movement"/>
                    </div>
                    <h4><?php esc_html_e( 'Movement Focused', 'dt-campaign-generic-porch' ); ?></h4>
                    <p><?php esc_html_e( 'Join us in asking, seeking, and knocking for streams of disciples and churches to be made.', 'dt-campaign-generic-porch' ); ?></p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="item-boxes wow fadeInDown" data-wow-delay="0.6s">
                    <div class="icon">
                        <img class="color-img" style="height: 40px; margin-top:10px" src="<?php echo esc_html( plugin_dir_url( __File__ ) . 'img/24_7.svg' ) ?>" alt="clock icon"/>
                    </div>
                    <h4><?php esc_html_e( '24/7 for 30 Days', 'dt-campaign-generic-porch' ); ?></h4>
                    <p><?php esc_html_e( 'Choose a 15-minute (or more!) time slot that you can pray during each day. Invite someone else to sign up too.', 'dt-campaign-generic-porch' ); ?></p>
                </div>
            </div>
        </div>
        <div class="row" style="justify-content: center">
            <a href="<?php echo esc_url( site_url() ) ?>#sign-up" class="btn btn-common"><?php esc_html_e( 'Sign Up to Pray', 'dt-campaign-generic-porch' ); ?></a>
        </div>

    </div>
</section>
<!-- Services Section End -->


<!-- COUNTER ROW -->
<div id="days_until" class="counters section" data-stellar-background-ratio="0.5" >
    <div class="overlay"></div>
    <div class="container">
        <div class="row">
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
                <?php echo nl2br( esc_html( DT_Porch_Settings::get_field_translation( $porch_fields["what_content"], $lang ) ) ); ?>
            </div>
            <div class="col-sm-12 col-md-4">
                <?php
                $dt_ramadan_selected_campaign_magic_link_settings["section"] = "calendar";
                echo dt_24hour_campaign_shortcode( //phpcs:ignore
                    $dt_ramadan_selected_campaign_magic_link_settings
                );
                ?>
            </div>
        </div>
    </div>
</div>
<!-- Counter Section End -->
<script>
    let myfunc = setInterval(function() {
        // The data/time we want to countdown to
        <?php
        $timezone = !empty( $campaign_fields["campaign_timezone"] ) ? $campaign_fields["campaign_timezone"]["key"] : 'America/Chicago';
        $tz = new DateTimeZone( $timezone );
        $begin_date = new DateTime( "@".$campaign_fields['start_date']['timestamp'] );
        $begin_date->setTimezone( $tz );
        $timezone_offset = $tz->getOffset( $begin_date );

        $timezone_adjusted_start_date = $campaign_fields['start_date']['timestamp'] - $timezone_offset;

        $timezone_adjusted_end_date = $campaign_fields['end_date']['timestamp'] - $timezone_offset;
        ?>
        let countDownDate = '<?php echo esc_html( $timezone_adjusted_start_date )?>'
        let endCountDownDate = '<?php echo esc_html( $timezone_adjusted_end_date ) ?>'


        let now = new Date().getTime() / 1000
        let timeleft = countDownDate - now;
        let endtimeleft = endCountDownDate - now ;

        let days = Math.floor(timeleft / (60 * 60 * 24));
        let hours = Math.floor((timeleft % (60 * 60 * 24)) / (60 * 60));
        let minutes = Math.floor((timeleft % (60 * 60)) / 60);
        let seconds = Math.floor(timeleft % 60);

        if ( endtimeleft < 0 ) {
            clearInterval(myfunc);
            document.getElementById("counter_title").innerHTML = "<?php echo esc_html__( "Ramadan is Finished", 'dt-campaign-generic-porch' ); ?>"
            document.getElementById("days").innerHTML = ""
            document.getElementById("hours").innerHTML = ""
            document.getElementById("mins").innerHTML = ""
            document.getElementById("secs").innerHTML = ""
        }
        else if ( timeleft < 0 ) {

            days = Math.floor(endtimeleft / (60 * 60 * 24));
            hours = Math.floor((endtimeleft % (60 * 60 * 24)) / (60 * 60));
            minutes = Math.floor((endtimeleft % (60 * 60)) / 60);
            seconds = Math.floor(endtimeleft % 60);

            document.getElementById("counter_title").innerHTML = "<?php echo esc_html__( "Ramadan Ends ...", 'dt-campaign-generic-porch' ); ?>"
            document.getElementById("days").innerHTML = days + " <?php echo esc_html__( "days", 'dt-campaign-generic-porch' ); ?>, "
            document.getElementById("hours").innerHTML = hours + " <?php echo esc_html__( "hours", 'dt-campaign-generic-porch' ); ?>, "
            document.getElementById("mins").innerHTML = minutes + " <?php echo esc_html__( "minutes", 'dt-campaign-generic-porch' ); ?>, "
            document.getElementById("secs").innerHTML = seconds + " <?php echo esc_html__( "seconds", 'dt-campaign-generic-porch' ); ?>"

        } else {
            document.getElementById("counter_title").innerHTML = "<?php echo esc_html__( "Ramadan Begins ...", 'dt-campaign-generic-porch' ); ?>"
            document.getElementById("days").innerHTML = days + " <?php echo esc_html__( "days", 'dt-campaign-generic-porch' ); ?>, "
            document.getElementById("hours").innerHTML = hours + " <?php echo esc_html__( "hours", 'dt-campaign-generic-porch' ); ?>, "
            document.getElementById("mins").innerHTML = minutes + " <?php echo esc_html__( "minutes", 'dt-campaign-generic-porch' ); ?>, "
            document.getElementById("secs").innerHTML = seconds + " <?php echo esc_html__( "seconds", 'dt-campaign-generic-porch' ); ?>"
        }

    }, 1000)
</script>

<!-- SIGN UP TO PRAY -->
<section id="features" class="section" data-stellar-background-ratio="0.2">
    <div id="sign-up" name="sign-up" class="container">
        <div class="section-header">
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Sign Up to', 'dt-campaign-generic-porch' ); ?> <span><?php esc_html_e( 'Pray', 'dt-campaign-generic-porch' ); ?></span></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
        </div>
        <div class="row">
            <?php
            if ( empty( $dt_ramadan_selected_campaign_magic_link_settings ) ) :?>
                <p style="margin:auto">Choose campaign in settings <a href="<?php echo esc_html( admin_url( 'admin.php?page=dt_porch_template&tab=general' ) );?>"><?php esc_html_e( 'here', 'dt-campaign-generic-porch' ); ?></a></p>
            <?php else :

                $dt_ramadan_selected_campaign_magic_link_settings["section"] = "sign_up";
                echo dt_24hour_campaign_shortcode( //phpcs:ignore
                    $dt_ramadan_selected_campaign_magic_link_settings
                );
            endif;
            ?>
        </div>
    </div>
</section>
<!-- Features Section End -->

<!-- COUNTER ROW -->
<div class="counters section" data-stellar-background-ratio="0.5" >
    <div class="overlay"></div>
    <div class="container">
        <div class="row">
            <div class="col-sm-6 col-md-4 col-lg-4">
                <div class="wow fadeInUp" data-wow-delay=".2s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-calendar-full"></i>
                        </div>
                        <div class="fact-count">
                            <h3><span class="counter">30</span></h3>
                            <h4><?php esc_html_e( 'Days', 'dt-campaign-generic-porch' ); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-4">
                <div class="wow fadeInUp" data-wow-delay=".6s">
                    <div class="facts-item">
                        <div class="icon">
                            <i class="lnr lnr-user"></i>
                        </div>
                        <div class="fact-count">
                            <h3>720</h3>
                            <h4><?php esc_html_e( 'Hours of Prayer', 'dt-campaign-generic-porch' ); ?></h4>
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
                            <h3>2880</h3>
                            <h4><?php esc_html_e( 'Prayer Commitments Needed', 'dt-campaign-generic-porch' ); ?></h4>
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
            <h2 class="section-title wow fadeIn" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( '15-Minute', 'dt-campaign-generic-porch' ); ?> <span><?php esc_html_e( 'Prayer Fuel', 'dt-campaign-generic-porch' ); ?></span></h2>
            <hr class="lines wow zoomIn" data-wow-delay="0.3s">
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><?php esc_html_e( 'Use these resources to help pray specifically each day for the month of Ramadan', 'dt-campaign-generic-porch' ); ?></p>
            <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s"><a href="/prayer/list" class="btn btn-common btn-rm"><?php esc_html_e( 'View All', 'dt-campaign-generic-porch' ); ?></a></p>
        </div>
    </div>
</section>
<!-- blog Section End -->
