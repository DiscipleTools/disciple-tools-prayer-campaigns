<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$lang = dt_campaign_get_current_lang();
dt_campaign_add_lang_to_cookie( $lang );
dt_campaign_set_translation( $lang );

$campaign_fields = DT_Campaign_Landing_Settings::get_campaign();
$langs = dt_campaign_list_languages();
$campaign_url = DT_Campaign_Landing_Settings::get_landing_root_url();
$url_path = dt_get_url_path();

$sign_up_link = $campaign_url . '#sign-up';
?>
<style>
    :root {
        --cp-color: <?php echo esc_html( PORCH_COLOR_SCHEME_HEX ) ?>;
        --cp-color-dark: color-mix(in srgb, var(--cp-color), #000 10%);
        --cp-color-light: color-mix(in srgb, var(--cp-color), #fff 70%);
    }
</style>

<!-- Nav -->
<div class="menu-wrap">
    <nav class="menu navbar">
        <div class="icon-list navbar-collapse">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( site_url() ) ?>"><?php esc_html_e( 'Home', 'disciple-tools-prayer-campaigns' ); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href='<?php echo esc_url( $sign_up_link ) ?>'><?php esc_html_e( 'Sign Up', 'disciple-tools-prayer-campaigns' ); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo esc_url( site_url() ) ?>/prayer/list"><?php echo esc_html( DT_Porch_Settings::get_field_translation( 'prayer_fuel_name' ) ) ?></a>
                </li>
            </ul>
        </div>
    </nav>
    <button class="close-button" id="close-button" aria-label="Menu Close"><i class="lnr lnr-cross"></i></button>
</div>
<!-- end Nav -->

<!-- HEADER -->
<header id="hero-area" data-stellar-background-ratio="0.5" class="stencil-background">
    <div class="fixed-top">
        <div class="container">
            <div class="logo-menu">
                <a href="<?php echo esc_url( $campaign_fields['logo_link_url'] ?? $campaign_url ) ?>" class="logo"><?php echo esc_html( $campaign_fields['name'] ?? 'Set Up a Campaign' ) ?></a>
                <div class="d-flex align-items-center">

                    <?php if ( count( $langs ) > 1 ): ?>

                    <select class="dt-magic-link-language-selector">

                        <?php foreach ( $langs as $code => $language ) : ?>

                            <option value="<?php echo esc_html( $code ); ?>" <?php selected( $lang === $code ) ?>>

                            <?php echo esc_html( $language['flag'] ); ?> <?php echo esc_html( $language['native_name'] ); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <?php endif; ?>

                    <button class="menu-button" id="open-button" aria-label="Menu Open"><i class="lnr lnr-menu"></i></button>
                </div>
            </div>
        </div>
    </div>
    <div class="overlay"></div>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="contents content-height text-center">

                    <?php if ( !empty( $campaign_fields['logo_url'] ) ) : ?>

                        <img class="logo-image" src="<?php echo esc_url( $campaign_fields['logo_url'] ) ?>" alt=""  />

                    <?php else : ?>

                        <h1 class="wow fadeInDown" style="font-size: 3em;" data-wow-duration="1000ms" data-wow-delay="0.3s">
                            <?php echo esc_html( $campaign_fields['name'] ?? 'Set Up A Campaign' ) ?>
                        </h1>

                    <?php endif; ?>

                    <h4 id="hero-section-subtitle">
                        <?php echo esc_html( DT_Porch_Settings::get_field_translation( 'subtitle', $lang ) ); ?>
                    </h4>
                    <?php
                    if ( is_user_logged_in() ) {
                        $settings = DT_Porch_Settings::settings();
                        $field_key = 'subtitle';
                        $section_id = 'hero-section-subtitle';
                        $lang_default = $settings[$field_key]['default'] ?? '';

                        $lang_all = $settings[$field_key]['value'] ?? '';
                        $lang_selected = $settings[$field_key]['translations'][dt_campaign_get_current_lang()] ?? '';
                        ?>
                        <input id="<?php echo esc_attr( $section_id ) ?>_lang_default" type="hidden" value="<?php echo esc_attr( $lang_default ) ?>"/>
                        <input id="<?php echo esc_attr( $section_id ) ?>_lang_all" type="hidden" value="<?php echo esc_attr( $lang_all ) ?>"/>
                        <input id="<?php echo esc_attr( $section_id ) ?>_lang_selected" type="hidden" value="<?php echo esc_attr( $lang_selected ) ?>"/>

                        <button class="btn edit-btn btn-common" style="font-size: 10px; padding: 5px 15px;" data-field_key="<?php echo esc_attr( $field_key ) ?>" data-section_id="hero-section-subtitle"  data-split_text="<?php echo esc_attr( ( $split_text ? 'true' : 'false' ) ) ?>"><?php esc_html_e( 'Edit', 'disciple-tools-prayer-campaigns' ); ?></button>
                        <?php
                    }
                    ?>

                    <?php
                    if ( !isset( $campaign_fields['end_date']['formatted'] ) || time() <= strtotime( $campaign_fields['end_date']['formatted'] ?? '' ) ) : ?>

                        <p class="section-subtitle wow fadeInDown" data-wow-duration="1000ms" data-wow-delay="0.3s">
                            <a href="<?php echo esc_url( $sign_up_link ) ?>" class="btn btn-common btn-rm"><?php esc_html_e( 'Sign Up to Pray', 'disciple-tools-prayer-campaigns' ); ?></a>
                        </p>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</header>
<!-- Header Section End -->
