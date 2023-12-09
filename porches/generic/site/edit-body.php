<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();
$porch_fields = DT_Porch_Settings::settings();

$todays_campaign_day = DT_Campaign_Fuel::what_day_in_campaign( gmdate( 'Y-m-d' ) );

$today = DT_Campaign_Prayer_Fuel_Post_Type::instance()->get_days_posts( $todays_campaign_day );
$campaign = DT_Campaign_Landing_Settings::get_campaign();
$minutes_scheduled = isset( $campaign['ID'] ) ? DT_Campaigns_Base::get_minutes_prayed_and_scheduled( $campaign['ID'] ) : 0;
$days_scheduled = round( !empty( $minutes_scheduled ) ? ( $minutes_scheduled / 24 / 60 ) : 0, 1 );
?>

