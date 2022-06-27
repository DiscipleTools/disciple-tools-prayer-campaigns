<?php

/**
 * Class for defining the theme options available to the porches for usage
 *
 * If a porch does not define a theme css file then that theme colour will not be available
 *
 * Available default theme names are 'preset', 'forestgreen', 'green', 'orange', 'purple' and 'teal'
 */
class DT_Porch_Theme {

    private $themes;
    private $default_theme_name = 'preset';

    public function __construct() {
        $theme_default_options = $this->get_theme_default_options();

        /**
         * Add new theme options with this filter. They will show up in the list of available
         * colours as long as there exists a css file to define it in the porches own site/css/theme_name.css
         */
        $theme_options = apply_filters( 'dt_campaign_porch_theme_options', $theme_default_options );

        $theme_options = $this->add_theme_names( $theme_options );

        $this->themes = $theme_options;
    }

    /**
     * Get the theme options array filtered by the themes defined in the porches theme dir
     *
     * @param string $theme_dir The directory that contains the theme specific css files, named according to the theme names
     *
     * @return array
     */
    public function get_available_theme_names( string $theme_dir ) {
        $dir = scandir( $theme_dir );
        $theme_names = [];
        foreach ( $dir as $file ) {
            if ( substr( $file, -4, 4 ) === '.css' ){
                $f = explode( '.', $file );
                $l_key = $f[0];
                $label = ucwords( $l_key );
                $theme_names[$l_key] = $label;
            }
        }

        $keep_themes_with_css = function ( $theme_name ) use ( $theme_names ) {
            return isset( $this->themes[$theme_name] );
        };

        $available_themes = array_filter( $theme_names, $keep_themes_with_css, ARRAY_FILTER_USE_KEY );

        return $available_themes;
    }

    /**
     * Get a theme option by it's option key. Empty object is returned if they key is not defined
     * in the options
     *
     * @param string $theme_key
     *
     * @return array
     */
    public function get_theme( string $theme_key ) {
        if ( !$this->is_theme_available( $theme_key ) ) {
            return [];
        }

        return $this->themes[$theme_key];
    }

    /**
     * Get the default theme
     */
    public function get_default_theme() {
        if ( !$this->is_theme_available( $this->default_theme_name ) ) {
            return [];
        }

        return $this->get_theme( $this->default_theme_name );
    }

    /**
     * Is the theme available
     *
     * @param string $option_key
     *
     * @return bool
     */
    public function is_theme_available( $option_key ) {
        return isset( $this->themes[$option_key] );
    }

    private function get_theme_default_options() {
        $defaults = [
            'preset' => [
                'color' => '#4676fa',
            ],
            'forestgreen' => [
                'color' => '#1EB858',
            ],
            'green' => [
                'color' => '#94C523',
            ],
            'orange' => [
                'color' => '#F57D41',
            ],
            'purple' => [
                'color' => '#6B58CD',
            ],
            'teal' => [
                'color' => '#1AB7D8',
            ],
        ];

        return $defaults;
    }

    private function add_theme_names( $options ) {

        foreach ( $options as $key => $value ) {
            $options[$key]["name"] = $key;
        }

        return $options;
    }
}
