<?php
add_shortcode( 'dt_prayer_timer', 'show_prayer_timer' );

function show_prayer_timer( $color_hex = '#3e729a', $prayer_duration_min = 15 ) {
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

            .prayer-timer-slot-complete {
                opacity: 100;
                background-color: gray;
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
            }

            .prayer-timer-button-container {
                width: 100%;
                margin-top: 25px;
            }

            .prayer-timer-start-praying-button {
                color: #fefefe;
                background-color: <?php echo esc_html( $color_hex ); ?>;
                border-radius: 5px;
                height: 3rem;
                cursor: pointer;
                font-size: .9rem;
                line-height: 1;
                padding: .85em 1em;
                text-align: center;
            }

            .prayer-timer-now-praying {
                color: #fefefe;
                background-color: gray;
            }

            .prayer-timer-start-praying-button:hover {
                color: #fefefe;
                filter: brightness(0.9);
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
                <a href="javascript:start_timer();" class="prayer-timer-start-praying-button" id="start-praying">Start Praying!</a>
                <p class="prayer-timer-duration-label">(Prayer time: <?php echo esc_html( $prayer_duration_min ); ?> min)</p>
            </div>
        </div>
        <script>
            function start_timer() {
                var start_praying_button = jQuery( '#start-praying' )
                
                start_praying_button.attr( 'href', 'javascript:void();' );
                start_praying_button.text( 'Now praying...');
                start_praying_button.attr('class', 'prayer-timer-start-praying-button prayer-timer-now-praying');
                start_praying_button.blur()
                
                jQuery( '.prayer-timer-needle' ).attr( 'class', 'prayer-timer-needle prayer-timer-rotate' );
                
                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-1').attr( 'class', 'prayer-timer-slot prayer-timer-slot-1 prayer-timer-slot-complete');                    
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5; ?> );

                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-2').attr( 'class', 'prayer-timer-slot prayer-timer-slot-2 prayer-timer-slot-complete');                    
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5 * 2; ?> );

                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-3').attr( 'class', 'prayer-timer-slot prayer-timer-slot-3 prayer-timer-slot-complete');                    
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5 * 3; ?> );

                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-4').attr( 'class', 'prayer-timer-slot prayer-timer-slot-4 prayer-timer-slot-complete');                    
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5 * 4; ?> );

                setTimeout( function() {
                    jQuery( '.prayer-timer-slot-5').attr( 'class', 'prayer-timer-slot prayer-timer-slot-5 prayer-timer-slot-complete');                    
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000 / 5 * 5; ?> );

                setTimeout( function() {
                    jQuery( '#start-praying' ).text( 'All done!' );
                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000; ?> );
            }
        </script>
    <?php
}