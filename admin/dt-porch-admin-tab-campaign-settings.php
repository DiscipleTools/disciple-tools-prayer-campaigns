<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

class DT_Porch_Admin_Tab_Campaign_Settings extends DT_Porch_Admin_Tab_Base {

    public $title = 'Campaign Settings';

    public $key = 'campaign_landing';

    public function __construct() {
        parent::__construct( $this->key );
        add_action( 'dt_prayer_campaigns_tab_content', [ $this, 'dt_prayer_campaigns_tab_content' ], 10, 2 );
        add_filter( 'prayer_campaign_tabs', [ $this, 'prayer_campaign_tabs' ], 10, 1 );
    }
    public function prayer_campaign_tabs( $tabs ) {
        $tabs[ $this->key ] = $this->title;
        return $tabs;
    }

    public function dt_prayer_campaigns_tab_content( $tab, $campaign_id ) {
        if ( $tab !== $this->key || empty( $campaign_id ) ){
            return;
        }
        $this->process_language_settings();
        $this->process_new_language();

        $this->content( true );
    }

    public function body_content(){
        $this->main_column();
        $this->box_languages();
        $this->custom_js();
    }

    public function custom_js(){
        ?>
        <script>
          jQuery(document).ready(function ($) {
            // Add a new language
            let goaltype = $('#goal').val();
            $('#goal_quantity-row').toggle(goaltype==='quantity');
            $('#goal').change(function () {
              $('#goal_quantity-row').toggle($(this).val()==='quantity');
            });
          });
        </script>

        <?php
    }

    private function box_languages() {
        $campaign_id = DT_Campaign_Landing_Settings::get_campaign_id();
        $languages = DT_Campaign_Languages::get_installed_languages( $campaign_id );
        ?>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><h3 id="Languages">Language Settings</h3></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2">
                        <p>
                            These are the languages with existing translations, where the landing page and emails are translated. <br>
                            Please help us and new translations and improve existing ones. See <a href="https://prayer.tools/docs/translation/" target="_blank">translation</a>.
                        </p>
                        <form method="POST">
                            <input type="hidden" name="language_settings_nonce" id="language_settings_nonce"
                                    value="<?php echo esc_attr( wp_create_nonce( 'language_settings' ) ) ?>"/>

                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Flag</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $enabled_column = array_column( $languages, 'enabled' );
                                        array_multisort( $enabled_column, SORT_DESC, $languages );
                                    ?>

                                    <style>
                                        .disabled-language {
                                            background-color: darkgrey;
                                        }
                                        .widefat .disabled-language td {
                                            color: white;
                                        }
                                    </style>

                                    <?php foreach ( $languages as $code => $language ): ?>

                                        <tr class="<?php echo $language['enabled'] === false ? 'disabled-language' : '' ?>">
                                            <td><?php echo esc_html( $language['english_name'] ) ?></td>
                                            <td><?php echo esc_html( $code ) ?></td>
                                            <td><?php echo esc_html( $language['flag'] ) ?></td>
                                            <td>

                                                <?php if ( isset( $language['enabled'] ) && $language['enabled'] === true ): ?>

                                                <button class="button" name="language_settings_disable" value="<?php echo esc_html( $code ) ?>">
                                                    Disable
                                                </button>

                                                <?php else : ?>

                                                <button class="button button-primary" name="language_settings_enable" value="<?php echo esc_html( $code ) ?>">
                                                    Enable
                                                </button>

                                                <?php endif; ?>

                                                <?php if ( !isset( $language['default'] ) || $language['default'] !== true ): ?>

                                                <button class="button" name="language_settings_remove" value="<?php echo esc_html( $code ) ?>">
                                                    Remove
                                                </button>

                                                <?php endif; ?>

                                            </td>
                                        </tr>

                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td>
                        Add a new untranslated language (usually used to create prayer fuel):
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="add_language_nonce" id="add_language_nonce"
                                    value="<?php echo esc_attr( wp_create_nonce( 'add_language' ) ) ?>"/>

                            <?php $language_list = dt_get_available_languages( false, true );
                            //sort by english name
                            $english_name_column = array_column( $language_list, 'label' );
                            array_multisort( $english_name_column, SORT_ASC, $language_list );
                            ?>

                            <select name="new_language" id="language_list">

                            <?php foreach ( $language_list as $code => $language ): ?>

                                <option value="<?php echo esc_html( $code ) ?>"><?php echo esc_html( $language['flag'] . ' ' . $language['label'] ) ?></option>

                            <?php endforeach; ?>

                            </select>
                            <button class="button">
                                Add
                            </button>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
        <br/>

        <?php
    }

    /**
     * Process changes to the language settings
     */
    public function process_language_settings() {
        if ( isset( $_POST['language_settings_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['language_settings_nonce'] ) ), 'language_settings' ) ) {

            if ( isset( $_POST['language_settings_disable'] ) ) {
                DT_Campaign_Languages::disable( sanitize_text_field( wp_unslash( $_POST['language_settings_disable'] ) ) );
            }

            if ( isset( $_POST['language_settings_enable'] ) ) {
                DT_Campaign_Languages::enable( sanitize_text_field( wp_unslash( $_POST['language_settings_enable'] ) ) );
            }

            if ( isset( $_POST['language_settings_remove'] ) ) {
                DT_Campaign_Languages::remove( sanitize_text_field( wp_unslash( $_POST['language_settings_remove'] ) ) );
            }
        }
    }

    /**
     * Process changes to the language settings
     */
    public function process_new_language() {
        if ( isset( $_POST['add_language_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['add_language_nonce'] ) ), 'add_language' ) ) {
            if ( isset( $_POST['new_language'] ) ) {
                DT_Campaign_Languages::add_from_code( sanitize_text_field( wp_unslash( $_POST['new_language'] ) ) );
            }
        }
    }

    public function right_column() {
        $sections = DT_Porch_Settings::sections( $this->key );
        $sections[] = 'Languages';
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th>On this page</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <ul>
                        <?php foreach ( $sections as $section ):
                            $section = $section ?: 'Other';
                            ?>
                            <li>
                                <a href="#<?php echo esc_html( $section ) ?>"><?php echo esc_html( $section ) ?> Settings</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }
}
new DT_Porch_Admin_Tab_Campaign_Settings();