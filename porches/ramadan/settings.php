<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'dt_campaign_porch_settings', function ( $settings, $porch_type = null ){
    if ( $porch_type === 'ramadan-porch' ){
        $settings['what_content']['default'] = __( 'Ramadan is one of the five requirements (or pillars) of Islam. During each of its 30 days, Muslims are obligated to fast from dawn until sunset. During this time they are supposed to abstain from food, drinking liquids, smoking, and sexual relations.

Women typically spend the afternoons preparing a big meal. At sunset, families often gather to break the fast. Traditionally the families break the fast with a drink of water, then three dried date fruits, and a multi-course meal. After watching the new Ramadan TV series, men (and some women) go out to coffee shops where they drink coffee, and smoke with friends until late into the night.

Though many have stopped fasting in recent years, and are turned off by the hypocrisy, increased crime rates, and rudeness that is pervasive through the month, others have become more serious about religion during this time. Many attend the evening prayer services and do the other ritual prayers. Some even read the entire Quran (about a tenth the length of the Bible). This sincere seeking makes it a strategic time for us to pray for them.', 'disciple-tools-prayer-campaigns' );

        $settings['header_background_url']['options'] = [
            DT_Generic_Porch::assets_dir() . 'img/stencil-header.png',
            'https://s3.prayer.tools/ramadan/arab-city-stencil-skyline-black-and-while-mosque-png.jpg',
            'https://s3.prayer.tools/ramadan/arab-city-stencil-skyline-black-and-while-mosque-png2.jpg',
            'https://s3.prayer.tools/ramadan/city-stencil-skyline-black-and-while-outline-29.jpg',
            'https://s3.prayer.tools/ramadan/city-stencil-skyline-black-and-while-outline.jpg',
            'https://s3.prayer.tools/ramadan/d664d27a-e336-48aa-b911-13e8d0999bf4.jpeg',
            'https://s3.prayer.tools/ramadan/desert-river-stencil-black-and-white-outline-e820bf.jpg',
            'https://s3.prayer.tools/ramadan/desert-stencil-skyline-black-and-while-outline-camels.jpg',
            'https://s3.prayer.tools/ramadan/mecca-stencil-skyline-black-and-while-outline.jpg',
            'https://s3.prayer.tools/ramadan/muslim-city-stencil-black-and-white-outline.jpg',
            'https://s3.prayer.tools/ramadan/muslim-city-stencil-skyline-black-and-while-outline-c.jpg',
            'https://s3.prayer.tools/ramadan/muslim-city-stencil-skyline-black-and-while-outline.jpg',
            'https://s3.prayer.tools/ramadan/muslim-city-stencil-skyline-black-and-white-outline-5.jpg',
            'https://s3.prayer.tools/ramadan/nomads-desert-stencil-skyline-black-and-white-outline.jpg',
            'https://s3.prayer.tools/ramadan/prayer-in-the-desert-stencil-black-and-white-outline.jpg',
            'https://s3.prayer.tools/ramadan/prayer-in-the-desert-stencil-black-and-white-outline2.jpg',
            'https://s3.prayer.tools/ramadan/prayer-in-the-desert-stencil-black-and-white-outline3.jpg',
            'https://s3.prayer.tools/ramadan/ramadan-city.jpeg',
            'https://s3.prayer.tools/ramadan/sahara-desert-stencil-skyline-black-and-while-outline.jpg',
            'https://s3.prayer.tools/ramadan/sahara-stencil-skyline-black-and-while-outline-camel.jpg',
            'https://s3.prayer.tools/ramadan/sahara-stencil-skyline-black-and-while-outline-camels.jpg',
            'https://s3.prayer.tools/ramadan/stencil-skyline-black-and-while-outline.jpg',
            'https://s3.prayer.tools/ramadan/village-in-the-desert-stencil-black-and-white-outline.jpg',
            'https://s3.prayer.tools/ramadan/village-in-the-desert-stencil-black-and-white-outline2.jpg',
        ];
    }
    return $settings;
}, 20, 2 );
