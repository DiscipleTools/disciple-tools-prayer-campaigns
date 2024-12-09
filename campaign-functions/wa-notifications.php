<?php
/**
 * Integrate with the twilio plugin to send whatsapp notifications
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Prayer_Campaign_WhatsApp_Notifications {
    public function __construct(){
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 10, 2 );
        add_action( 'dt_prayer_campaign_prayer_time_reminder', [ $this, 'dt_prayer_campaign_prayer_time_reminder' ], 10, 3 );
        add_filter( 'dt_twilio_messaging_templates', [ $this, 'dt_twilio_messaging_templates' ], 10, 1 );
        add_action( 'campaign_subscription_activated', [ $this, 'campaign_subscription_activated' ], 10, 1 );
        add_filter( 'dt_subscription_update_profile', [ $this, 'dt_subscription_update_profile' ], 10, 3 );
        //rest endpoints
        add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
    }

    public function rest_api_init(){
        register_rest_route( 'dt-public/dt-campaigns/v1', '/webhook/', [
            'methods' => 'POST',
            'callback' => [ $this, 'wa_callback' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function wa_callback( WP_REST_Request $request ){
        $params = $request->get_params();
        dt_write_log( $params );
        $params = dt_recursive_sanitize_array( $params );
        $headers = $request->get_headers();
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === 'subscriptions' ){
            $fields['whatsapp_number'] = [
                'type' => 'text',
                'name' => 'WhatsApp # for Notifications',
                'description' => 'Include a country code like +33',
                'tile' => 'signup_form',
                'default' => '',
                'hidden' => true,
            ];
            $fields['whatsapp_number_verified'] = [ //@todo made this a text field with the number
                'type' => 'checkbox',
                'name' => 'WhatsApp # Verified',
                'tile' => 'details',
                'default' => false,
                'hidden' => true,
            ];
        }
        return $fields;
    }

    public function dt_twilio_messaging_templates( $templates ){
        $templates['prayer_fuel_notification'] = [
            'id' => 'prayer_fuel_notification',
            'name' => 'Prayer Fuel Notifications',
            'type' => 'whatsapp',
            'enabled' => true,
            'content_category' => 'UTILITY',
            'content_template' => [
                'friendly_name' => 'Prayer Fuel Notifications',
                'language' => 'en',
                'variables' => [
                    '1' => 'https://ramadandemo.pray4movement.org/list',
                ],
                'types' => [
                    'twilio/text' => [
                        'body' => 'Your prayer time is approaching. Here is a link to the prayer fuel: {{1}}.\n\nThank you for praying with us.'
                    ]
                ]
            ]
        ];
        $templates['confirm_wa_number'] = [
            'id' => 'confirm_wa_number',
            'name' => 'Confirm WhatsApp Number',
            'type' => 'whatsapp',
            'enabled' => true,
            'content_category' => 'UTILITY',
            'content_template' => [
                'friendly_name' => 'Confirm WhatsApp Number',
                'language' => 'en',
                'types' => [
                    'twilio/quick-reply' => [
                        'body' => 'Please confirm that you would like to receive prayer fuel notifications via WhatsApp.',
                        'actions' => [
                            [ 'id' => 'confirm', 'title' => 'Confirm' ],
                        ]
                    ]
                ]
            ]
        ];
        return $templates;
    }

    public function dt_prayer_campaign_prayer_time_reminder( $subscriber, $reports, $campaign_id ){
        if ( empty( $subscriber['whatsapp_number'] ) ){
            return;
        }
        $phone_number = $subscriber['whatsapp_number'];
        $prayer_fuel_link = DT_Prayer_Campaigns_Send_Email::prayer_fuel_link( $subscriber, $campaign_id );

        //sort reports by time ascending
        usort( $reports, function( $a, $b ){
            return $a['time_begin'] <=> $b['time_begin'];
        } );

        $prayer_fuel_time = $reports[0]['time_begin'];
        $fifteen_mints_before = $prayer_fuel_time - MINUTE_IN_SECONDS * 15;
        $now = time();
        $from_now = $fifteen_mints_before - $now;

        if ( $prayer_fuel_time < $now + MINUTE_IN_SECONDS * 20 ){
            self::send_whatsapp_notification( $phone_number, $prayer_fuel_link, $subscriber['ID'] );
        } else {
            wp_queue()->push( new Prayer_Campaign_WhatsApp_Notifications_Job( $phone_number, $prayer_fuel_link, $subscriber['ID'] ), $from_now );
        }
    }
    public static function send_whatsapp_notification( $phone_number, $prayer_fuel_link, $subscriber_id = null ){
        $sent = Disciple_Tools_Twilio_API::send_dt_notification_template(
            $phone_number,
            [
                '1' => $prayer_fuel_link,
            ],
            'prayer_fuel_notification',
        );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send whatsapp to ' . $phone_number );
        }
        DT_Posts::add_post_comment( 'subscriptions', $subscriber_id, 'Message sent to verify WhatsApp Number', 'comment', [], false, true );
        return $sent;
    }

    /**
     * Send a message to the subscriber to confirm their whatsapp number
     * @param  $subscriber_id
     * @return void
     */
    public static function campaign_subscription_activated( $params ){
        $subscriber = DT_Posts::get_post( 'subscriptions', $params['id'], true, false );
        if ( empty( $subscriber['whatsapp_number'] ) || !empty( $subscriber['whatsapp_number_verified'] ) ){
            return;
        }
        $callback_url = get_rest_url() . 'dt-public/dt-campaigns/v1/webhook/';
        $phone_number = $subscriber['whatsapp_number'];
        $sent = Disciple_Tools_Twilio_API::send_dt_notification_template(
            $phone_number,
            [],
            'confirm_wa_number',
            $callback_url
        );
        if ( ! $sent ){
            dt_write_log( __METHOD__ . ': Unable to send whatsapp verification to ' . $phone_number );
            return;
        }
        DT_Posts::add_post_comment( 'subscriptions', $params['id'], 'Message sent to verify WhatsApp Number', 'comment', [], false, true );
    }

    public static function dt_subscription_update_profile( $updates, $subscriber_id, $new_values ){
        if ( !empty( $new_values['whatsapp_number'] ) ){
            $updates['whatsapp_number'] = $new_values['whatsapp_number'];
            //@todo verify the new number
        }
        return $updates;
    }
}
new Prayer_Campaign_WhatsApp_Notifications();

use WP_Queue\Job;
class Prayer_Campaign_WhatsApp_Notifications_Job extends Job {
    public $phone_number;
    public $prayer_fuel_link;
    public $subscriber_id;
    public function __construct( $phone_number, $prayer_fuel_link, $subscriber_id ){
        $this->phone_number = $phone_number;
        $this->prayer_fuel_link = $prayer_fuel_link;
        $this->subscriber_id = $subscriber_id;
    }
    public function handle(){
        Prayer_Campaign_WhatsApp_Notifications::send_whatsapp_notification( $this->phone_number, $this->prayer_fuel_link, $this->subscriber_id );
    }
}
