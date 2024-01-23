<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class DT_Campaigns_Ongoing_Shortcode_Display {
    public $parts;
    public function __construct( $magic_link_parts ){
        $this->parts = $magic_link_parts;
    }
    public function body(){
        ( new DT_Generic_Porch_Loader() )->load_porch();
        $atts = $this->parts;
        $atts['rest_url'] = rest_url();
        $atts['color'] = PORCH_COLOR_SCHEME_HEX;
        if ( $atts['color'] === 'preset' || empty( $atts['color'] ) ){
            $atts['color'] = '#4676fa';
        }
        $progress_shortcode = dt_campaigns_build_shortcode_from_array( 'dt-generic-campaign-percentage', $atts );
        $calendar_shortcode = dt_campaigns_build_shortcode_from_array( 'dt-ongoing-campaign-calendar', $atts );
        $sign_up_shortcode = dt_campaigns_build_shortcode_from_array( 'dt-ongoing-campaign-signup', $atts );
        $prayer_timer_shortcode = dt_campaigns_build_shortcode_from_array( 'dt_prayer_timer', [ 'color' => '#3e729a', 'duration' => '15' ] );
        ?>
        <style>
            #dt-ongoing-display {
                max-width:1400px; margin:auto
            }
            #dt-ongoing-display .center {
                text-align: center;
            }
            #dt-ongoing-display .display-section {
                margin-bottom: 150px;;
            }
            #dt-ongoing-display .display-section code {
                text-align: left; display: block; white-space: pre-wrap;
                margin-top:20px;
                background-color: white;
                padding: 20px;
            }
        </style>
        <div id="dt-ongoing-display">
            <div class="center">
                <div class="display-section center">
                    <h1>The Progress</h1>

                        <?php echo do_shortcode( $progress_shortcode ); ?>
                    <code><?php echo esc_html( $progress_shortcode ); ?></code>
                </div>
                <div class="display-section center">
                    <h1>The Calendar</h1>
                    <div style="max-width: 500px" class="center">
                        <?php echo do_shortcode( $calendar_shortcode ); ?>
                    </div>
                    <code><?php echo esc_html( $calendar_shortcode ); ?></code>
                </div>
                <div class="display-section">
                    <h1>The Sign Up Tool</h1>
                    <?php echo do_shortcode( $sign_up_shortcode ); ?>
                    <code><?php echo esc_html( $sign_up_shortcode ); ?></code>
                </div>
                <div class="display-section">
                    <h1>The Prayer Timer</h1>
                    <?php echo do_shortcode( $prayer_timer_shortcode ); ?>
                    <code><?php echo esc_html( $prayer_timer_shortcode ); ?></code>
                    <p style="text-align: start">
                        Optional Parameters:
                    </p>
                    <ul style="text-align: start">
                        <li>color</li>
                        <li>duration: time in minutes</li>
                        <li>language</li>
                    </ul>

                </div>
            </div>
        </div>
        <?php
    }
}
