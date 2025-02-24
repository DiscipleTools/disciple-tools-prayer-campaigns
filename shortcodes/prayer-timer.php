<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'dt_prayer_timer', 'show_prayer_timer' );

function show_prayer_timer( $atts ) {
    ob_start();

    $atts = shortcode_atts( [
        'color'     => '#3e729a',
        'duration'  => 15,
        'lang' => 'en_US'
    ], $atts, '' );
    $color_hex = '#3e729a';
    $prayer_duration_min = 15;

    if ( isset( $atts ) ) {
        if ( isset( $atts['color'] ) ) {
            $color_hex = $atts['color'];
        }

        if ( isset( $atts['duration'] ) ) {
            $prayer_duration_min = (float) $atts['duration'];
        }
        if ( isset( $atts['lang'] ) && $atts['lang'] !== 'en_US' ){
            add_filter( 'determine_locale', function ( $locale ) use ( $atts ){
                $lang_code = sanitize_text_field( wp_unslash( $atts['lang'] ) );
                if ( !empty( $lang_code ) ){
                    return $lang_code;
                }
                return $locale;
            } );
            dt_campaign_reload_text_domain();
        }
    }
    wp_enqueue_script( 'jquery' );
    ?>
        <style>
            .prayer-timer-container {
                text-align: center;
                margin-top: 25px;
            }

            .prayer-timer-clock {
                width: 256px;
                height: 256px;
                background-color: <?php echo esc_html( $color_hex ); ?>AD;
                border-radius: 50%;
                border: 2.5px solid black;
                display: inline-block;
                overflow: hidden;
                position: relative;
                text-align: center;
            }

            .prayer-timer-slot {
                width: 76%;
                height: 51.6%;
                clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
                transform-origin: top;
                margin: 50% 12%;
                position: absolute;
            }

            .prayer-timer-slot-1 {
                transform: rotate(216deg);
                background-color: <?php echo esc_html( $color_hex ); ?>33;
            }
            .prayer-timer-slot-2 {
                transform: rotate(288deg);
                background-color: <?php echo esc_html( $color_hex ); ?>66;
            }
            .prayer-timer-slot-3 {
                transform: rotate(0deg);
                background-color: <?php echo esc_html( $color_hex ); ?>99;
            }
            .prayer-timer-slot-4 {
                transform: rotate(72deg);
                background-color: <?php echo esc_html( $color_hex ); ?>CC;
            }
            .prayer-timer-slot-5 {
                transform: rotate(144deg);
                background-color: <?php echo esc_html( $color_hex ); ?>;
            }

            .prayer-timer-slot-1-incomplete {
                opacity: 100;
                background-color: #868b89;
            }

            .prayer-timer-slot-2-incomplete {
                opacity: 100;
                background-color: #7e8280;
            }

            .prayer-timer-slot-3-incomplete {
                opacity: 100;
                background-color: #787b7a;
            }

            .prayer-timer-slot-4-incomplete {
                opacity: 100;
                background-color: #727372;
            }

            .prayer-timer-slot-5-incomplete {
                opacity: 100;
                background-color: #6c6c6c;
            }

            .prayer-timer-needle {
                position: absolute;
                width: 1.25%;
                height: 45%;
                background: black;
                margin: 49%;
                z-index: 1;
                transform-origin: bottom;
                top: -44%;
                border-radius: 50px;
                left: 1.3px;
            }

            .prayer-timer-button-container {
                width: 100%;
                margin-top: 25px;
            }

            #start-praying {
                color: #fefefe;
                background-color: <?php echo esc_html( $color_hex ); ?>;
                border: none;
                border-radius: 5px;
                height: 3rem;
                cursor: pointer;
                font-size: .9rem;
                line-height: 1;
                padding: .85em 1em;
                text-align: center;
            }

            #start-praying:hover {
                color: #fefefe;
                background-color: <?php echo esc_html( $color_hex ); ?>;
                border: none;
                filter: brightness(0.9);
            }

            .prayer-timer-now-praying {
                color: #fefefe;
                background-color: gray;
            }


            .prayer-timer-rotate {
                -webkit-transition:-webkit-transform <?php echo esc_html( $prayer_duration_min ) * 60; ?>s linear;
                transform: rotate(360deg);
            }

            .prayer-timer-duration-label {
                margin-top: 1.6rem;
                color: gray;
                font-style: italic;
            }

            #clock-sticky {
                position: fixed;
                top: 75px;
                right:0;
                padding: 5px 10px;
                border-radius: 2px;
                z-index: 100;

                color: #fefefe;
                background-color: <?php echo esc_html( $color_hex ); ?>;
            }
            #clock-sticky-button {
                border: none;
                background: none;
                cursor: pointer;
                padding: 0;
                margin: 0;
            }
            #clock-sticky-button img {
                height: 20px;
            }
            #clock-sticky-clock img {
                height: 20px;
            }
            #clock-sticky-remaining-time{
                color:white
            }
        </style>

        <span id="clock-sticky" class="clock-sticky">
            <button id="clock-sticky-button" onclick="start_timer()">
                <span id="clock-sticky-remaining-time">
                    <?php echo esc_html( $prayer_duration_min ); ?>:00
                </span>

                <img class="clock-sticky-play-icon" src="<?php echo esc_html( DT_Prayer_Campaigns::get_url_path() . 'assets/play-white.svg' ) ?>"/>
                <span style="display: none"; id="clock-sticky-clock">
                    <img src="<?php echo esc_html( DT_Prayer_Campaigns::get_url_path() . 'assets/clock-white.svg' ) ?>"/>
                </span>
            </button>
        </span>

        <div class="prayer-timer-container">
            <div class="prayer-timer-clock">
                <div class="prayer-timer-needle" id="needle" data-degrees="0"></div>
                <div class="prayer-timer-slot prayer-timer-slot-1"></div>
                <div class="prayer-timer-slot prayer-timer-slot-2"></div>
                <div class="prayer-timer-slot prayer-timer-slot-3"></div>
                <div class="prayer-timer-slot prayer-timer-slot-4"></div>
                <div class="prayer-timer-slot prayer-timer-slot-5"></div>
            </div>
            <div class="prayer-timer-button-container">
                <button onclick="javascript:start_timer();" id="start-praying">
                    <?php esc_html_e( 'Start Praying!', 'disciple-tools-prayer-campaigns' ); ?>
                </button>
                <p class="prayer-timer-duration-label">
                    (<?php echo esc_html( sprintf( _x( 'Prayer time: %s min', 'Prayer time: 15 min', 'disciple-tools-prayer-campaigns' ), $prayer_duration_min ) ); ?>)
                </p>
            </div>
        </div>
        <script>
            let end_of_time = null;
            function start_timer() {
                if ( end_of_time ) {
                    return;
                }

                end_of_time = new Date().getTime() + <?php echo esc_html( $prayer_duration_min ) * 60 * 1000; ?>;
                setInterval( function() {
                    let time = new Date().getTime() - 1000;
                    if ( time < end_of_time ) {
                        seconds = Math.floor( (( end_of_time - time ) / 1000) % 60 )
                        jQuery('#clock-sticky-remaining-time').text( Math.floor( (( end_of_time - time ) / 1000 /60) % 60 ) + ':' +  ( seconds < 10 ? '0' + seconds : seconds ) )
                    }
                }, 5000)
                jQuery('.clock-sticky-play-icon').hide();
                jQuery('#clock-sticky-clock').show();

                let start_praying_button = jQuery( '#start-praying' )

                start_praying_button.attr( 'onclick', '' );
                start_praying_button.text( '<?php esc_html_e( 'Now praying...', 'disciple-tools-prayer-campaigns' ); ?>');
                start_praying_button.attr('class', 'prayer-timer-now-praying');
                start_praying_button.blur()

                jQuery( '.prayer-timer-needle' ).attr( 'class', 'prayer-timer-needle prayer-timer-rotate' );
                jQuery( '.prayer-timer-slot-1').attr( 'class', 'prayer-timer-slot prayer-timer-slot-1 prayer-timer-slot-1-incomplete');
                jQuery( '.prayer-timer-slot-2').attr( 'class', 'prayer-timer-slot prayer-timer-slot-2 prayer-timer-slot-2-incomplete');
                jQuery( '.prayer-timer-slot-3').attr( 'class', 'prayer-timer-slot prayer-timer-slot-3 prayer-timer-slot-3-incomplete');
                jQuery( '.prayer-timer-slot-4').attr( 'class', 'prayer-timer-slot prayer-timer-slot-4 prayer-timer-slot-4-incomplete');
                jQuery( '.prayer-timer-slot-5').attr( 'class', 'prayer-timer-slot prayer-timer-slot-5 prayer-timer-slot-5-incomplete');
                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-1').attr( 'class', 'prayer-timer-slot prayer-timer-slot-1');
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5; ?> );

                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-2').attr( 'class', 'prayer-timer-slot prayer-timer-slot-2');
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5 * 2; ?> );

                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-3').attr( 'class', 'prayer-timer-slot prayer-timer-slot-3');
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5 * 3; ?> );

                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-4').attr( 'class', 'prayer-timer-slot prayer-timer-slot-4');
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5 * 4; ?> );

                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-5').attr( 'class', 'prayer-timer-slot prayer-timer-slot-5');
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5 * 5; ?> );

                setTimeout( function() {
                    jQuery( '#start-praying' ).text( 'All done!' );
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000; ?> );
            }
        </script>
    <?php
    return ob_get_clean();
}
