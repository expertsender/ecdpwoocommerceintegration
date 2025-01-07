<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    ExpertSender_CDP
 * @subpackage ExpertSender_CDP/admin
 * @author     Endora <marcin.krupa@endora.pl>
 */
class ExpertSender_CDP_Admin
{
    const RESOURCE_PRODUCT = 'product';
    const RESOURCE_CUSTOMER = 'customer';
    const RESOURCE_ORDER = 'order';

    const FORM_REGISTRATION_KEY = 'registration';
    const FORM_CUSTOMER_SETTINGS_KEY = 'customer_settings';
    const FORM_CHECKOUT_KEY = 'checkout';
    const FORM_NEWSLETTER_KEY = 'newsletter';

    const FORM_CONSENT_FORMS = 'expertsender_cdp-consent-forms-form';
    const OPTION_VALUE_SINGLE_OPT_IN = 'single-opt-in';
    const OPTION_VALUE_DOUBLE_OPT_IN = 'double-opt-in';

    const OPTION_FORM_REGISTRATION_TEXT_BEFORE = 'expertsender_cdp_registration_text_before';
    const OPTION_FORM_REGISTRATION_TYPE = 'expertsender_cdp_registration_form_type';
    const OPTION_FORM_REGISTRATION_MESSAGE_ID = 'expertsender_cdp_registration_form_message_id';
    const OPTION_FORM_CUSTOMER_SETTINGS_TEXT_BEFORE = 'expertsender_cdp_customer_settings_text_before';
    const OPTION_FORM_CUSTOMER_SETTINGS_TYPE = 'expertsender_cdp_customer_settings_form_type';
    const OPTION_FORM_CUSTOMER_SETTINGS_MESSAGE_ID = 'expertsender_cdp_customer_settings_form_message_id';
    const OPTION_FORM_CHECKOUT_TEXT_BEFORE = 'expertsender_cdp_checkout_text_before';
    const OPTION_FORM_CHECKOUT_TYPE = 'expertsender_cdp_checkout_form_type';
    const OPTION_FORM_CHECKOUT_MESSAGE_ID = 'expertsender_cdp_checkout_form_message_id';
    const OPTION_FORM_NEWSLETTER_TEXT_BEFORE = 'expertsender_cdp_newsletter_text_before';
    const OPTION_FORM_NEWSLETTER_TYPE = 'expertsender_cdp_newsletter_form_type';
    const OPTION_FORM_NEWSLETTER_MESSAGE_ID = 'expertsender_cdp_newsletter_form_message_id';

    const OPTION_ENABLE_LOGS = 'expertsender_cdp_enable_logs';
    const OPTION_API_KEY = 'expertsender_cdp_api_key';

    const PARAMETER_MISSING_API_KEY = 'missing_api_key';

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action('admin_menu', [$this, 'add_plugin_admin_menu']);
        add_action('admin_menu', [$this, 'add_plugin_admin_mappings']);
        add_action('admin_menu', [$this, 'add_plugin_admin_consents']);
        add_action( 'admin_menu', array( $this, 'add_plugin_admin_consent_forms' ) );
        add_action('admin_menu', [
            $this,
            'add_plugin_admin_order_status_mapping',
        ]);

        add_action('admin_menu', [
            $this,
            'add_plugin_admin_synchronize_orders',
        ]);

        add_action('admin_init', [
            $this,
            'expertsender_cdp_handle_form_submission',
        ]);

        add_action('admin_init', [
            $this,
            'expertsender_cdp_mappings_handle_form_submission',
        ]);

        add_action('admin_init', [
            $this,
            'expertsender_cdp_consents_handle_form_submission',
        ]);

        add_action( 'admin_init', array(
            $this,
            'handle_consent_forms_submit'
        ));

        add_action('admin_init', [
            $this,
            'expertsender_cdp_order_status_mapping_handle_form_submission',
        ]);

        add_action('admin_init', [
            $this,
            'expertsender_cdp_order_synchronize_submission',
        ]);

        if ( isset ( $_GET[ self::PARAMETER_MISSING_API_KEY ] ) ) {
            $this->add_admin_error_notice( __( 'Przed ustawieniami mapowań i synchronizacją zamówień należy uzupełnić klucz API.', 'expertsender-cdp' ) );
        }
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name . '_admin',
            plugin_dir_url(__FILE__) . 'css/expertsender-cdp-admin.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * @param string $form
     * @return string|null
     */
    public static function get_opt_in_option_by_form( $form ) {
        $option = null;

        switch ( $form ) {
            case self::FORM_CHECKOUT_KEY:
                $option = self::OPTION_FORM_CHECKOUT_TYPE;
                break;
            case self::FORM_CUSTOMER_SETTINGS_KEY:
                $option = self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE;
                break;
            case self::FORM_NEWSLETTER_KEY:
                $option = self::OPTION_FORM_NEWSLETTER_TYPE;
                break;
            case self::FORM_REGISTRATION_KEY:
                $option = self::OPTION_FORM_REGISTRATION_TYPE;
                break;
            default:
                break;
        }

        if ( null !== $option ) {
            return get_option( $option );
        }

        return null;
    }

    /**
     * @param string $form
     * @return int|null
     */
    public static function get_confirmation_message_id_option_by_form( $form ) {
        $option = null;

        switch ( $form ) {
            case self::FORM_CHECKOUT_KEY:
                $option = self::OPTION_FORM_CHECKOUT_MESSAGE_ID;
                break;
            case self::FORM_CUSTOMER_SETTINGS_KEY:
                $option = self::OPTION_FORM_CUSTOMER_SETTINGS_MESSAGE_ID;
                break;
            case self::FORM_NEWSLETTER_KEY:
                $option = self::OPTION_FORM_NEWSLETTER_MESSAGE_ID;
                break;
            case self::FORM_REGISTRATION_KEY:
                $option = self::OPTION_FORM_REGISTRATION_MESSAGE_ID;
                break;
            default:
                break;
        }

        if ( null !== $option ) {
            $value = get_option( $option );

            if ( null !== $value ) {
                return (int) $value;
            }
        }

        return null;
    }

    public function add_plugin_admin_menu()
    {
        add_menu_page(
            __('Ustawienia ogólne', 'expertsender-cdp'), // Page title
            'ExpertSender CDP', // Menu title
            'manage_options', // Capability
            'expertsender_cdp-settings', // Menu slug
            [$this, 'render_settings_page'] // Callback function to render the settings page
        );
    }

    public function add_plugin_admin_mappings()
    {
        add_submenu_page(
            'expertsender_cdp-settings',
            __( 'Mapowania pól', 'expertsender-cdp' ),
            __( 'Mapowania pól', 'expertsender-cdp' ),
            'manage_options',
            'expertsender_cdp-settings-mappings',
            [ $this, 'render_mappings_page' ]
        );
    }

    public function add_plugin_admin_consents()
    {
        add_submenu_page(
            'expertsender_cdp-settings',
            __( 'Mapowania zgód', 'expertsender-cdp' ),
            __( 'Mapowania zgód', 'expertsender-cdp' ),
            'manage_options',
            'expertsender_cdp-settings-consents',
            array( $this, 'render_consents_page' )
        );
    }

    /**
     * @return void
     */
    public function add_plugin_admin_consent_forms()
    {
        add_submenu_page(
            'expertsender_cdp-settings',
            __( 'Ustawienia formularzy ze zgodami', 'expertsender-cdp' ),
            __( 'Ustawienia formularzy ze zgodami', 'expertsender-cdp' ),
            'manage_options',
            'expertsender_cdp-settings-consent-forms',
            array( $this, 'render_consent_forms_page' )
        );
    }

    public function add_plugin_admin_order_status_mapping()
    {
        add_submenu_page(
            'expertsender_cdp-settings',
            __( 'Mapowania statusów zamówienia', 'expertsender-cdp' ),
            __( 'Mapowania statusów zamówienia', 'expertsender-cdp' ),
            'manage_options',
            'expertsender_cdp-settings-order-status-mapping',
            array( $this, 'render_order_status_mapping_page' )
        );
    }

