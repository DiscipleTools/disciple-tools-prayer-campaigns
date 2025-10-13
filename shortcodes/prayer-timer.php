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

            #start-praying:focus {
                outline: none;
            }

            .prayer-timer-now-praying {
                color: #fefefe;
                background-color: gray;
            }


            .prayer-timer-rotate {
                -webkit-transition:-webkit-transform <?php echo esc_html( $prayer_duration_min ) * 60; ?>s linear;
                transform: rotate(360deg);
            }

            .prayer-timer-rotate-paused {
                -webkit-transition: none;
                animation-play-state: paused;
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
                display: flex;
                align-items: center;
                gap: 5px;
            }
            #clock-sticky-button:focus {
                outline: none;
            }
            #clock-sticky-button img {
                height: 16px;
                display: block;
            }
            #clock-sticky-clock img {
                height: 16px;
                display: block;
            }
            #clock-sticky-remaining-time{
                color:white
            }
            .prayer-timer-resume-pulse {
                animation: resumePulse 0.6s ease-in-out;
            }
            @keyframes resumePulse {
                0% { background-color: <?php echo esc_html( $color_hex ); ?>; }
                50% { background-color: #ffffff; color: <?php echo esc_html( $color_hex ); ?>; }
                100% { background-color: <?php echo esc_html( $color_hex ); ?>; }
            }
        </style>

        <span id="clock-sticky" class="clock-sticky">
            <button id="clock-sticky-button" onclick="start_timer()">
                <span id="clock-sticky-remaining-time">
                    <?php echo esc_html( $prayer_duration_min ); ?>:00
                </span>

                <img class="clock-sticky-play-icon" src="<?php echo esc_html( DT_Prayer_Campaigns::get_url_path() . 'assets/play-white.svg' ) ?>"/>
                <span style="display: none"; id="clock-sticky-clock">
                    <img id="clock-sticky-icon" src="<?php echo esc_html( DT_Prayer_Campaigns::get_url_path() . 'assets/clock-white.svg' ) ?>"/>
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
            // Simplified timer variables
            let total_duration = <?php echo esc_html( $prayer_duration_min ) * 60 * 1000; ?>;
            let total_duration_timer = total_duration; // Working timer in milliseconds
            let timer_interval = null;
            let is_paused = false;
            let update_interval = 1000; // Start with 1 second updates
            let ten_seconds_passed = false;
            let slot_timeouts = [];
            let rotation_monitor_interval = null;
            let slots_updated = [false, false, false, false, false]; // Track which slots have been updated

            function updateStickyTimerDisplay() {
                if (total_duration_timer <= 0) {
                    jQuery('#clock-sticky-remaining-time').text('00:00');
                    return;
                }

                let remaining_seconds = Math.floor(total_duration_timer / 1000);
                let minutes = Math.floor(remaining_seconds / 60);
                let seconds = remaining_seconds % 60;

                // Format display: always show MM:SS format
                let display_text = minutes + ':' + (seconds < 10 ? '0' + seconds : seconds);
                jQuery('#clock-sticky-remaining-time').text(display_text);
            }

            function start_timer() {
                // If already running and not paused, do nothing
                if (timer_interval && !is_paused) {
                    return;
                }
                
                // Store pause state before we change it
                let was_paused = is_paused;
                
                // Clear any existing interval
                if (timer_interval) {
                    clearInterval(timer_interval);
                }
                
                // Reset timer if starting fresh
                if (!was_paused) {
                    total_duration_timer = total_duration;
                    ten_seconds_passed = false;
                    update_interval = 1000;
                } else {
                    // Resume: add visual feedback
                    jQuery('#clock-sticky').addClass('prayer-timer-resume-pulse');
                    setTimeout(function() {
                        jQuery('#clock-sticky').removeClass('prayer-timer-resume-pulse');
                    }, 600);
                    
                    // Don't reset slots - they should stay as they were when paused
                    // Don't reset the dial - it should continue from paused position
                }
                
                is_paused = false;
                
                // Update display immediately
                updateStickyTimerDisplay();
                
                // Start the timer interval
                startTimerInterval();

                // Update UI
                jQuery('.clock-sticky-play-icon').hide();
                jQuery('#clock-sticky-clock').show();
                jQuery('#clock-sticky-icon').attr('src', '<?php echo esc_html( DT_Prayer_Campaigns::get_url_path() . 'assets/clock-white.svg' ) ?>');

                let start_praying_button = jQuery('#start-praying');
                start_praying_button.attr('onclick', 'pause_timer()');
                start_praying_button.text('<?php esc_html_e( 'Pause', 'disciple-tools-prayer-campaigns' ); ?>');
                start_praying_button.attr('class', 'prayer-timer-now-praying');
                start_praying_button.blur();

                // Handle dial animation
                if (was_paused) {
                    // Resume: continue from paused position
                    let current_transform = jQuery('.prayer-timer-needle').css('transform');
                    let current_rotation = 0;
                    if (current_transform && current_transform !== 'none') {
                        let matrix = current_transform.match(/matrix\(([^,]+),\s*([^,]+),\s*([^,]+),\s*([^,]+)/);
                        if (matrix) {
                            let a = parseFloat(matrix[1]);
                            let b = parseFloat(matrix[2]);
                            current_rotation = Math.atan2(b, a) * (180 / Math.PI);
                            if (current_rotation < 0) current_rotation += 360;
                        }
                    }
                    
                    jQuery('.prayer-timer-needle').removeClass('prayer-timer-rotate-paused');
                    jQuery('.prayer-timer-needle').css('-webkit-transition', 'none');
                    jQuery('.prayer-timer-needle').css('transform', 'rotate(' + current_rotation + 'deg)');
                    jQuery('.prayer-timer-needle')[0].offsetHeight;
                    jQuery('.prayer-timer-needle').css('-webkit-transition', '-webkit-transform ' + (total_duration_timer / 1000) + 's linear');
                    jQuery('.prayer-timer-needle').css('transform', 'rotate(360deg)');
                } else {
                    // Start fresh
                    jQuery('.prayer-timer-needle').removeClass('prayer-timer-rotate-paused');
                    jQuery('.prayer-timer-needle').css('-webkit-transition', 'none');
                    jQuery('.prayer-timer-needle').css('transform', 'rotate(0deg)');
                    jQuery('.prayer-timer-needle')[0].offsetHeight;
                    jQuery('.prayer-timer-needle').css('-webkit-transition', '-webkit-transform ' + (total_duration / 1000) + 's linear');
                    jQuery('.prayer-timer-needle').css('transform', 'rotate(360deg)');
                }
                
                // Handle slots - only reset when starting fresh
                slot_timeouts.forEach(function(timeout) {
                    clearTimeout(timeout);
                });
                slot_timeouts = [];
                
                if (!was_paused) {
                    // Only reset slots when starting fresh
                    jQuery('.prayer-timer-slot-1').attr('class', 'prayer-timer-slot prayer-timer-slot-1 prayer-timer-slot-1-incomplete');
                    jQuery('.prayer-timer-slot-2').attr('class', 'prayer-timer-slot prayer-timer-slot-2 prayer-timer-slot-2-incomplete');
                    jQuery('.prayer-timer-slot-3').attr('class', 'prayer-timer-slot prayer-timer-slot-3 prayer-timer-slot-3-incomplete');
                    jQuery('.prayer-timer-slot-4').attr('class', 'prayer-timer-slot prayer-timer-slot-4 prayer-timer-slot-4-incomplete');
                    jQuery('.prayer-timer-slot-5').attr('class', 'prayer-timer-slot prayer-timer-slot-5 prayer-timer-slot-5-incomplete');
                }
                
                setupRotationBasedSlots();
            }
            
            function finishTimer() {
                // Clear any existing intervals
                if (timer_interval) {
                    clearInterval(timer_interval);
                    timer_interval = null;
                }
                
                // Clear rotation monitoring
                if (rotation_monitor_interval) {
                    clearInterval(rotation_monitor_interval);
                    rotation_monitor_interval = null;
                }
                
                // Clear slot timeouts
                slot_timeouts.forEach(function(timeout) {
                    clearTimeout(timeout);
                });
                slot_timeouts = [];
                
                // Remove all incomplete classes to show complete prayer cycle
                jQuery('.prayer-timer-slot-1').removeClass('prayer-timer-slot-1-incomplete');
                jQuery('.prayer-timer-slot-2').removeClass('prayer-timer-slot-2-incomplete');
                jQuery('.prayer-timer-slot-3').removeClass('prayer-timer-slot-3-incomplete');
                jQuery('.prayer-timer-slot-4').removeClass('prayer-timer-slot-4-incomplete');
                jQuery('.prayer-timer-slot-5').removeClass('prayer-timer-slot-5-incomplete');
                
                // Set sticky timer to 00:00
                jQuery('#clock-sticky-remaining-time').text('00:00');
                jQuery('#start-praying').text('All done!');
                is_paused = true;
                
                // Ensure total_duration_timer is 0
                total_duration_timer = 0;
            }

            function pause_timer() {
                if (is_paused) {
                    return;
                }

                is_paused = true;
                
                if (timer_interval) {
                    clearInterval(timer_interval);
                    timer_interval = null;
                }

                // Clear rotation monitoring
                if (rotation_monitor_interval) {
                    clearInterval(rotation_monitor_interval);
                    rotation_monitor_interval = null;
                }
                
                // Update sticky timer display one last time before pausing
                updateStickyTimerDisplay();

                // Clear slot timeouts
                slot_timeouts.forEach(function(timeout) {
                    clearTimeout(timeout);
                });
                slot_timeouts = [];

                // Pause the dial animation - capture current position
                let current_transform = jQuery('.prayer-timer-needle').css('transform');
                jQuery('.prayer-timer-needle').css('-webkit-transition', 'none');
                jQuery('.prayer-timer-needle').css('transform', current_transform);
                jQuery('.prayer-timer-needle').attr('class', 'prayer-timer-needle prayer-timer-rotate-paused');

                // Change icon to pause when paused
                jQuery('#clock-sticky-icon').attr('src', '<?php echo esc_html( DT_Prayer_Campaigns::get_url_path() . 'assets/pause-white.svg' ) ?>');

                let start_praying_button = jQuery('#start-praying');
                start_praying_button.attr('onclick', 'start_timer()');
                start_praying_button.text('<?php esc_html_e( 'Resume', 'disciple-tools-prayer-campaigns' ); ?>');
                start_praying_button.blur();
            }

            function setupRotationBasedSlots() {
                // Clear any existing rotation monitoring
                if (rotation_monitor_interval) {
                    clearInterval(rotation_monitor_interval);
                }
                
                // Reset slot tracking
                slots_updated = [false, false, false, false, false];
                
                // Start monitoring rotation every 100ms
                rotation_monitor_interval = setInterval(function() {
                    let current_transform = jQuery('.prayer-timer-needle').css('transform');
                    let current_rotation = 0;
                    
                    if (current_transform && current_transform !== 'none') {
                        // Extract rotation from matrix: matrix(a, b, c, d, tx, ty)
                        let matrix = current_transform.match(/matrix\(([^,]+),\s*([^,]+),\s*([^,]+),\s*([^,]+)/);
                        if (matrix) {
                            let a = parseFloat(matrix[1]);
                            let b = parseFloat(matrix[2]);
                            // Calculate rotation angle from matrix values
                            current_rotation = Math.atan2(b, a) * (180 / Math.PI);
                            if (current_rotation < 0) current_rotation += 360;
                        }
                    }
                    
                    // Update slots based on rotation milestones
                    if (current_rotation >= 72 && !slots_updated[0]) {
                        slots_updated[0] = true;
                        jQuery('.prayer-timer-slot-1').removeClass('prayer-timer-slot-1-incomplete');
                    }
                    
                    if (current_rotation >= 144 && !slots_updated[1]) {
                        slots_updated[1] = true;
                        jQuery('.prayer-timer-slot-1').removeClass('prayer-timer-slot-1-incomplete');
                        jQuery('.prayer-timer-slot-2').removeClass('prayer-timer-slot-2-incomplete');
                    }
                    
                    if (current_rotation >= 216 && !slots_updated[2]) {
                        slots_updated[2] = true;
                        jQuery('.prayer-timer-slot-1').removeClass('prayer-timer-slot-1-incomplete');
                        jQuery('.prayer-timer-slot-2').removeClass('prayer-timer-slot-2-incomplete');
                        jQuery('.prayer-timer-slot-3').removeClass('prayer-timer-slot-3-incomplete');
                    }
                    
                    if (current_rotation >= 288 && !slots_updated[3]) {
                        slots_updated[3] = true;
                        jQuery('.prayer-timer-slot-1').removeClass('prayer-timer-slot-1-incomplete');
                        jQuery('.prayer-timer-slot-2').removeClass('prayer-timer-slot-2-incomplete');
                        jQuery('.prayer-timer-slot-3').removeClass('prayer-timer-slot-3-incomplete');
                        jQuery('.prayer-timer-slot-4').removeClass('prayer-timer-slot-4-incomplete');
                    }
                    
                    if (current_rotation >= 360 && !slots_updated[4]) {
                        slots_updated[4] = true;
                        jQuery('.prayer-timer-slot-1').removeClass('prayer-timer-slot-1-incomplete');
                        jQuery('.prayer-timer-slot-2').removeClass('prayer-timer-slot-2-incomplete');
                        jQuery('.prayer-timer-slot-3').removeClass('prayer-timer-slot-3-incomplete');
                        jQuery('.prayer-timer-slot-4').removeClass('prayer-timer-slot-4-incomplete');
                        jQuery('.prayer-timer-slot-5').removeClass('prayer-timer-slot-5-incomplete');
                        
                        // Clear the monitoring interval when all slots are updated
                        clearInterval(rotation_monitor_interval);
                        rotation_monitor_interval = null;
                    }
                }, 100); // Check every 100ms
            }
            
            function startTimerInterval() {
                // Clear any existing interval
                if (timer_interval) {
                    clearInterval(timer_interval);
                }

                // Check if we're already at a 5-second boundary and past 10 seconds
                let remaining_seconds = Math.floor(total_duration_timer / 1000);
                let elapsed = total_duration - total_duration_timer;
                let shouldUseFiveSecondUpdates = elapsed >= 10000 && (remaining_seconds % 5 === 0);

                if (shouldUseFiveSecondUpdates) {
                    // Use 5-second intervals (only when we're at a 5-second boundary)
                    update_interval = 5000;
                    ten_seconds_passed = true;

                    timer_interval = setInterval(function() {
                        if (!is_paused) {
                            total_duration_timer -= 5000; // Subtract 5 seconds
                            updateStickyTimerDisplay();

                            if (total_duration_timer <= 0) {
                                clearInterval(timer_interval);
                                timer_interval = null;
                                finishTimer();
                            }
                        }
                    }, update_interval);
                } else {
                    // Use 1-second intervals
                    update_interval = 1000;
                    timer_interval = setInterval(function() {
                        if (!is_paused) {
                            total_duration_timer -= 1000; // Subtract 1 second

                            updateStickyTimerDisplay();

                            // Check if we should switch to 5-second updates
                            // Switch when: past 10 seconds AND at a 5-second boundary
                            let elapsed = total_duration - total_duration_timer;
                            let remaining_seconds = Math.floor(total_duration_timer / 1000);
                            if (elapsed >= 10000 && remaining_seconds % 5 === 0) {
                                clearInterval(timer_interval);
                                startTimerInterval(); // Restart with 5-second intervals
                                return;
                            }

                            if (total_duration_timer <= 0) {
                                clearInterval(timer_interval);
                                timer_interval = null;
                                finishTimer();
                            }
                        }
                    }, update_interval);
                }
            }
        </script>
    <?php
    return ob_get_clean();
}
