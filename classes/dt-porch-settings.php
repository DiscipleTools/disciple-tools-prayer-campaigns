<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Encapsulates getting and setting of porch settings
 */
class DT_Porch_Settings {

    public static function porch_fields() {
        $defaults = [];

        $defaults = apply_filters( 'dt_campaign_porch_settings', $defaults );

        $saved_fields = get_option( 'dt_campaign_porch_settings', [] );

        $lang = dt_campaign_get_current_lang();
        $people_name = self::get_field_translation( $saved_fields["people_name"] ?? [], $lang );
        $country_name = self::get_field_translation( $saved_fields["country_name"] ?? [], $lang );

        $defaults["goal"]["default"] = sprintf( $defaults["goal"]["default"], !empty( $country_name ) ? $country_name : "COUNTRY" );

        $defaults["what_content"]["default"] = sprintf(
            $defaults["what_content"]["default"],
            !empty( $country_name ) ? $country_name : "COUNTRY",
            !empty( $people_name ) ? $people_name : "PEOPLE"
        );

        return recursive_parse_args( $saved_fields, $defaults );
    }

    public static function get_field_translation( $field, $code ) {
        if ( empty( $field ) ) {
            return "";
        }

        if ( isset( $field["translations"][$code] ) && !empty( $field["translations"][$code] ) ) {
            return $field["translations"][$code];
        }

        return $field["value"] ?: ( $field["default"] ?? "" );
    }
}
