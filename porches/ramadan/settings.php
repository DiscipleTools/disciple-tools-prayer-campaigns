<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'dt_campaign_porch_settings', function ( $settings, $porch_type = null ){
    if ( $porch_type === 'ramadan-porch' ){
        $settings['what_content']['default'] = __( 'Ramadan is one of the five requirements (or pillars) of Islam. During each of its 30 days, Muslims are obligated to fast from dawn until sunset. During this time they are supposed to abstain from food, drinking liquids, smoking, and sexual relations.

     Women typically spend the afternoons preparing a big meal. At sunset, families often gather to break the fast. Traditionally the families break the fast with a drink of water, then three dried date fruits, and a multi-course meal. After watching the new Ramadan TV series, men (and some women) go out to coffee shops where they drink coffee, and smoke with friends until late into the night.

     Though many have stopped fasting in recent years, and are turned off by the hypocrisy, increased crime rates, and rudeness that is pervasive through the month, others have become more serious about religion during this time. Many attend the evening prayer services and do the other ritual prayers. Some even read the entire Quran (about a tenth the length of the Bible). This sincere seeking makes it a strategic time for us to pray for them.', 'pray4ramadan-porch' );
    }
    return $settings;
}, 20, 2 );