    public function add_plugin_admin_synchronize_orders()
    {
        add_submenu_page(
            'expertsender_cdp-settings',
            __( 'Synchronizacja zamówień', 'expertsender-cdp' ),
            __( 'Synchronizacja zamówień', 'expertsender-cdp' ),
            'manage_options',
            'expertsender_cdp-settings-synchronize-orders',
            array( $this, 'render_order_synchronize_page' )
        );
    }

    public function render_settings_page()
    {
        $this->check_permissions();
        $value = get_option('expertsender_cdp_enable_script');
        $checked = $value ? 'checked' : '';
        $phoneChecked = get_option('expertsender_cdp_enable_phone')
            ? 'checked'
            : '';
        $enableLogs = get_option( self::OPTION_ENABLE_LOGS ) ? 'checked' : '';
?>

        <div class="wrap">
            <h1 class="es-bold"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form id="expertSenderForm" method="post" action="/wp-admin/admin.php?page=expertsender_cdp-settings">
                <input type="hidden" name="expertsender_cdp-main-form">

                <?php settings_fields('expertsender_cdp_settings_group'); ?>
                <?php do_settings_sections('expertsender_cdp-settings'); ?>
                <table class="form-table es-table">
                    <tr valign="top">
                        <th scope="row"><?= esc_html__( 'Klucz API', 'expertsender-cdp' ); ?></th>
                        <td>
                            <input type="password" name="<?= ExpertSender_CDP_Admin::OPTION_API_KEY; ?>" value="<?php echo esc_attr( get_option( self::OPTION_API_KEY ) ); ?>" />
                        </td>
                        <td>
                            <p><?= esc_html__( 'Skąd pobrać klucz API?', 'expertsender-cdp' ); ?></p>
                            <ol>
                                <li>
                                    <a href="https://client.ecdp.app/Account/SignIn"><?= esc_html__( 'Zaloguj się do systemu ECDP', 'expertsender-cdp' ); ?></a>
                                </li>
                                <li><?= esc_html__( 'Wybierz odpowiednią jednostkę', 'expertsender-cdp' ); ?></li>
                                <li><?= esc_html__( 'Przejdź do zakładki Settings -&gt; API, a następnie skopiuj klucz i wklej w ustawieniach wtyczki.', 'expertsender-cdp' ); ?></li>
                            </ol>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?= esc_html__( 'Włącz skrypt śledzący ruch', 'expertsender-cdp' ); ?></th>
                        <td>
                            <input type="checkbox" class="es-input" id="expertsender_cdp_enable_script" name="expertsender_cdp_enable_script" value="1" <?= $checked ?> />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?= esc_html__( 'Skrypt śledzący ruch', 'expertsender-cdp' ); ?></th>
                        <td>
                            <textarea name="expertsender_cdp_script" id="expertsender_cdp_script" rows="8" cols="50"><?php echo esc_textarea( base64_decode( get_option( 'expertsender_cdp_script' ) ) ); ?></textarea>
                        </td>
                        <td>
                            <p><?= esc_html__( 'Skąd pobrać skrypt?', 'expertsender-cdp' ); ?></p>
                            <ol>
                                <li>
                                    <a href="https://client.ecdp.app/Account/SignIn"><?= esc_html__( 'Zaloguj się do systemu ECDP', 'expertsender-cdp' ); ?></a>
                                </li>
                                <li><?= esc_html__( 'Wybierz odpowiednią jednostkę', 'expertsender-cdp' ); ?></li>
                                <li><?= esc_html__( 'Przejdź do zakładki Settings -&gt; Web Tracking, a następnie przy odpowiedniej stronie internetowej kliknij ikonkę "Tracking code".', 'expertsender-cdp' ); ?></li>
                                <li><?= esc_html__( 'Skopiuj i wklej w ustawieniach wtyczki.', 'expertsender-cdp' ); ?></li>
                            </ol>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?= esc_html__( 'ID strony internetowej', 'expertsender-cdp' ); ?></th>
                        <td>
                            <input type="text" name="expertsender_cdp_website_id" value="<?php echo esc_attr( get_option( 'expertsender_cdp_website_id' ) ); ?>" />
                        </td>
                        <td>
                            <p><?= esc_html__( 'Skąd pobrać numer ID strony internetowej?', 'expertsender-cdp' ); ?></p>
                            <ol>
                                <li><a href="https://client.ecdp.app/Account/SignIn"><?= esc_html__( 'Zaloguj się do systemu ECDP', 'expertsender-cdp' ); ?></a></li>
                                <li><?= esc_html__( 'Wybierz odpowiednią jednostkę', 'expertsender-cdp' ); ?></li>
                                <li><?= esc_html__( 'Przejdź do zakładki Settings -&gt; Webtracking, a następnie znajdź odpowiednią stronę internetową.', 'expertsender-cdp' ); ?></li>
                                <li><?= esc_html__( 'Skopiuj ID i wklej w ustawieniach wtyczki.', 'expertsender-cdp' ); ?></li>
                            </ol>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?= esc_html__( 'Wysyłaj numer telefonu użytkowników do ExpertSender CDP', 'expertsender-cdp' ); ?></th>
                        <td><input type="checkbox" class="es-input" id="expertsender_cdp_enable_phone" name="expertsender_cdp_enable_phone" value="1" <?= $phoneChecked ?> /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?= esc_html__( 'Włącz logi', 'expertsender-cdp' ); ?></th>
                        <td><input type="checkbox" class="es-input" id="<?= self::OPTION_ENABLE_LOGS; ?>" name="<?= self::OPTION_ENABLE_LOGS; ?>" value="1" <?= $enableLogs; ?>/></td>
                    </tr>
                </table>
                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        $("#expertSenderForm").submit(function(event) {
                            event.preventDefault();

                            var inputValue = $("#expertsender_cdp_script").val();
                            var encodedValue = btoa(inputValue);
                            $("#expertsender_cdp_script").val(encodedValue);

                            event.target.submit();
                        });
                    });
                </script>
                <button class="es-button submit" type="submit"><?= esc_html__( 'Zapisz zmiany', 'expertsender-cdp' ); ?></button>
            </form>
        </div>
    <?php
    }

    public function expertsender_cdp_handle_form_submission() {
        if (isset($_POST['expertsender_cdp-main-form'])) {
            register_setting(
                'expertsender_cdp_settings_group',
                self::OPTION_API_KEY
            );
            register_setting(
                'expertsender_cdp_settings_group',
                'expertsender_cdp_enable_script'
            );
            register_setting(
                'expertsender_cdp_settings_group',
                'expertsender_cdp_enable_phone'
            );
            register_setting(
                'expertsender_cdp_settings_group',
                'expertsender_cdp_script',
                [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'show_in_rest' => false,
                ]
            );
            register_setting(
                'expertsender_cdp_settings_group',
                'expertsender_cdp_website_id'
            );
            $expertsender_cdp_key = sanitize_text_field(
                $_POST[self::OPTION_API_KEY]
            );
            update_option(self::OPTION_API_KEY, $expertsender_cdp_key);
            update_option(
                'expertsender_cdp_enable_script',
                $_POST['expertsender_cdp_enable_script'] ?? false
            );
            update_option(
                'expertsender_cdp_enable_phone',
                $_POST['expertsender_cdp_enable_phone'] ?? false
            );
            update_option(
                'expertsender_cdp_script',
                $_POST['expertsender_cdp_script']
            );
            $expertsender_cdp_website_id = sanitize_text_field(
                $_POST['expertsender_cdp_website_id']
            );
            update_option(
                'expertsender_cdp_website_id',
                $expertsender_cdp_website_id
            );
            update_option(
                self::OPTION_ENABLE_LOGS,
                $_POST[ self::OPTION_ENABLE_LOGS ] ?? false
            );
            $this->add_admin_success_notice();
        }
    }

    public function render_mappings_page()
    {
        $this->check_permissions();
        $this->check_api_key();

        $is_error = $_GET['es_is_error'] ?? false;

        if ( true === $is_error && isset( $_POST['expertsender_cdp-mapping-form'] ) ) {
            $customerMappings = $this->get_post_field_mappings( self::RESOURCE_CUSTOMER );
            $orderMappings = $this->get_post_field_mappings( self::RESOURCE_ORDER );
            $productMappings = $this->get_post_field_mappings( self::RESOURCE_PRODUCT );
        } else {
            $customerMappings = es_get_field_mappings_by_resource_type( self::RESOURCE_CUSTOMER );
            $productMappings = es_get_field_mappings_by_resource_type( self::RESOURCE_PRODUCT );
            $orderMappings = es_get_field_mappings_by_resource_type( self::RESOURCE_ORDER );
        }

        $orderKeys = es_get_order_mapping_field_keys();
        $productKeys = es_get_product_mapping_field_keys();
        $customerKeys = es_get_customer_mapping_field_keys();
        $customerOptions = $this->expertsender_cdp_get_customer_attributes_from_api();
        $productOptions = $this->expertsender_cdp_get_product_attributes_from_api();
        $orderOptions = $this->expertsender_cdp_get_order_attributes_from_api();
        $last_id = es_get_max_field_mapping_id() + 1;
    ?>

        <div class="wrap">
            <h1 class="es-bold"><?= esc_html( get_admin_page_title() ); ?></h1>
            <form id="es-field-mappings-form" method="post" action="">
                <input type="hidden" name="expertsender_cdp-mapping-form">
                <div id="productMapping" class="mappingSection">
                    <h2><?= esc_html__( 'Mapowania pól produktów', 'expertsender-cdp' ); ?></h2>

                    <button type="button" class="addPairBtn es-button"><?= esc_html__( 'Dodaj', 'expertsender-cdp' ); ?></button>

                    <div class="es-input-pairs-container" data-slug="product">
                        <?php foreach ( $productMappings as $productMapping ): ?>
                            <div class="es-input-pair">
                                <input type="hidden" name="product[<?= esc_attr( $productMapping['id'] ); ?>][id]" value="<?= esc_attr( $productMapping['id'] ); ?>"/>

                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Pole produktu WC', 'expertsender-cdp' ); ?></label>
                                    <select name="product[<?= esc_attr( $productMapping['id'] ); ?>][wp_field]">
                                        <?php foreach ( $productKeys as $value ): ?>
                                            <?php $selected = $productMapping['wp_field'] == $value ? 'selected' : ''; ?>
                                            <option value="<?= esc_attr( $value ); ?>" <?= $selected; ?>><?= esc_html( $value ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Pole produktu ECDP', 'expertsender-cdp' ); ?></label>
                                    <select name="product[<?= esc_attr( $productMapping['id'] ); ?>][ecdp_field]">
                                        <?php foreach ( $productOptions as $value ): ?>
                                            <?php
                                            $value = $value->name;
                                            $selected = $productMapping['ecdp_field'] == $value ? 'selected' : '';
                                            ?>
                                            <option value="<?= esc_attr( $value ); ?>" <?= $selected; ?>><?= esc_html( $value ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button class="removeButton es-button" type="button"><?= esc_html__( 'Usuń', 'expertsender-cdp' ); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="customerMapping" class="mappingSection">
                    <h2><?= esc_html__( 'Mapowania pól użytkowników', 'expertsender-cdp' ); ?></h2>

                    <button type="button" class="addPairBtn es-button"><?= esc_html__( 'Dodaj', 'expertsender-cdp' ); ?></button>

                    <div class="es-input-pairs-container" data-slug="customer">
                        <?php foreach ( $customerMappings as $customerMapping ): ?>
                            <div class="es-input-pair">
                                <input type="hidden" name="customer[<?= esc_attr( $customerMapping['id'] ); ?>][id]" value="<?= esc_attr( $customerMapping['id'] ); ?>"/>

                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Pole użytkownika WC', 'expertsender-cdp' ); ?></label>

                                    <select name="customer[<?= esc_attr( $customerMapping['id'] ); ?>][wp_field]">
                                        <?php foreach ( $customerKeys as $customerKey ): ?>
                                            <?php $selected = $customerMapping['wp_field'] == $customerKey ? 'selected' : ''; ?>
                                            <option value="<?= esc_attr( $customerKey ); ?>" <?= $selected; ?>><?= esc_html( $customerKey ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            
                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Pole użytkownika ECDP', 'expertsender-cdp' ); ?></label>

                                    <select name="customer[<?= esc_attr( $customerMapping['id'] ); ?>][ecdp_field]">
                                        <?php foreach ($customerOptions as $value): ?>
                                            <?php
                                            $value = $value->name;
                                            $selected = $customerMapping['ecdp_field'] == $value ? 'selected' : '';
                                            ?>
                                        <option value="<?= esc_attr( $value ); ?>" <?= $selected; ?>><?= esc_html( $value ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            <button class="removeButton es-button" type="button"><?= esc_html__( 'Usuń', 'expertsender-cdp' ); ?></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="orderMapping" class="mappingSection">
                    <h2><?= esc_html__( 'Mapowania pól zamówień', 'expertsender-cdp' ); ?></h2>

                    <button type="button" class="addPairBtn es-button"><?= esc_html__( 'Dodaj', 'expertsender-cdp' ); ?></button>

                    <div class="es-input-pairs-container" data-slug="order">
                        <?php foreach ( $orderMappings as $orderMapping ): ?>
                            <div class="es-input-pair">
                                <input type="hidden" name="order[<?= esc_attr( $orderMapping['id'] ); ?>][id]" value="<?= esc_attr( $orderMapping['id'] ); ?>"/>

                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Pole zamówienia WC', 'expertsender-cdp' ); ?></label>
                                    <select name="order[<?= esc_attr( $orderMapping['id'] ); ?>][wp_field]">
                                        <?php foreach ($orderKeys as $orderKey): ?>
                                            <?php $selected = $orderMapping['wp_field'] == $orderKey ? 'selected' : ''; ?>
                                            <option value="<?= esc_attr( $orderKey ); ?>" <?= $selected; ?>><?= esc_html( $orderKey ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Pole zamówienia ECDP', 'expertsender-cdp' ); ?></label>
                                    <select name="order[<?= esc_attr( $orderMapping['id'] ); ?>][ecdp_field]">
                                        <?php foreach ($orderOptions as $value): ?>
                                            <?php
                                            $value = $value->name;
                                            $selected = $orderMapping['ecdp_field'] == $value ? 'selected' : '';
                                            ?>
                                            <option value="<?= esc_attr( $value ); ?>" $selected><?= esc_html( $value ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button class="removeButton es-button" type="button"><?= esc_html__( 'Usuń', 'expertsender-cdp' ); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">

                <button class="es-button submit" type="submit"><?= esc_html__( 'Zapisz zmiany', 'expertsender-cdp' ); ?></button>
            </form>

            <template id="es-field-mapping-id-template">
                <input type="hidden"/> 
            </template>

            <template id="productselect">
                <div class="es-input-wrap">
                    <label><?= esc_html__('Pole produktu ECDP'); ?></label>

                    <select>
                        <?php foreach ($productOptions as $value): ?>
                            <?php $value = $value->name; ?>
                            <option value="<?= esc_attr( $value ); ?>"><?= esc_html( $value ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </template>

            <template id="orderselect">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Pole zamówienia ECDP', 'expertsender-cdp' ); ?></label>

                    <select>
                        <?php foreach ($orderOptions as $value): ?>
                            <?php $value = $value->name; ?>
                            <option value="<?= esc_attr( $value ); ?>"><?= esc_html( $value ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </template>

            <template id="customerselect">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Pole użytkownika ECDP', 'expertsender-cdp' ); ?></label>

                    <select>
                        <?php foreach ($customerOptions as $value): ?>
                            <?php $value = $value->name; ?>
                            <option value="<?= esc_attr( $value ); ?>"><?= esc_html( $value ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </template>

            <template id="orderkeys">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Pole zamówienia WC', 'expertsender-cdp' ); ?></label>

                    <select>
                        <?php foreach ( $orderKeys as $key ): ?>
                            <option value="<?= esc_attr( $key ); ?>"><?= esc_html( $key ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </template>

            <template id="productkeys">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Pole produktu WC', 'expertsender-cdp' ); ?></label>

                    <select>
                        <?php foreach ( $productKeys as $value ): ?>
                            <option value="<?= esc_attr( $value ); ?>"><?= esc_html( $value ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </template>

            <template id="customerkeys">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Pole użytkownika WC', 'expertsender-cdp' ); ?></label>

                    <select>
                        <?php foreach ( $customerKeys as $key ): ?>
                            <option value="<?= esc_attr( $key ); ?>"><?= esc_html( $key ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </template>
        </div>

        <script>
            var removeButtons = document.querySelectorAll('.removeButton');

            removeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var inputPair = this.parentElement;
                    inputPair.remove();
                });
            });


            document.addEventListener("DOMContentLoaded", function() {
                const sections = document.querySelectorAll(".mappingSection");

                sections.forEach(function(section) {
                    const inputPairsContainer = section.querySelector(".es-input-pairs-container");
                    const addPairBtn = section.querySelector(".addPairBtn");

                    addPairBtn.addEventListener("click", function() {
                        createInputPair(inputPairsContainer);
                    });
                });

                function createInputPair(container) {
                    const pairDiv = document.createElement("div");
                    pairDiv.classList.add("es-input-pair");

                    var id = document.getElementById("idCounter").value;
                    var slug = container.getAttribute("data-slug");

                    const idTemplate = document.querySelector('#es-field-mapping-id-template');
                    const idTemplateContent = idTemplate.content.cloneNode(true);
                    const idInput = idTemplateContent.querySelector('input');
                    idInput.name = `${slug}[${id}][id]`;
                    idInput.value = id;

                    var template = document.querySelector("#" + slug + "keys");
                    const wpSelect = template.content.cloneNode(true);
                    let wp = wpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][wp_field]";

                    console.log("#" + slug + "select");
                    var template2 = document.querySelector("#" + slug + "select");
                    const ecdpSelect = template2.content.cloneNode(true);
                    let td = ecdpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][ecdp_field]";

                    const removeBtn = document.createElement("button");
                    removeBtn.textContent = '<?= esc_html__( 'Usuń', 'expertsender-cdp' ); ?>';
                    removeBtn.type = "button"
                    removeBtn.classList.add('es-button');
                    removeBtn.addEventListener("click", function() {
                        pairDiv.remove();
                    });

                    pairDiv.appendChild(idInput);
                    pairDiv.appendChild(wpSelect);
                    pairDiv.appendChild(ecdpSelect);
                    pairDiv.appendChild(removeBtn);

                    container.appendChild(pairDiv);
                    document.getElementById("idCounter").value = parseInt(id) + 1;
                }
            });
        </script>
    <?php
    }

    /**
     * @return void
     */
    public function expertsender_cdp_mappings_handle_form_submission() {
        if ( isset( $_POST['expertsender_cdp-mapping-form'] ) ) {
            $current_data = es_get_all_field_mappings();
            es_truncate_field_mappings();
            $mappings = $this->get_post_field_mappings();

            if ( ! empty( $mappings ) ) {
                $errors = es_validate_field_mapping_data( $mappings );

                if ( ! empty( $errors ) ) {
                    foreach( $errors as $error ) {
                        $this->add_admin_error_notice( $error );
                    }

                    $_GET['es_is_error'] = true;

                    if ( ! empty( $current_data ) ) {
                        es_insert_field_mappings( $current_data );
                    }

                    return;
                }

                $result = es_insert_field_mappings( $mappings );

                if ( false === $result ) {
                    if ( ! empty( $current_data ) ) {
                        es_insert_field_mappings( $current_data );
                    }

                    $this->add_admin_error_notice(
                        __( 'Wystąpił błąd w trakcie zapisywania mapowań.', 'expertsender-cdp' )
                    );
                } else {
                    $this->add_admin_success_notice();
                }
            } else if (! empty ( $current_data ) ) {
                $this->add_admin_success_notice();
            }
        }
    }

    /**
     * @param string|null $resource
     *
     * @return array
     */
    public function get_post_field_mappings( $by_resource = null ) {
        $mappings = array();
        $resources = array(
            self::RESOURCE_CUSTOMER,
            self::RESOURCE_ORDER,
            self::RESOURCE_PRODUCT
        );

        foreach ($_POST as $resource => $resource_mappings) {
            if ( is_string( $by_resource ) && $resource !== $by_resource ) {
                continue;
            }

            if ( in_array( $resource, $resources ) ) {
                foreach ( $resource_mappings as $mapping ) {
                    $mapping['resource_type'] = $resource;
                    $mappings[] = $mapping; 
                }
            }
        }

        return $mappings;
    }

    public function expertsender_cdp_get_customer_attributes_from_api()
    {
        $api_url = ES_API_URL . 'customerattributes';

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option(self::OPTION_API_KEY),
        ];

        $args = [
            'headers' => $headers,
        ];

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $this->print_api_error_message( $response );
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    public function expertsender_cdp_get_product_attributes_from_api()
    {
        $api_url = ES_API_URL . 'productattributes';

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option(self::OPTION_API_KEY),
        ];

        $args = [
            'headers' => $headers,
        ];

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $this->print_api_error_message( $response );
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    public function expertsender_cdp_get_order_attributes_from_api()
    {
        $api_url = ES_API_URL . 'orderattributes';

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option(self::OPTION_API_KEY),
        ];

        $args = [
            'headers' => $headers,
        ];

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $this->print_api_error_message( $response );
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    public function render_consents_page()
    {
        $this->check_permissions();
        $this->check_api_key();

        $consents = es_get_all_consents();
        $apiConsents = $this->expertsender_cdp_get_consents_from_api();
        $consentLocations = $this->expertsender_cdp_get_consents_locations();
        $last_id = es_get_max_consent_id() + 1;
    ?>
        <div class="wrap">
            <h1 class="es-bold"><?= esc_html( get_admin_page_title() ); ?></h1>
            <form id="expertSenderConsentsForm" method="post" action="">
                <input type="hidden" name="expertsender_cdp-consents-form">
                <div id="consentSection" class="consentSection">
                    <button type="button" class="addPairBtn es-button"><?= esc_html__( 'Dodaj', 'expertsender-cdp' ); ?></button>

                    <div class="es-input-pairs-container">
                        <?php foreach ( $consents as $consent ): ?>
                            <div class="es-input-pair">
                                <input type="hidden" name="consent[<?= esc_attr( $consent['id'] ); ?>][id]" value="<?= esc_attr( $consent['id'] ); ?>"/>
    
                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Zgoda ECDP', 'expertsender-cdp' ); ?></label>    

                                    <select name="consent[<?= esc_attr( $consent['id'] ); ?>][api_consent_id]">   
                                        <?php foreach ( $apiConsents as $value ): ?>
                                            <?php $selected = $consent['api_consent_id'] == $value->id ? 'selected' : ''; ?>
                                            <option value="<?= esc_attr( $value->id ); ?>" <?= $selected; ?>><?= esc_html( $value->name ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Lokalizacja', 'expertsender-cdp' ); ?></label> 

                                    <select name="consent[<?= esc_attr( $consent['id'] ); ?>][consent_location]">';
                                        <?php foreach ( $consentLocations as $value => $label ): ?>
                                            <?php $selected = $consent['consent_location'] == $value ? 'selected' : ''; ?>
                                            <option value="<?= esc_attr( $value ); ?>" <?= $selected; ?>><?= esc_html( $label ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Tekst zgody', 'expertsender-cdp' ); ?></label> 

                                    <input type="text" placeholder="<?= esc_attr__( 'Tekst zgody', 'expertsender-cdp' ); ?>" required="true" name="consent[<?= esc_attr( $consent['id'] ); ?>][consent_text]" value="<?= esc_attr( $consent['consent_text'] ); ?>">
                                </div>

                                <button class="removeButton es-button" type="button"><?= esc_html__( 'Usuń', 'expertsender-cdp'); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">

                    <button class="es-button submit" type="submit"><?= esc_html__( 'Zapisz zmiany', 'expertsender-cdp' ); ?></button>
            </form>

            <template id="es-consent-id-template">
                <input type="hidden"/> 
            </template>

            <template id="apiConsentsTemplate">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Zgoda ECDP', 'expertsender-cdp' ); ?></label>

                    <select>
                        <?php foreach ($apiConsents as $value): ?>
                            <option value="<?= esc_attr( $value->id ); ?>"><?= esc_html( $value->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </template>

            <template id="consentLocationTemplate">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Lokalizacja', 'expertsender-cdp' ); ?></label>

                    <select>
                        <?php foreach ( $consentLocations as $value => $label ): ?>
                            <option value="<?= esc_attr( $value ); ?>"><?= esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </template>

            <template id="es-consent-text-template">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Tekst zgody', 'expertsender-cdp' ); ?></label>

                    <input type="text" placeholder="<?= esc_attr__( 'Tekst zgody', 'expertsender-cdp' ); ?>" required="true"/>
                </div>
            </template>
        </div>

        <script>
            var removeButtons = document.querySelectorAll('.removeButton');

            removeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var inputPair = this.parentElement;
                    inputPair.remove();
                });
            });

            document.addEventListener("DOMContentLoaded", function() {
                const sections = document.querySelectorAll(".consentSection");

                sections.forEach(function(section) {
                    const elementContainer = section.querySelector(".es-input-pairs-container");
                    const addPairBtn = section.querySelector(".addPairBtn");

                    addPairBtn.addEventListener("click", function() {
                        createInputPair(elementContainer);
                    });
                });

                function createInputPair(container) {
                    const pairDiv = document.createElement("div");
                    pairDiv.classList.add("es-input-pair");

                    var id = document.getElementById("idCounter").value;

                    const idTemplate = document.querySelector('#es-consent-id-template');
                    const idTemplateContent = idTemplate.content.cloneNode(true);
                    const idInput = idTemplateContent.querySelector('input');
                    idInput.name = `consent[${id}][id]`;
                    idInput.value = id;

                    var template = document.querySelector("#apiConsentsTemplate");
                    const apiConsent = template.content.cloneNode(true);
                    apiConsent.querySelectorAll("select")[0].name = "consent[" + id + "][api_consent_id]";

                    var template2 = document.querySelector("#consentLocationTemplate");
                    const consentLocation = template2.content.cloneNode(true);
                    consentLocation.querySelectorAll("select")[0].name = "consent[" + id + "][consent_location]";

                    const consentTextTemplate = document.querySelector('#es-consent-text-template');
                    const consentText = consentTextTemplate.content.cloneNode(true);
                    const consentTextInput = consentText.querySelector('input');
                    consentTextInput.name = `consent[${id}][consent_text]`;

                    const removeBtn = document.createElement("button");
                    removeBtn.textContent = '<?= esc_html__( 'Usuń', 'expertsender-cdp' ); ?>';
                    removeBtn.type = "button"
                    removeBtn.classList.add('es-button')
                    removeBtn.addEventListener("click", function() {
                        pairDiv.remove();
                    });

                    pairDiv.appendChild(idInput);
                    pairDiv.appendChild(apiConsent);
                    pairDiv.appendChild(consentLocation);
                    pairDiv.appendChild(consentText);
                    pairDiv.appendChild(removeBtn);

                    container.appendChild(pairDiv);
                    document.getElementById("idCounter").value = parseInt(id) + 1;
                }
            });
        </script>
    <?php
    }

    public function expertsender_cdp_get_consents_from_api()
    {
        $api_url = ES_API_URL . 'customerconsents';

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option(self::OPTION_API_KEY),
        ];

        $args = [
            'headers' => $headers,
        ];

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $this->print_api_error_message( $response );
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    /**
     * @return array
     */
    public function expertsender_cdp_get_consents_locations() {
        return array(
            self::FORM_CHECKOUT_KEY => __( 'Checkout', 'expertsender-cdp' ),
            self::FORM_CUSTOMER_SETTINGS_KEY => __( 'Edycja profilu', 'expertsender-cdp' ),
            self::FORM_NEWSLETTER_KEY => __( 'Newsletter', 'expertsender-cdp' ),
            self::FORM_REGISTRATION_KEY => __( 'Rejestracja', 'expertsender-cdp' )
        );
    }

    /**
     * @return void
     */
    public function expertsender_cdp_consents_handle_form_submission() {
        if ( isset( $_POST['expertsender_cdp-consents-form'] ) ) {
            $current_data = es_get_all_consents();
            es_truncate_consents();

            if ( isset( $_POST['consent'] ) ) {
                $errors = es_validate_consent_data( $_POST['consent'] );

                if ( ! empty( $errors ) ) {
                    foreach ( $errors as $error ) {
                        $this->add_admin_error_notice( $error );
                    }

                    $_GET['es_is_error'] = true;

                    if ( ! empty( $current_data ) ) {
                        es_insert_consents( $current_data );
                    }

                    return;
                }

                $result = es_insert_consents( $_POST['consent'] );

                if ( false === $result ) {
                    if ( ! empty ( $current_data ) ) {
                        es_insert_consents( $current_data );
                    }

                    $this->add_admin_error_notice(
                        __( 'Wystąpił błąd w trakcie zapisywania mapowań.', 'expertsender-cdp' )
                    );
                } else {
                    $this->add_admin_success_notice();
                }
            } else if ( ! empty ( $current_data ) ) {
                $this->add_admin_success_notice();
            }
        }
    }

    /**
     * @return void
     */
    public function render_consent_forms_page() {
        $this->check_permissions();
        $this->check_api_key();
        $options = get_options( array(
            self::OPTION_FORM_CHECKOUT_TEXT_BEFORE,
            self::OPTION_FORM_CHECKOUT_TYPE,
            self::OPTION_FORM_CHECKOUT_MESSAGE_ID,
            self::OPTION_FORM_CUSTOMER_SETTINGS_TEXT_BEFORE,
            self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE,
            self::OPTION_FORM_CUSTOMER_SETTINGS_MESSAGE_ID,
            self::OPTION_FORM_NEWSLETTER_TEXT_BEFORE,
            self::OPTION_FORM_NEWSLETTER_TYPE,
            self::OPTION_FORM_NEWSLETTER_MESSAGE_ID,
            self::OPTION_FORM_REGISTRATION_TEXT_BEFORE,
            self::OPTION_FORM_REGISTRATION_TYPE,
            self::OPTION_FORM_REGISTRATION_MESSAGE_ID
        ) );
    ?>
        <div class="wrap">
            <h1 class="es-bold"><?= esc_html( get_admin_page_title() ); ?></h1>
            <form id="<?= self::FORM_CONSENT_FORMS; ?>" method="post" action="">
                <input type="hidden" name="<?= self::FORM_CONSENT_FORMS; ?>" />
                <h3><?= esc_html__( 'Rejestracja', 'expertsender-cdp' ); ?></h3>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Tekst wyświetlany przed zgodami', 'expertsender-cdp' ); ?></label>
                    <input type="text" name="<?= self::OPTION_FORM_REGISTRATION_TEXT_BEFORE; ?>" value="<?= $options[self::OPTION_FORM_REGISTRATION_TEXT_BEFORE]; ?>" />
                </div>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Tryb formularza', 'expertsender-cdp' ); ?></label>
                    <select name="<?= self::OPTION_FORM_REGISTRATION_TYPE ?>" class="es-form-type-select">
                        <option <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[self::OPTION_FORM_REGISTRATION_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_SINGLE_OPT_IN; ?>"><?= esc_html__( 'Single Opt-In', 'expertsender-cdp' ); ?></option>
                        <option <?php if ( self::OPTION_VALUE_DOUBLE_OPT_IN === $options[self::OPTION_FORM_REGISTRATION_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_DOUBLE_OPT_IN; ?>"><?= esc_html__( 'Double Opt-In', 'expertsender-cdp' ); ?></option>
                    </select>
                </div>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'ID wiadomości potwierdzającej w trybie Double Opt-In', 'expertsender-cdp' ); ?></label>
                    <input type="number" name="<?= self::OPTION_FORM_REGISTRATION_MESSAGE_ID; ?>" value="<?= $options[self::OPTION_FORM_REGISTRATION_MESSAGE_ID]; ?>" <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[ self::OPTION_FORM_REGISTRATION_TYPE ] ) { echo 'disabled'; } else { echo 'required="true"'; } ?> />
                </div>
                <div class="es-divider"></div>
                <h3><?= esc_html__( 'Edycja profilu', 'expertsender_cdp' ); ?></h3>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Tekst wyświetlany przed zgodami', 'expertsender-cdp' ); ?></label>
                    <input type="text" name="<?= self::OPTION_FORM_CUSTOMER_SETTINGS_TEXT_BEFORE; ?>" value="<?= $options[self::OPTION_FORM_CUSTOMER_SETTINGS_TEXT_BEFORE]; ?>" />
                </div>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Tryb formularza', 'expertsender-cdp' ); ?></label>
                    <select name="<?= self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE ?>" class="es-form-type-select">
                        <option <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_SINGLE_OPT_IN; ?>"><?= esc_html__( 'Single Opt-In', 'expertsender-cdp' ); ?></option>
                        <option <?php if ( self::OPTION_VALUE_DOUBLE_OPT_IN === $options[self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_DOUBLE_OPT_IN; ?>"><?= esc_html__( 'Double Opt-In', 'expertsender-cdp' ); ?></option>
                    </select>
                </div>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'ID wiadomości potwierdzającej w trybie Double Opt-In', 'expertsender-cdp' ); ?></label>
                    <input type="number" name="<?= self::OPTION_FORM_CUSTOMER_SETTINGS_MESSAGE_ID; ?>" value="<?= $options[self::OPTION_FORM_CUSTOMER_SETTINGS_MESSAGE_ID]; ?>" <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[ self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE ] ) { echo 'disabled'; } else { echo 'required="true"'; } ?>/>
                </div>
                <div class="es-divider"></div>
                <h3><?= esc_html__( 'Checkout', 'expertsender_cdp' ); ?></h3>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Tekst wyświetlany przed zgodami', 'expertsender-cdp' ); ?></label>
                    <input type="text" name="<?= self::OPTION_FORM_CHECKOUT_TEXT_BEFORE; ?>" value="<?= $options[self::OPTION_FORM_CHECKOUT_TEXT_BEFORE]; ?>" />
                </div>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Tryb formularza', 'expertsender-cdp' ); ?></label>
                    <select name="<?= self::OPTION_FORM_CHECKOUT_TYPE ?>" class="es-form-type-select">
                        <option <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[self::OPTION_FORM_CHECKOUT_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_SINGLE_OPT_IN; ?>"><?= esc_html__( 'Single Opt-In', 'expertsender-cdp' ); ?></option>
                        <option <?php if ( self::OPTION_VALUE_DOUBLE_OPT_IN === $options[self::OPTION_FORM_CHECKOUT_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_DOUBLE_OPT_IN; ?>"><?= esc_html__( 'Double Opt-In', 'expertsender-cdp' ); ?></option>
                    </select>
                </div>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'ID wiadomości potwierdzającej w trybie Double Opt-In', 'expertsender-cdp' ); ?></label>
                    <input type="number" name="<?= self::OPTION_FORM_CHECKOUT_MESSAGE_ID; ?>" value="<?= $options[self::OPTION_FORM_CHECKOUT_MESSAGE_ID]; ?>" <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[ self::OPTION_FORM_CHECKOUT_TYPE ] ) { echo 'disabled'; } else { echo 'required="true"'; } ?> />
                </div>
                <div class="es-divider"></div>
                <h3><?= esc_html__( 'Newsletter', 'expertsender_cdp' ); ?></h3>

                <span><?= esc_html__('Aby zgody z formularzy newsletter były zbierane, należy:', 'expertsender-cdp'); ?></span>
                <br/>
                <span><?= esc_html__('- dodać atrybut', 'expertsender-cdp'); ?> <i>data-ecdp-field="email"</i> <?= esc_html__(' do pola email', 'expertsender-cdp'); ?></span>
                <br/>
                <span><?= esc_html__('- dodać atrybut', 'expertsender-cdp'); ?> <i>data-ecdp-field="newsletter-submit"</i> <?= esc_html__(' do przycisku submit', 'expertsender-cdp'); ?></span>
                <br/><br/>

                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Tryb formularza', 'expertsender_cdp' ); ?></label>
                    <select name="<?= self::OPTION_FORM_NEWSLETTER_TYPE ?>" class="es-form-type-select">
                        <option <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[self::OPTION_FORM_NEWSLETTER_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_SINGLE_OPT_IN; ?>"><?= esc_html__( 'Single Opt-In', 'expertsender-cdp' ); ?></option>
                        <option <?php if ( self::OPTION_VALUE_DOUBLE_OPT_IN === $options[self::OPTION_FORM_NEWSLETTER_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_DOUBLE_OPT_IN; ?>"><?= esc_html__( 'Double Opt-In', 'expertsender-cdp' ); ?></option>
                    </select>
                </div>
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'ID wiadomości potwierdzającej w trybie Double Opt-In', 'expertsender-cdp' ); ?></label>
                    <input type="number" name="<?= self::OPTION_FORM_NEWSLETTER_MESSAGE_ID; ?>" value="<?= $options[self::OPTION_FORM_NEWSLETTER_MESSAGE_ID]; ?>" <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[ self::OPTION_FORM_NEWSLETTER_TYPE ] ) { echo 'disabled'; } else { echo 'required="true"'; } ?> />
                </div>
                <button class="submit es-button" type="submit"><?= esc_html__( 'Zapisz zmiany', 'expertsender-cdp' ); ?></button>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const formTypeSelects = document.querySelectorAll('.es-form-type-select');
                formTypeSelects.forEach(function (select) {
                    select.addEventListener('change', function () {
                        const self = this;
                        const matches = this.name.matchAll(/expertsender_cdp_([a-z_]+)_form_type/g);
                        matches.forEach(function (match) {
                            const confirmationMessageInput = document.querySelector('[name="expertsender_cdp_' + match[1] + '_form_message_id"]');
                            
                            if ('double-opt-in' === self.value) {
                                confirmationMessageInput.setAttribute('required', 'true');
                                confirmationMessageInput.removeAttribute('disabled');
                            } else {
                                confirmationMessageInput.removeAttribute('required');
                                confirmationMessageInput.setAttribute('disabled', 'true');
                            }
                        });
                    })
                });
            })
        </script>
    <?php
    }

    /**
     * @return void
     */
    public function handle_consent_forms_submit()
    {
        if ( isset( $_POST[self::FORM_CONSENT_FORMS] ) ) {
            foreach ( $_POST as $option => $value ) {
                if ( self::FORM_CONSENT_FORMS === $option ) {
                    continue;
                }

                update_option( $option, $value );
            }

            $this->add_admin_success_notice();
        }
    }

    public function render_order_status_mapping_page()
    {
        $this->check_permissions();
        $this->check_api_key();
        $is_error = $_GET['es_is_error'] ?? false;

        if ( true === $is_error && isset( $_POST['orderMapping'] ) ) {
            $orderStatusMappings = $_POST['orderMapping'];
            $orderStatusMappings = array_map( function ( $mapping ) {
                $mapping['wp_order_statuses'] = implode( ',', $mapping['wp_order_statuses'] ?? array() );
                return $mapping;
            }, $orderStatusMappings );
        } else {
            $orderStatusMappings = es_get_all_order_status_mappings();
        }

        $wpStatuses = $this->expertsender_cdp_get_wp_order_statuses();
        $ecdpStatuses = $this->expertsender_cdp_get_ecdp_order_statuses();
        $last_id = es_get_max_order_status_mapping_id() + 1;
    ?>

        <div class="wrap es-order-status-mappings-page">
            <h1 class="es-bold"><?= esc_html( get_admin_page_title() ); ?></h1>
            <form id="expertSenderOrderStatusMappingsForm" method="post" action="">
                <input type="hidden" name="expertsender_cdp-order-status-mapping-form">
                <div id="orderStatusMapping" class="mappingSection">
                    <button type="button" class="addPairBtn es-button"><?= esc_html__( 'Dodaj', 'expertsender-cdp' ); ?></button>
                    <div class="es-input-pairs-container" data-slug="product">
                        <?php foreach ($orderStatusMappings as $orderMapping): ?>
                            <?php $mapping_wp_statuses = es_get_order_status_mapping_wc_statuses( $orderMapping ); ?>
                            <div class="es-input-pair">
                                <input type="hidden" name="orderMapping[<?= esc_attr( $orderMapping['id'] ); ?>][id]" value="<?= esc_attr( $orderMapping['id'] ); ?>"/>

                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Statusy WC', 'expertsender-cdp' ); ?></label>
                                    <select name="orderMapping[<?= esc_attr($orderMapping['id']);?>][wp_order_statuses][]" multiple>
                                        <?php foreach ( $wpStatuses as $status ): ?>
                                            <option value="<?= esc_attr( $status ); ?>"<?php if ( in_array( $status, $mapping_wp_statuses ) ) { echo ' selected'; } ?>><?= esc_html( $status ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="es-input-wrap es-custom-order-statuses-wrap">
                                    <label><?= esc_html__( 'Niestandardowe statusy WC', 'expertsender-cdp' ); ?></label>
                                    <input type="text" name="orderMapping[<?= esc_attr( $orderMapping['id'] ); ?>][wp_custom_order_statuses]" value="<?= esc_attr( $orderMapping['wp_custom_order_statuses'] ); ?>" placeholder="<?= esc_attr__( 'Statusy rozdzielone przecinkiem', 'expertsender-cdp' ); ?>"/>
                                </div>
                                <div class="es-input-wrap">
                                    <label><?= esc_html__( 'Status ECDP', 'expertsender-cdp' ); ?></label>
                                    <select name="orderMapping[<?= esc_attr( $orderMapping['id'] ); ?>][ecdp_order_status]">
                                        <?php foreach ($ecdpStatuses as $status): ?>
                                            <option value="<?= esc_attr( $status ); ?>"<?php if ( $status === $orderMapping['ecdp_order_status'] ) { echo ' selected'; } ?>><?= esc_html( $status ); ?></option>
                                        <?php endforeach;?>
                                    </select>
                                </div>
                                <button class="removeButton es-button es-remove-button" type="button"><?= esc_html__( 'Usuń', 'expertsender-cdp' ); ?></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">
                <button class="submit es-button" type="submit"><?= esc_html__( 'Zapisz zmiany', 'expertsender-cdp' ); ?></button>
            </form>

            <template id="order_status_mapping_id">
                <input type="hidden"/> 
            </template>
            <template id="wpstatus">
                <div class="es-input-wrap">
                    <label><?= esc_html__( 'Statusy WC', 'expertsender-cdp' ); ?></label>
                    <select multiple>
                        <?php
                        foreach ( $wpStatuses as $status ) {
                            echo '<option value="' . esc_attr($status) . '">' . esc_html($status) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </template>

            <template id="wp_custom_order_statuses">
                <div class="es-input-wrap es-custom-order-statuses-wrap">
                    <label><?= esc_html__( 'Niestandardowe statusy WC', 'expertsender-cdp' ); ?></label>
                    <input type="text"/>
                </div>
            </template>

            <template id="ecdpstatus">
                <div class="es-input-wrap">
                    <label><?= esc_html__(' Status ECDP', 'expertsender-cdp' ); ?></label>
                    <select>
                        <?php foreach ($ecdpStatuses as $status) {
                            echo "<option value=\"$status\">$status</option>";
                        } ?>
                    </select>
                </div>
            </template>
        </div>

        <script>
            var removeButtons = document.querySelectorAll('.removeButton');

            removeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var inputPair = this.parentElement;
                    inputPair.remove();
                });
            });

            document.addEventListener("DOMContentLoaded", function() {
                const sections = document.querySelectorAll(".mappingSection");

                sections.forEach(function(section) {
                    const inputPairsContainer = section.querySelector(".es-input-pairs-container");
                    const addPairBtn = section.querySelector(".addPairBtn");

                    addPairBtn.addEventListener("click", function() {
                        createInputPair(inputPairsContainer);
                    });
                });

                function createInputPair(container) {
                    const pairDiv = document.createElement("div");
                    pairDiv.classList.add("es-input-pair");

                    var id = document.getElementById("idCounter").value;
                    var slug = 'orderMapping';

                    const idTemplate = document.querySelector('#order_status_mapping_id');
                    const idTemplateContent = idTemplate.content.cloneNode(true);
                    const idInput = idTemplateContent.querySelector('input');
                    idInput.name = `${slug}[${id}][id]`;
                    idInput.value = id;
                    var template = document.querySelector("#wpstatus");
                    const wpSelect = template.content.cloneNode(true);
                    let wp = wpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][wp_order_statuses][]";

                    const customOrderStatusesTemplate = document.querySelector('#wp_custom_order_statuses');
                    const customOrderStatusesInput = customOrderStatusesTemplate.content.cloneNode(true);
                    customOrderStatusesInput.querySelector('input').name = `${slug}[${id}][wp_custom_order_statuses]`;

                    var template2 = document.querySelector("#ecdpstatus");
                    const ecdpSelect = template2.content.cloneNode(true);
                    let td = ecdpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][ecdp_order_status]";

                    const removeBtn = document.createElement("button");
                    removeBtn.textContent = '<?= esc_html__( 'Usuń', 'expertsender-cdp' ); ?>';
                    removeBtn.type = "button"
                    removeBtn.classList.add('es-button');
                    removeBtn.addEventListener("click", function() {
                        pairDiv.remove();
                    });

                    pairDiv.appendChild(idInput);
                    pairDiv.appendChild(wpSelect);
                    pairDiv.appendChild(customOrderStatusesInput);
                    pairDiv.appendChild(ecdpSelect);
                    pairDiv.appendChild(removeBtn);

                    container.appendChild(pairDiv);
                    document.getElementById("idCounter").value = parseInt(id) + 1;
                }
            });
        </script>
    <?php
    }

    public function expertsender_cdp_get_wp_order_statuses()
    {
        return [
            'placed',
            'paid',
            'pending',
            'processing',
            'on-hold',
            'completed',
            'cancelled',
            'refunded',
            'failed',
        ];
    }
    public function expertsender_cdp_get_ecdp_order_statuses()
    {
        return ['Placed', 'Paid', 'Completed', 'Cancelled'];
    }

    /**
     * @return void
     */
    public function expertsender_cdp_order_status_mapping_handle_form_submission() {
        if ( isset( $_POST[ 'expertsender_cdp-order-status-mapping-form' ] ) ) {
            $current_data = es_get_all_order_status_mappings();
            es_truncate_order_status_mappings();

            if ( isset( $_POST['orderMapping'] ) ) {
                $errors = es_validate_order_status_mapping_data( $_POST['orderMapping'] );

                if ( ! empty ( $errors ) ) {
                    foreach ( $errors as $error ) {
                        $this->add_admin_error_notice( $error );
                    }

                    $_GET['es_is_error'] = true;

                    if ( ! empty( $current_data ) ) {
                        es_insert_order_status_mappings( $current_data );
                    }

                    return;
                }

                $result = es_insert_order_status_mappings( $_POST['orderMapping'] );

                if ( false === $result ) {
                    if ( ! empty( $current_data ) ) {
                        es_insert_order_status_mappings( $current_data );
                    }
                    
                    $this->add_admin_error_notice(
                        __( 'Wystąpił błąd w trakcie zapisywania mapowań.', 'expertsender-cdp' )
                    );
                } else {
                    $this->add_admin_success_notice();
                }
            } else if ( ! empty ( $current_data ) ) {
                $this->add_admin_success_notice();
            }
        }
    }

    public function render_order_synchronize_page()
    {
        $this->check_permissions();
        $this->check_api_key();
        global $wpdb;
        $table_name = $wpdb->prefix . 'expertsender_cdp_requests';
        $query = "SELECT MAX(synchronization_id) AS last_sync FROM $table_name";
        $result = $wpdb->get_row($query);
        $sync_id = 0;

        if ($result->last_sync > 0) {
            $sync_id = $result->last_sync;
        }

        $expertSenderRequests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s AND synchronization_id = %d",
                'order',
                $sync_id
            )
        );

        $sent = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s AND synchronization_id = %d AND is_sent = 1 AND response IS NULL",
                'order',
                $sync_id
            )
        );

        $failed = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s AND synchronization_id = %d AND is_sent = 1 AND response IS NOT NULL",
                'order',
                $sync_id
            )
        );

        $toBeSend = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s AND synchronization_id = %d AND is_sent = 0 AND response IS NULL",
                'order',
                $sync_id
            )
        );
    ?>

        <script>
            function setMaxDate() {
                var today = new Date();
                var maxDate = new Date(today);
                maxDate.setFullYear(today.getFullYear() - 2);

                var datetimeInputs = document.querySelectorAll('input[type="datetime-local"]');
                datetimeInputs.forEach(function(input) {
                    input.setAttribute('min', maxDate.toISOString().slice(0, 16));
                });
            }

            window.onload = setMaxDate;
        </script>

        <div class="wrap">
            <h1 class="es-bold"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form id="es-synchronize-ordes-form" method="post" action="">
                <input type="hidden" name="expertsender_cdp-order-synchronize-form">
                <label for="datefrom"><?= esc_html__( 'Od:', 'expertsender-cdp' ); ?></label>
                <input type="datetime-local" id="datefrom" name="datefrom"><br><br>
                <label for="dateto"><?= esc_html__( 'Do:', 'expertsender-cdp' ); ?></label>
                <input type="datetime-local" id="dateto" name="dateto"><br><br>
                <button class="submit es-button" type="submit"><?= esc_html__( 'Synchronizuj', 'expertsender-cdp' ); ?></button>
            </form>
            <h2><?= esc_html__( 'Ostatnia synchronizacja', 'expertsender-cdp' ); ?></h2>
            <div><?= esc_html( sprintf( __( 'Synchronizacja zamówień: %1$s', 'expertsender-cdp' ), count( $expertSenderRequests ) ) ); ?></div>
            <div><?= esc_html( sprintf( __( 'Zsynchronizowano: %1$s', 'expertsender-cdp' ), count( $sent ) ) ); ?></div>
            <div><?= esc_html( sprintf( __( 'W kolejce: %1$s', 'expertsender-cdp' ), count( $toBeSend ) ) ); ?></div>
            <div><?= esc_html( sprintf( __( 'Błędy: %1$s', 'expertsender-cdp' ), count( $failed ) ) ); ?></div>
            <div class="es-divider"></div>
            <div class="log-container" id="logContainer">
                <?php foreach ($failed as $fail) {
                    echo '<div class=log-message>' .
                        $fail->id .
                        ':' .
                        $fail->response .
                        '</div>';
                } ?>
            </div>
        </div>
        <style>
            .log-container {
                width: 400px;
                height: 300px;
                overflow-y: scroll;
                border: 1px solid #ccc;
                padding: 10px;
                background-color: #f9f9f9;
            }

            .log-message {
                margin-bottom: 5px;
                font-family: Arial, sans-serif;
            }
        </style>
