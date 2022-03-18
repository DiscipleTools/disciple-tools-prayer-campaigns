<?php
add_shortcode( 'dt_prayer_timer', 'show_prayer_timer' );

function show_prayer_timer( $atts ) {
    ob_start();

    $atts = shortcode_atts( [
        'color'     => '#3e729a',
        'duration'  => 15
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
        </style>
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
                    <?php
                        echo '(';
                        esc_html_e( 'Prayer time', 'disciple-tools-prayer-campaigns' );
                        echo esc_html( ": $prayer_duration_min " );
                        esc_html_e( 'min', 'disciple-tools-prayer-campaigns' );
                        echo ')';
                    ?>
                </p>
            </div>
        </div>
        <script>
            function start_timer() {
                var start_praying_button = jQuery( '#start-praying' )

                start_praying_button.attr( 'onclick', 'javascript:void();' );
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
