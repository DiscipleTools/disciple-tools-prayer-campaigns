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

        add_action( 'dt_twilio_message_received', [ $this, 'wa_callback' ], 10, 2 );
        add_filter( 'campaign_create_subscriber_fields', [ $this, 'campaign_create_subscriber_fields' ], 10, 1 );

        add_filter( 'dt_post_update_fields', [ $this, 'dt_post_update_fields' ], 10, 3 );
    }

    public function dt_post_update_fields( $fields, $post_type, $post_id ){
        if ( $post_type !== 'subscriptions' ){
            return $fields;
        }
        if ( !empty( $fields['whatsapp_number'] ) ){
            $fields['whatsapp_number'] = campaigns_validate_and_format_phone( $fields['whatsapp_number'], true );
        }

        return $fields;
    }



    /**
     * Callback for when a message is received
     * @param $type
     * @param $params
     * @return bool
     */
    public function wa_callback( $type, $params ){
        dt_write_log( $params );
        if ( $type !== 'whatsapp' || empty( $params['Body'] ) ){
            return false;
        }
        $phone_number = str_replace( 'whatsapp:', '', $params['From'] );
        //find subscribers
        $subscribers = DT_Posts::list_posts( 'subscriptions', [ 'whatsapp_number' => [ $phone_number ] ], false );
        if ( is_wp_error( $subscribers ) || empty( $subscribers['posts'] ) ){
            dt_write_log( $subscribers );
            dt_write_log( __METHOD__ . ': Unable to find subscriber with phone number ' . $phone_number );
            return false;
        }

        if ( strtolower( $params['Body'] ) === 'confirm'
            || ( !empty( $params['ButtonPayload'] ) && strtolower( $params['ButtonPayload'] ) === 'confirm' ) ){
            //set whatsapp_number_verified to true
            foreach ( $subscribers['posts'] as $subscriber ){
                if ( empty( $subscriber['whatsapp_number'] ) || !empty( $subscriber['whatsapp_number_verified'] ) ){
                    continue;
                }
                $update = DT_Posts::update_post( 'subscriptions', $subscriber['ID'], [ 'whatsapp_number_verified' => true ], true, false );
                if ( is_wp_error( $update ) ){
                    dt_write_log( __METHOD__ . ': Unable to update subscriber with phone number ' . $phone_number );
                }
            }
            return true;
        } else {
            //record the comment on the subscriber and @mention the admin
            $base_user_id = dt_get_base_user( true );
            $comment_text = dt_get_user_mention_syntax( $base_user_id ) . ', ';
            $comment_text .= wp_kses_post( $params['Body'] );
            foreach ( $subscribers['posts'] as $subscriber ){
                DT_Posts::add_post_comment( 'subscriptions', $subscriber['ID'], $comment_text, 'comment', [], false, false );
            }
        }

        return true;
    }

    public function dt_custom_fields_settings( $fields, $post_type ){
        if ( $post_type === 'subscriptions' ){
            $fields['whatsapp_number'] = [
                'type' => 'text',
                'name' => 'WhatsApp # for Prayer Time Reminders (beta)',
                'description' => 'Include a country code like +33',
                'tile' => 'signup_form',
                'default' => '',
                'hidden' => true,
            ];
            $fields['whatsapp_number_verified'] = [
                'type' => 'boolean',
                'name' => 'WhatsApp # Verified',
                'tile' => 'details',
                'default' => false,
                'hidden' => false,
            ];
        }
        return $fields;
    }

    public function dt_twilio_messaging_templates( $templates ){
        $templates['prayer_time_notification'] = [
            'id' => 'prayer_time_notification',
            'name' => 'Prayer Time Notification',
            'type' => 'whatsapp',
            'enabled' => true,
            'content_category' => 'UTILITY',
            'content_template' => [
                'friendly_name' => 'Prayer Time Notification',
                'language' => 'en',
                'variables' => [
                    '1' => 'John Doe',
                    '2' => '12:00 PM',
                    '3' => 'https://ramadandemo.pray4movement.org/list',
                ],
                'types' => [
                    'twilio/text' => [
                        'body' => 'Hi {{1}},\n\nYour prayer time at {{2}} is approaching. Here is a link to the prayer fuel: {{3}}.\n\nThank you for praying with us.'
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
        $phone_number = campaigns_validate_and_format_phone( $subscriber['whatsapp_number'] ?? '' );
        if ( empty( $phone_number ) ){
            return;
        }
        $prayer_fuel_link = DT_Prayer_Campaigns_Send_Email::prayer_fuel_link( $subscriber, $campaign_id );

        //sort reports by time ascending
        usort( $reports, function( $a, $b ){
            return $a['time_begin'] <=> $b['time_begin'];
        } );

        foreach ( $reports as $report ){
            $prayer_fuel_time = $report['time_begin'];
            $fifteen_mints_before = $prayer_fuel_time - MINUTE_IN_SECONDS * 15;
            $now = time();
            $from_now = $fifteen_mints_before - $now;
            if ( $prayer_fuel_time < $now + MINUTE_IN_SECONDS * 20 ){
                if ( empty( $subscriber['whatsapp_number_verified'] ) ){
                    return;
                }
                self::send_whatsapp_notification( $phone_number, $prayer_fuel_link, $subscriber['ID'], $report );
            } else {
                //check if verified later.
                wp_queue()->push( new Prayer_Campaign_WhatsApp_Notifications_Job( $phone_number, $prayer_fuel_link, $subscriber['ID'], $report ), $from_now );
            }
        }
    }

    public static function send_whatsapp_notification( $phone_number, $prayer_fuel_link, $subscriber_id = null, $report = [] ){
        $subscriber = DT_Posts::get_post( 'subscriptions', $subscriber_id, true, false );
        if ( empty( $subscriber['whatsapp_number'] ) || empty( $subscriber['whatsapp_number_verified'] ) ){
            return true;
        }
        $locale = $record['lang'] ?? 'en_US';
        //self::switch_email_locale( $locale );
        $timezone = $subscriber['timezone'] ?? 'America/Chicago';
        $tz = new DateTimeZone( $timezone );
        $begin_date = new DateTime( '@'.$report['time_begin'] );
        $begin_date->setTimezone( $tz );
        $hour = DT_Time_Utilities::display_hour_localized( $begin_date, $locale, $timezone );
        $sent = Disciple_Tools_Twilio_API::send_dt_notification_template(
            $phone_number,
            [
                '1' => $subscriber['name'] ?? '',
                '2' => $hour,
                '3' => $prayer_fuel_link,
            ],
            'prayer_time_notification',
        );
        if ( !$sent ){
            dt_write_log( __METHOD__ . ': Unable to send whatsapp to ' . $phone_number );
        }
        DT_Posts::add_post_comment( 'subscriptions', $subscriber_id, 'WhatsApp Fuel Notification Sent', 'comment', [], false, true );
        return $sent;
    }

    /**
     * Send a message to the subscriber to confirm their whatsapp number
     * @param  $subscriber_id
     * @return void
     */
    public static function campaign_subscription_activated( $params ){
        $subscriber = DT_Posts::get_post( 'subscriptions', $params['id'], true, false );
        $phone_number  = campaigns_validate_and_format_phone( $subscriber['whatsapp_number'] ?? '' );
        if ( empty( $phone_number ) || !empty( $subscriber['whatsapp_number_verified'] ) ){
            return;
        }
        $sent = Disciple_Tools_Twilio_API::send_dt_notification_template(
            $phone_number,
            [],
            'confirm_wa_number'
        );
        if ( !$sent ){
            dt_write_log( __METHOD__ . ': Unable to send whatsapp verification to ' . $phone_number );
            return;
        }
        DT_Posts::add_post_comment( 'subscriptions', $params['id'], 'Message sent to verify WhatsApp Number', 'comment', [], false, true );
    }

    public static function dt_subscription_update_profile( $updates, $subscriber_id, $new_values ){
        if ( !empty( $new_values['whatsapp_number'] ) ){
            $new_values['whatsapp_number'] = campaigns_validate_and_format_phone( $new_values['whatsapp_number'] );
            $updates['whatsapp_number'] = $new_values['whatsapp_number'];
            $updates['whatsapp_number_verified'] = false;
            if ( !empty( $new_values['whatsapp_number'] ) ){
                Disciple_Tools_Twilio_API::send_dt_notification_template(
                    $updates['whatsapp_number'],
                    [],
                    'confirm_wa_number',
                );
            }
        } else if ( isset( $new_values['whatsapp_number'] ) ){
            $updates['whatsapp_number'] = '';
            $updates['whatsapp_number_verified'] = false;
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
    public $report;
    public function __construct( $phone_number, $prayer_fuel_link, $subscriber_id, $report ){
        $this->phone_number = $phone_number;
        $this->prayer_fuel_link = $prayer_fuel_link;
        $this->subscriber_id = $subscriber_id;
        $this->report = $report;
    }
    public function handle(){
        Prayer_Campaign_WhatsApp_Notifications::send_whatsapp_notification( $this->phone_number, $this->prayer_fuel_link, $this->subscriber_id, $this->report );
    }
}