<?php
    }

    /**
     * @return void
     */
    public function expertsender_cdp_order_synchronize_submission()
    {
        if ( isset( $_POST[ 'expertsender_cdp-order-synchronize-form' ] ) ) {
            $start_date = $_POST[ 'datefrom' ];
            $end_date = $_POST[ 'dateto' ];
            $page = 0;
            $sync_id = scortea_get_next_synchronization_id();

            while ( ++$page ) {
                $orders_result = es_get_orders_by_dates( $start_date, $end_date, $page );
                $orders = $orders_result->orders;
                $order_request = new ExpertSender_CDP_Order_Request();
                $order_ids = array();

                if ( ! empty( $orders ) ) {
                    /** @var \WC_Order $order */
                    foreach ( $orders as $order ) {
                        if ( $order instanceof WC_Order ) {
                            $order_id = $order->get_id();
                        } else {
                            $order_id = $order->get_parent_id();
                            $order = wc_get_order( $order_id );
                        }

                        if ( ! in_array( $order_id, $order_ids ) ) {
                            $order_request->expertsender_cdp_order_save_request(
                                $order_id,
                                $order,
                                $sync_id
                            );

                            $order_ids[] = $order_id;
                        }
                    }
                }

                if ( $page >= $orders_result->max_num_pages ) {
                    break;
                }
            }
        }
    }

    /**
     * @param string|null $message
     * 
     * @return void
     */
    public function expertsender_cdp_success_notice( $message = null ) {
        $this->add_admin_success_notice( $message );
    }

    public function expertsender_cdp_data_error_notice( $message = null ) {
        $this->add_admin_error_notice( $message );
    }

    /**
     * @param string|null $message
     * 
     * @return void
     */
    private function add_admin_success_notice( $message = null ) {
        add_action( 'admin_notices', function () use ( $message ) {
            if ( null === $message ) {
                $message = __( 'Zmiany zostały zapisane.', 'expertsender-cdp' );
            }

            $message = esc_html( $message );

            $notice = <<<HTML
                <div class="notice is-dismissible notice-success">
                    <p>$message</p>
                </div>
            HTML;

            echo $notice;
        } );
    }

    /**
     * @param string|null $message
     * 
     * @return void
     */
    private function add_admin_error_notice( $message = null ) {
        add_action( 'admin_notices', function () use ( $message ) {
            if ( null === $message ) {
                $message = __( 'Serwer napotkał błąd. Spróbuj ponownie później.', 'expertsender-cdp' );
            }

            $message = esc_html( $message );

            $notice = <<<HTML
                <div class="notice is-dismissible notice-error">
                    <p>$message</p>
                </div>
            HTML;

            echo $notice;
        } );
    }

    /**
     * @return void
     */
    private function check_permissions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(
                __( 'Nie masz wystarczających uprawnień do tej strony.', 'expertsender-cdp' )
            );
        }
    }

    /**
     * @return void
     */
    private function check_api_key() {
        if ( ! get_option( self::OPTION_API_KEY ) ) {
            wp_safe_redirect(
                '/wp-admin/admin.php?page=expertsender_cdp-settings&' . self::PARAMETER_MISSING_API_KEY
                . '=true'
            );
        }
    }

    /**
     * @param \WP_Error $wp_error
     *
     * @return void
     */
    private function print_api_error_message( $wp_error ) {
        $error_message = $wp_error->get_error_message();

        echo esc_html( 
            sprintf( __( 'Coś poszło nie tak: %1$s', 'expertsender-cdp' ), $error_message )
        );
    }
}
