<?php
add_shortcode( 'dt_prayer_timer', 'show_prayer_timer' );

function show_prayer_timer( $color_hex = '#3e729a' ) {
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
                width: 75%;
                height: 51.6%;
                clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
                transform-origin: top;
                margin: 50% 12%;
                position: absolute;
            }

            .prayer-timer-slot-1 {
                transform: rotate(0deg);
                background: <?php echo esc_html( $color_hex ); ?>99;
            }
            .prayer-timer-slot-2 {
                transform: rotate(72deg);
                background: <?php echo esc_html( $color_hex ); ?>CC;
            }
            .prayer-timer-slot-3 {
                transform: rotate(144deg);
                background: <?php echo esc_html( $color_hex ); ?>;
            }
            .prayer-timer-slot-4 {
                transform: rotate(216deg);
                background: <?php echo esc_html( $color_hex ); ?>33;
            }
            .prayer-timer-slot-5 {
                transform: rotate(288deg);
                background: <?php echo esc_html( $color_hex ); ?>66;
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

            .prayer-timer-start-praying {
                position: relative;
                margin: 11.1% 33.3%;
            }

            .prayer-timer-rotate {
                -webkit-transition:-webkit-transform 900s linear;
                transform: rotate(360deg);
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
                <a href="javascript:start_timer();" class="button cp-nav" id="start-praying">Start Praying!</a>
            </div>
        </div>
        <script>
            function start_timer() {
                jQuery( '.prayer-timer-needle' ).attr( 'class', 'prayer-timer-needle prayer-timer-rotate' );
            }
        </script>
    <?php
}