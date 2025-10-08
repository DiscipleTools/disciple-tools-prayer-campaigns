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
            }
            #clock-sticky-button:focus {
                outline: none;
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
            let end_of_time = null;
            let timer_interval = null;
            let is_paused = false;
            let total_duration = <?php echo esc_html( $prayer_duration_min ) * 60 * 1000; ?>;
            let start_time = null;
            let update_interval = 1000; // Start with 1 second updates
            let ten_seconds_passed = false;
            let dial_start_time = null;
            let slot_timeouts = [];
            let rotation_monitor_interval = null;
            let slots_updated = [false, false, false, false, false]; // Track which slots have been updated
            let remaining_time_when_paused = 0; // Track remaining time when paused
            let use_five_second_updates = false; // Flag to force 5-second updates after resume

            function updateStickyTimerDisplay() {
                if (is_paused) {
                    // When paused, use the stored remaining time
                    if (remaining_time_when_paused > 0) {
                        let remaining_seconds = Math.floor(remaining_time_when_paused / 1000);
                        let minutes = Math.floor(remaining_seconds / 60);
                        let seconds = remaining_seconds % 60;
                        
                        // Enforce 5-second stepping when paused if 10+ seconds have elapsed
                        if (ten_seconds_passed || (total_duration - remaining_time_when_paused) >= 10000) {
                            seconds = Math.floor(seconds / 5) * 5;
                        }
                        
                        // Format display: always show MM:SS format
                        let display_text = minutes + ':' + (seconds < 10 ? '0' + seconds : seconds);
                        jQuery('#clock-sticky-remaining-time').text(display_text);
                    }
                    return;
                }
                
                let time = new Date().getTime();
                let remaining_ms = end_of_time - time;
                
                if (remaining_ms > 0) {
                    let remaining_seconds = Math.floor(remaining_ms / 1000);
                    let minutes = Math.floor(remaining_seconds / 60);
                    let seconds = remaining_seconds % 60;
                    
                    // Enforce 5-second stepping after 10 seconds
                    if (ten_seconds_passed || (total_duration - remaining_ms) >= 10000) {
                        seconds = Math.floor(seconds / 5) * 5;
                    }
                    
                    // Format display: always show MM:SS format
                    let display_text = minutes + ':' + (seconds < 10 ? '0' + seconds : seconds);
                    jQuery('#clock-sticky-remaining-time').text(display_text);
                }
            }

            function start_timer() {
                if ( end_of_time && !is_paused ) {
                    return;
                }
                if (is_paused) {
                    // Resume: restart the clock with remaining time
                    let elapsed_time = new Date().getTime() - start_time;
                    let remaining_time = total_duration - elapsed_time;
                    
                    // Restart the clock
                    start_time = new Date().getTime();
                    total_duration = remaining_time;
                    end_of_time = new Date().getTime() + remaining_time;
                    ten_seconds_passed = false;
                    update_interval = 1000;
                } else {
                    // Start fresh
                    end_of_time = new Date().getTime() + total_duration + 1000; // Add 1-second buffer
                    start_time = new Date().getTime();
                    ten_seconds_passed = false;
                    update_interval = 1000;
                }

                // Clear any existing interval
                if (timer_interval) {
                    clearInterval(timer_interval);
                }

                // Update display immediately to fix initial delay
                updateStickyTimerDisplay();

                timer_interval = setInterval( function() {
                    if (is_paused) {
                        return; // Don't update when paused
                    }
                    
                    let time = new Date().getTime();
                    let remaining_ms = end_of_time - time;
                    
                    if ( remaining_ms > 0 ) {
                        let remaining_seconds = Math.floor(remaining_ms / 1000);
                        let minutes = Math.floor(remaining_seconds / 60);
                        let seconds = remaining_seconds % 60;
                        
                        // Enforce 5-second stepping after 10 seconds
                        if (ten_seconds_passed || (total_duration - remaining_ms) >= 10000) {
                            // Quantize seconds to nearest lower multiple of 5
                            seconds = Math.floor(seconds / 5) * 5;
                        }
                        
                        // Check if we should switch to 5-second updates
                        if ((!ten_seconds_passed && (total_duration - remaining_ms) >= 9000) || use_five_second_updates) {
                            ten_seconds_passed = true;
                            clearInterval(timer_interval);
                            update_interval = 5000;
                            
                            // Snap to 5-second boundary when switching
                            let current_time = new Date().getTime();
                            let elapsed = total_duration - remaining_ms;
                            let snapped_elapsed = Math.floor(elapsed / 5000) * 5000;
                            let adjustment = elapsed - snapped_elapsed;
                            end_of_time = current_time + remaining_ms + adjustment;
                            
                            timer_interval = setInterval(arguments.callee, update_interval);
                        }
                        
                        // Format display: always show MM:SS format
                        let display_text = minutes + ':' + (seconds < 10 ? '0' + seconds : seconds);
                        
                        jQuery('#clock-sticky-remaining-time').text(display_text);
                    }
                }, update_interval);

                jQuery('.clock-sticky-play-icon').hide();
                jQuery('#clock-sticky-clock').show();
                
                // Change icon to clock when running
                jQuery('#clock-sticky-icon').attr('src', '<?php echo esc_html( DT_Prayer_Campaigns::get_url_path() . 'assets/clock-white.svg' ) ?>');

                let start_praying_button = jQuery( '#start-praying' );

                start_praying_button.attr( 'onclick', 'pause_timer()' );
                start_praying_button.text( '<?php esc_html_e( 'Pause', 'disciple-tools-prayer-campaigns' ); ?>');
                start_praying_button.attr('class', 'prayer-timer-now-praying');
                start_praying_button.blur();

                // Handle dial animation
                if (is_paused) {
                    // Resume: continue from paused position
                    // Get the current transform (which was set during pause)
                    let current_transform = jQuery( '.prayer-timer-needle' ).css('transform');
                    
                    // Parse the rotation angle from the transform matrix
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
                    
                    // Remove paused class and reset transition
                    jQuery( '.prayer-timer-needle' ).removeClass('prayer-timer-rotate-paused');
                    jQuery( '.prayer-timer-needle' ).css('-webkit-transition', 'none');
                    jQuery( '.prayer-timer-needle' ).css('transform', 'rotate(' + current_rotation + 'deg)');
                    
                    // Force a reflow
                    jQuery( '.prayer-timer-needle' )[0].offsetHeight;
                    
                    // Continue animation from current position to 360 degrees
                    jQuery( '.prayer-timer-needle' ).css('-webkit-transition', '-webkit-transform ' + (total_duration / 1000) + 's linear');
                    jQuery( '.prayer-timer-needle' ).css('transform', 'rotate(360deg)');
                } else {
                    // Start fresh: begin from 0 degrees
                    dial_start_time = new Date().getTime();
                    jQuery( '.prayer-timer-needle' ).removeClass('prayer-timer-rotate-paused');
                    jQuery( '.prayer-timer-needle' ).css('-webkit-transition', 'none');
                    jQuery( '.prayer-timer-needle' ).css('transform', 'rotate(0deg)');
                    
                    // Force a reflow
                    jQuery( '.prayer-timer-needle' )[0].offsetHeight;
                    
                    // Start the animation
                    jQuery( '.prayer-timer-needle' ).css('-webkit-transition', '-webkit-transform ' + (total_duration / 1000) + 's linear');
                    jQuery( '.prayer-timer-needle' ).css('transform', 'rotate(360deg)');
                }
                
                // Clear the pause flag after handling resume
                if (is_paused) {
                    is_paused = false;
                    
                    // Add visual indication for resume
                    jQuery('#clock-sticky').addClass('prayer-timer-resume-pulse');
                    setTimeout(function() {
                        jQuery('#clock-sticky').removeClass('prayer-timer-resume-pulse');
                    }, 600);
                    
                    // Update end_of_time based on remaining time when paused
                    if (remaining_time_when_paused > 0) {
                        end_of_time = new Date().getTime() + remaining_time_when_paused;
                        remaining_time_when_paused = 0; // Reset
                    }
                    
                    // Always use 5-second updates after resume for better UX
                    use_five_second_updates = true;
                    ten_seconds_passed = true;
                    update_interval = 5000;
                    
                    // Clear existing interval and restart with 5-second updates
                    if (timer_interval) {
                        clearInterval(timer_interval);
                    }
                    
                    // Snap to 5-second boundary on resume
                    let current_time = new Date().getTime();
                    let remaining_ms = end_of_time - current_time;
                    let remaining_seconds = Math.floor(remaining_ms / 1000);
                    let snapped_seconds = Math.floor(remaining_seconds / 5) * 5;
                    let adjustment = (remaining_seconds - snapped_seconds) * 1000;
                    end_of_time = current_time + (snapped_seconds * 1000);
                    
                    timer_interval = setInterval(function() {
                        if (is_paused) {
                            return; // Don't update when paused
                        }
                        
                        let time = new Date().getTime();
                        let remaining_ms = end_of_time - time;
                        
                        if (remaining_ms > 0) {
                            let remaining_seconds = Math.floor(remaining_ms / 1000);
                            let minutes = Math.floor(remaining_seconds / 60);
                            let seconds = remaining_seconds % 60;
                            
                            // Enforce 5-second stepping after resume
                            seconds = Math.floor(seconds / 5) * 5;
                            
                            // Format display: always show MM:SS format
                            let display_text = minutes + ':' + (seconds < 10 ? '0' + seconds : seconds);
                            jQuery('#clock-sticky-remaining-time').text(display_text);
                        }
                    }, update_interval);
                    
                    // Immediately update the sticky timer display when resuming
                    updateStickyTimerDisplay();
                }
                
                // Handle slots - rotation-based approach
                // Clear any existing slot timeouts
                slot_timeouts.forEach(function(timeout) {
                    clearTimeout(timeout);
                });
                slot_timeouts = [];
                
                // Set all slots to incomplete
                jQuery( '.prayer-timer-slot-1').attr( 'class', 'prayer-timer-slot prayer-timer-slot-1 prayer-timer-slot-1-incomplete');
                jQuery( '.prayer-timer-slot-2').attr( 'class', 'prayer-timer-slot prayer-timer-slot-2 prayer-timer-slot-2-incomplete');
                jQuery( '.prayer-timer-slot-3').attr( 'class', 'prayer-timer-slot prayer-timer-slot-3 prayer-timer-slot-3-incomplete');
                jQuery( '.prayer-timer-slot-4').attr( 'class', 'prayer-timer-slot prayer-timer-slot-4 prayer-timer-slot-4-incomplete');
                jQuery( '.prayer-timer-slot-5').attr( 'class', 'prayer-timer-slot prayer-timer-slot-5 prayer-timer-slot-5-incomplete');
                
                // Start rotation-based slot monitoring
                setupRotationBasedSlots();

                setTimeout( function() {
                    // Remove all incomplete classes to show complete prayer cycle
                    jQuery( '.prayer-timer-slot-1').removeClass('prayer-timer-slot-1-incomplete');
                    jQuery( '.prayer-timer-slot-2').removeClass('prayer-timer-slot-2-incomplete');
                    jQuery( '.prayer-timer-slot-3').removeClass('prayer-timer-slot-3-incomplete');
                    jQuery( '.prayer-timer-slot-4').removeClass('prayer-timer-slot-4-incomplete');
                    jQuery( '.prayer-timer-slot-5').removeClass('prayer-timer-slot-5-incomplete');
                    
                    // Set sticky timer to 00:00
                    jQuery('#clock-sticky-remaining-time').text('00:00');
                    
                    jQuery( '#start-praying' ).text( 'All done!' );

                    is_paused = true; // Set the timer to paused

                }, <?php echo esc_html( $prayer_duration_min ) * 60 * 1000; ?> );
            }

            function pause_timer() {
                if (is_paused) {
                    return;
                }

                paused_time = new Date().getTime();
                
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
                
                // Capture remaining time when pausing
                let time = new Date().getTime();
                remaining_time_when_paused = end_of_time - time;
                
                // Update sticky timer display one last time before pausing
                updateStickyTimerDisplay();

                // Clear slot timeouts
                slot_timeouts.forEach(function(timeout) {
                    clearTimeout(timeout);
                });
                slot_timeouts = [];

                // Pause the dial animation - capture current position
                let current_transform = jQuery( '.prayer-timer-needle' ).css('transform');
                jQuery( '.prayer-timer-needle' ).css('-webkit-transition', 'none');
                jQuery( '.prayer-timer-needle' ).css('transform', current_transform);
                jQuery( '.prayer-timer-needle' ).attr( 'class', 'prayer-timer-needle prayer-timer-rotate-paused' );

                // Change icon to pause when paused
                jQuery('#clock-sticky-icon').attr('src', '<?php echo esc_html( DT_Prayer_Campaigns::get_url_path() . 'assets/pause-white.svg' ) ?>');

                let start_praying_button = jQuery( '#start-praying' );
                start_praying_button.attr( 'onclick', 'start_timer()' );
                start_praying_button.text( '<?php esc_html_e( 'Resume', 'disciple-tools-prayer-campaigns' ); ?>');
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
        </script>
    <?php
    return ob_get_clean();
}
