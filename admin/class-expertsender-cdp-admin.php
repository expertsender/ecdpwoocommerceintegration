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
            $this->add_admin_error_notice( 'Przed ustawieniami mapowań i synchronizacją zamówień należy uzupełnić klucz API.' );
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
            'Ustawienia ogólne', // Page title
            'ExpertSender CDP', // Menu title
            'manage_options', // Capability
            'expertsender_cdp-settings', // Menu slug
            [$this, 'render_settings_page'] // Callback function to render the settings page
        );
    }

    public function add_plugin_admin_mappings()
    {
        add_submenu_page(
            'expertsender_cdp-settings', // Page title
            'Mapowania pól',
            'Mapowania pól', // Menu title
            'manage_options', // Capability
            'expertsender_cdp-settings-mappings', // Menu slug
            [$this, 'render_mappings_page'] // Callback function to render the settings page
        );
    }

    public function add_plugin_admin_consents()
    {
        add_submenu_page(
            'expertsender_cdp-settings', // parent
            'Mapowania zgód',
            'Mapowania zgód', // Menu title
            'manage_options', // Capability
            'expertsender_cdp-settings-consents', // Menu slug
            [$this, 'render_consents_page'] // Callback function to render the settings page
        );
    }

    /**
     * @return void
     */
    public function add_plugin_admin_consent_forms()
    {
        add_submenu_page(
            'expertsender_cdp-settings', // parent
            'Ustawienia formularzy ze zgodami',
            'Ustawienia formularzy ze zgodami', // Menu title
            'manage_options', // Capability
            'expertsender_cdp-settings-consent-forms', // Menu slug
            array( $this, 'render_consent_forms_page' ) // Callback function to render the settings page
        );
    }

    public function add_plugin_admin_order_status_mapping()
    {
        add_submenu_page(
            'expertsender_cdp-settings', // parent
            'Mapowania statusów zamówienia',
            'Mapowania statusów zamówienia', // Menu title
            'manage_options', // Capability
            'expertsender_cdp-settings-order-status-mapping', // Menu slug
            [$this, 'render_order_status_mapping_page'] // Callback function to render the settings page
        );
    }

    public function add_plugin_admin_synchronize_orders()
    {
        add_submenu_page(
            'expertsender_cdp-settings', // parent
            'Synchronizacja zamówień',
            'Synchronizacja zamówień', // Menu title
            'manage_options', // Capability
            'expertsender_cdp-settings-synchronize-orders', // Menu slug
            [$this, 'render_order_synchronize_page'] // Callback function to render the settings page
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
                        <th scope="row">Klucz API</th>
                        <td>
                            <input type="password" name="<?= ExpertSender_CDP_Admin::OPTION_API_KEY; ?>" value="<?php echo esc_attr( get_option( self::OPTION_API_KEY ) ); ?>" />
                        </td>
                        <td>
                            <p>Skąd pobrać klucz API? </p>
                            <ol>
                                <li><a href="https://client.ecdp.app/Account/SignIn">Zaloguj się do systemu ECDP</a></li>
                                <li>Wybierz odpowiednią jednostkę</li>
                                <li>Przejdź do zakładki Settings -&gt; API, a następnie skopiuj klucz i wklej w ustawieniach wtyczki.</li>
                            </ol>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Włącz skrypt śledzący ruch</th>
                        <td>
                            <input type="checkbox" class="es-input" id="expertsender_cdp_enable_script" name="expertsender_cdp_enable_script" value="1" <?= $checked ?> />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Skrypt śledzący ruch</th>
                        <td>
                            <textarea name="expertsender_cdp_script" id="expertsender_cdp_script" rows="8" cols="50"><?php echo esc_textarea( base64_decode( get_option( 'expertsender_cdp_script' ) ) ); ?></textarea>
                        </td>
                        <td>
                            <p>Skąd pobrać skrypt? </p>
                            <ol>
                                <li><a href="https://client.ecdp.app/Account/SignIn">Zaloguj się do systemu ECDP</a></li>
                                <li>Wybierz odpowiednią jednostkę</li>
                                <li>Przejdź do zakładki Settings -&gt; Web Tracking, a następnie przy odpowiedniej stronie internetowej kliknij ikonkę "Tracking code".</li>
                                <li>Skopiuj i wklej w ustawieniach wtyczki.</li>
                            </ol>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">ID strony internetowej</th>
                        <td>
                            <input type="text" name="expertsender_cdp_website_id" value="<?php echo esc_attr( get_option( 'expertsender_cdp_website_id' ) ); ?>" />
                        </td>
                        <td>
                            <p>Skąd pobrać numer ID strony internetowej? </p>
                            <ol>
                                <li><a href="https://client.ecdp.app/Account/SignIn">Zaloguj się do systemu ECDP</a></li>
                                <li>Wybierz odpowiednią jednostkę</li>
                                <li>Przejdź do zakładki Settings -&gt; Webtracking, a następnie znajdź odpowiednią stronę internetową.</li>
                                <li>Skopiuj ID i wklej w ustawieniach wtyczki.</li>
                            </ol>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            Wysyłaj numer telefonu użytkowników do ExpertSender CDP
                        </th>
                        <td>
                            <input type="checkbox" class="es-input" id="expertsender_cdp_enable_phone" name="expertsender_cdp_enable_phone" value="1" <?= $phoneChecked ?> />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Włącz logi</th>
                        <td>
                            <input type="checkbox" class="es-input" id="<?= self::OPTION_ENABLE_LOGS; ?>" name="<?= self::OPTION_ENABLE_LOGS; ?>" value="1" <?= $enableLogs; ?>/>
                        </td>
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
                <button class="es-button submit" type="submit">Zapisz zmiany</button>
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
        global $wpdb;
        $table_name = $wpdb->prefix . 'expertsender_cdp_mappings';

        $customerMappings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s",
                $this::RESOURCE_CUSTOMER
            )
        );

        $productMappings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s",
                $this::RESOURCE_PRODUCT
            )
        );

        $orderMappings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE resource_type = %s",
                $this::RESOURCE_ORDER
            )
        );

        $orderKeys = $this->get_order_keys();
        $productKeys = $this->get_product_keys();
        $customerKeys = $this->get_customer_keys();
        $customerOptions = $this->expertsender_cdp_get_customer_attributes_from_api();
        $productOptions = $this->expertsender_cdp_get_product_attributes_from_api();
        $orderOptions = $this->expertsender_cdp_get_order_attributes_from_api();
        $last_id = $wpdb->get_var("SELECT MAX(id) FROM $table_name") + 1;
    ?>

        <div class="wrap">
            <h1 class="es-bold"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form id="es-field-mappings-form" method="post" action="">
                <input type="hidden" name="expertsender_cdp-mapping-form">
                <div id="productMapping" class="mappingSection">
                    <h2>Mapowania pól produktów</h2>
                    <button type="button" class="addPairBtn es-button">Dodaj</button>

                    <div class="es-input-pairs-container" data-slug="product">
                        <?php foreach ($productMappings as $productMapping) {
                            echo '<div class="es-input-pair">';
                            echo '<select name="product[' . $productMapping->id . '][wp_field]">';

                            foreach ($productKeys as $productKey => $value) {
                                $selected = $productMapping->wp_field == $value ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$value</option>";
                            }

                            echo '</select>';
                            echo '<select name="product[' . $productMapping->id . '][ecdp_field]">';

                            foreach ($productOptions as $value) {
                                $value = $value->name;
                                $selected = $productMapping->ecdp_field == $value ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$value</option>";
                            }

                            echo '</select><button class="removeButton es-button" type="button">Usuń</button></div>';
                        } ?>
                    </div>
                </div>

                <div id="customerMapping" class="mappingSection">
                    <h2>Mapowania pól użytkowników</h2>
                    <button type="button" class="addPairBtn es-button">Dodaj</button>

                    <div class="es-input-pairs-container" data-slug="customer">
                        <?php foreach ($customerMappings as $customerMapping) {
                            echo '<div class="es-input-pair">';
                            echo '<select name="customer[' . $customerMapping->id . '][wp_field]">';

                            foreach ($customerKeys as $customerKey => $value) {
                                $selected = $customerMapping->wp_field == $customerKey ? 'selected' : '';
                                echo "<option value=\"$customerKey\" $selected>$customerKey</option>";
                            }

                            echo '</select>';
                            echo '<select name="customer[' . $customerMapping->id . '][ecdp_field]">';

                            foreach ($customerOptions as $value) {
                                $value = $value->name;
                                $selected = $customerMapping->ecdp_field == $value ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$value</option>";
                            }

                            echo '</select><button class="removeButton es-button" type="button">Usuń</button></div>';
                        } ?>
                    </div>
                </div>

                <div id="orderMapping" class="mappingSection">
                    <h2>Mapowania pól zamówień</h2>
                    <button type="button" class="addPairBtn es-button">Dodaj</button>

                    <div class="es-input-pairs-container" data-slug="order">
                        <?php foreach ($orderMappings as $orderMapping) {
                            echo '<div class="es-input-pair">';
                            echo '<select name="order[' . $orderMapping->id . '][wp_field]">';

                            foreach ($orderKeys as $orderKey => $orderValue) {
                                $selected = $orderMapping->wp_field == $orderKey ? 'selected' : '';
                                echo "<option value=\"$orderKey\" $selected>$orderKey</option>";
                            }

                            echo '</select>';
                            echo '<select name="order[' . $orderMapping->id . '][ecdp_field]">';

                            foreach ($orderOptions as $value) {
                                $value = $value->name;
                                $selected = $orderMapping->ecdp_field == $value ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$value</option>";
                            }

                            echo '</select>';
                            echo '<button class="removeButton es-button" type="button">Usuń</button></div>';
                        } ?>
                    </div>
                </div>
                <input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">
                <button class="es-button submit" type="submit">Zapisz zmiany</button>
            </form>

            <template id="productselect">
                <select name="">
                    <?php foreach ($productOptions as $value) {
                        $value = $value->name;
                        echo "<option value=\"$value\">$value</option>";
                    } ?>
                </select>
            </template>

            <template id="orderselect">
                <select name="">
                    <?php foreach ($orderOptions as $value) {
                        $value = $value->name;
                        echo "<option value=\"$value\">$value</option>";
                    } ?>
                </select>
            </template>

            <template id="customerselect">
                <select name="">
                    <?php foreach ($customerOptions as $value) {
                        $value = $value->name;
                        echo "<option value=\"$value\">$value</option>";
                    } ?>
                </select>
            </template>

            </template>

            <template id="orderkeys">
                <select name="">
                    <?php foreach ($orderKeys as $key => $value) {
                        echo "<option value=\"$key\">$key</option>";
                    } ?>
                </select>
            </template>

            <template id="productkeys">
                <select name="">
                    <?php foreach ($productKeys as $key => $value) {
                        echo "<option value=\"$value\">$value</option>";
                    } ?>
                </select>
            </template>

            <template id="customerkeys">
                <select name="">
                    <?php foreach ($customerKeys as $key => $value) {
                        echo "<option value=\"$key\">$key</option>";
                    } ?>
                </select>
            </template>
        </div>

        <script>
            // Get all remove buttons
            var removeButtons = document.querySelectorAll('.removeButton');

            // Add click event listener to each remove button
            removeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    // Find the parent element with class 'inputPair' and remove it
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
                    var template = document.querySelector("#" + slug + "keys");
                    const wpSelect = template.content.cloneNode(true);
                    let wp = wpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][wp_field]";

                    console.log("#" + slug + "select");
                    var template2 = document.querySelector("#" + slug + "select");
                    const ecdpSelect = template2.content.cloneNode(true);
                    let td = ecdpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][ecdp_field]";

                    const removeBtn = document.createElement("button");
                    removeBtn.textContent = "Usuń";
                    removeBtn.type = "button"
                    removeBtn.classList.add('es-button');
                    removeBtn.addEventListener("click", function() {
                        pairDiv.remove();
                    });

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
    public function expertsender_cdp_mappings_handle_form_submission()
    {
        if (isset($_POST['expertsender_cdp-mapping-form'])) {
             /** @var \wpdb $wpdb */
            global $wpdb;
            $resources = array(
                self::RESOURCE_CUSTOMER,
                self::RESOURCE_ORDER,
                self::RESOURCE_PRODUCT
            );
            $table_name = $wpdb->prefix . 'expertsender_cdp_mappings';
            $current_data = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
            $wpdb->query("DELETE FROM $table_name");
            $insert_query = <<<SQL
                INSERT INTO $table_name (resource_type, wp_field, ecdp_field)
                VALUES
            SQL;
            $placeholders = array();
            $values = array();
            
            foreach ($_POST as $resource => $mappings) {
                if ( in_array( $resource, $resources ) ) {
                    foreach ( $mappings as $mapping ) {
                        $placeholders[] = "('%s', '%s', '%s')";
                        array_push(
                            $values,
                            $resource,
                            $mapping[ 'wp_field' ],
                            $mapping[ 'ecdp_field' ]
                        );
                    }
                }
            }

            if ( ! empty( $values ) ) {
                $query = $insert_query . implode( ', ', $placeholders );
                $inserted = $wpdb->query( $wpdb->prepare( $query, $values ) );

                if ( false === $inserted ) {
                    if ( ! empty( $current_data ) ) {
                        $placeholders = array();
                        $values = array();

                        foreach ( $current_data as $current_row ) {
                            $placeholders[] = "('%s', '%s', '%s')";
                            array_push(
                                $values,
                                $current_row[ 'resource_type' ],
                                $current_row[ 'wp_field' ],
                                $current_row[ 'ecdp_field' ]
                            );
                        }

                        $query = $insert_query . implode( ', ', $placeholders );
                        $wpdb->query( $wpdb->prepare( $query, $values) );
                    }
                    
                    $this->add_admin_error_notice('Zduplikowana wartość: każde pole WooCommerce dla każdego zasobu powinno być zmapowane tylko raz.');
                } else {
                    $this->add_admin_success_notice();
                }
            } else if (! empty ( $current_data ) ) {
                $this->add_admin_success_notice();
            }
        }
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

        // Send GET request to the API
        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Coś poszło nie tak: $error_message";
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

        // Send GET request to the API
        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Coś poszło nie tak: $error_message";
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

        // Send GET request to the API
        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Coś poszło nie tak: $error_message";
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    public function render_consents_page()
    {
        $this->check_permissions();
        $this->check_api_key();
        global $wpdb;
        $table_name = $wpdb->prefix . 'expertsender_cdp_consents';
        $consents = $wpdb->get_results("SELECT * FROM $table_name");
        $apiConsents = $this->expertsender_cdp_get_consents_from_api();
        $consentLocations = $this->expertsender_cdp_get_consents_locations();
        $last_id = $wpdb->get_var("SELECT MAX(id) FROM $table_name") + 1;
    ?>
        <div class="wrap">
            <h1 class="es-bold"><?= esc_html( get_admin_page_title() ); ?></h1>
            <form id="expertSenderConsentsForm" method="post" action="">
                <input type="hidden" name="expertsender_cdp-consents-form">
                <div id="consentSection" class="consentSection">
                    <button type="button" class="addPairBtn es-button">Dodaj</button>
                    <div class="es-input-pairs-container">
                        <?php foreach ( $consents as $consent ) {
                            echo '<div class="es-input-pair">';
                            echo '<select name="consent[' . $consent->id . '][api_consent_id]">';

                            foreach ( $apiConsents as $value ) {
                                $selected = $consent->api_consent_id == $value->id ? 'selected' : '';
                                echo "<option value=\"$value->id\" $selected>$value->name</option>";
                            }

                            echo '</select>';
                            echo '<select name="consent[' . $consent->id . '][consent_location]">';

                            foreach ( $consentLocations as $value => $label ) {
                                $selected = $consent->consent_location == $value ? 'selected' : '';
                                echo "<option value=\"$value\" $selected>$label</option>";
                            }

                            echo '</select>';
                            echo '<input type="text" placeholder="Tekst zgody" required="true" name="consent[' . $consent->id . '][consent_text]" value="' . $consent->consent_text . '">';
                            echo '<button class="removeButton es-button" type="button">Usuń</button></div>';
                        } ?>
                    </div>
                    <input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">
                    <button class="es-button submit" type="submit">Zapisz zmiany</button>
            </form>

            <template id="apiConsentsTemplate">
                <select name="">
                    <?php foreach ($apiConsents as $value) {
                        echo "<option value=\"$value->id\">$value->name</option>";
                    } ?>
                </select>
            </template>

            <template id="consentLocationTemplate">
                <select name="">
                    <?php foreach ( $consentLocations as $value => $label ) {
                        echo "<option value=\"$value\">$label</option>";
                    } ?>
                </select>
            </template>
        </div>

        <script>
            // Get all remove buttons
            var removeButtons = document.querySelectorAll('.removeButton');

            // Add click event listener to each remove button
            removeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    // Find the parent element with class 'inputPair' and remove it
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

                    var template = document.querySelector("#apiConsentsTemplate");
                    const apiConsent = template.content.cloneNode(true);
                    apiConsent.querySelectorAll("select")[0].name = "consent[" + id + "][api_consent_id]";

                    var template2 = document.querySelector("#consentLocationTemplate");
                    const consentLocation = template2.content.cloneNode(true);
                    consentLocation.querySelectorAll("select")[0].name = "consent[" + id + "][consent_location]";

                    const removeBtn = document.createElement("button");
                    removeBtn.textContent = "Usuń";
                    removeBtn.type = "button"
                    removeBtn.classList.add('es-button')
                    removeBtn.addEventListener("click", function() {
                        pairDiv.remove();
                    });

                    const consentTextInput = document.createElement("input");
                    consentTextInput.type = "text";
                    consentTextInput.name = "consent[" + id + "][consent_text]";
                    consentTextInput.placeholder = "Tekst zgody";
                    consentTextInput.required = true;


                    pairDiv.appendChild(apiConsent);
                    pairDiv.appendChild(consentLocation);
                    pairDiv.appendChild(consentTextInput);
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

        // Send GET request to the API
        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Coś poszło nie tak: $error_message";
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
            self::FORM_CHECKOUT_KEY => 'Checkout',
            self::FORM_CUSTOMER_SETTINGS_KEY => 'Edycja profilu',
            self::FORM_NEWSLETTER_KEY => 'Newsletter',
            self::FORM_REGISTRATION_KEY => 'Rejestracja'
        );
    }

    /**
     * @return void
     */
    public function expertsender_cdp_consents_handle_form_submission()
    {
        if (isset($_POST['expertsender_cdp-consents-form'])) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'expertsender_cdp_consents';
            $current_data = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
            $wpdb->query("DELETE FROM $table_name");
            $insert_query = <<<SQL
                INSERT INTO $table_name (api_consent_id, consent_location, consent_text)
                VALUES
            SQL;
            $placeholders = array();
            $values = array();

            if (isset($_POST['consent'])) {
                foreach ($_POST['consent'] as $mapping) {
                    $placeholders[] = "('%s', '%s', '%s')";
                    array_push(
                        $values,
                        $mapping[ 'api_consent_id' ],
                        $mapping[ 'consent_location' ],
                        $mapping[ 'consent_text' ]
                    );
                }

                if ( ! empty ( $values ) ) {
                    $query = $insert_query . implode( ', ', $placeholders );
                    $inserted = $wpdb->query( $wpdb->prepare( $query, $values ) );

                    if ( false === $inserted ) {
                        if ( ! empty( $current_data ) ) {
                            $placeholders = array();
                            $values = array();

                            foreach ( $current_data as $current_row ) {
                                $placeholders[] = "('%s', '%s', '%s')";
                                array_push(
                                    $values,
                                    $current_row[ 'api_consent_id' ],
                                    $current_row[ 'consent_location' ],
                                    $current_row[ 'consent_text' ]
                                );
                            }
                        }

                        $this->add_admin_error_notice('Zduplikowana wartość: każda zgoda ECDP dla danego formularza powinna być zmapowana tylko raz.');
                    } else {
                        $this->add_admin_success_notice();
                    }
                } 
            } else if ( ! empty ( $current_data ) ) {
                $this->add_admin_success_notice();
            }
        }
    }

    public function flatten($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result =
                    $result + $this->flatten($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    public function get_order_keys()
    {
        if (class_exists('WooCommerce')) {
            $order = new WC_Order();
            return $this->flatten($order->get_data());
        }
    }

    public function get_customer_keys()
    {
        if (class_exists('WooCommerce')) {
            $order = new WC_Customer();
            return $this->flatten($order->get_data());
        }
    }

    public function get_product_keys()
    {
        $product_attribute_taxonomies = wc_get_attribute_taxonomies();
        if ($product_attribute_taxonomies) {
            $attribute_names = array_map(function ($taxonomy) {
                return $taxonomy->attribute_label;
            }, $product_attribute_taxonomies);

            return $attribute_names;
        }
        return [];
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
                <h3>Rejestracja</h3>
                <div class="input-wrap">
                    <label>Tekst wyświetlany przed zgodami</label>
                    <input type="text" name="<?= self::OPTION_FORM_REGISTRATION_TEXT_BEFORE; ?>" value="<?= $options[self::OPTION_FORM_REGISTRATION_TEXT_BEFORE]; ?>" />
                </div>
                <div class="input-wrap">
                    <label>Tryb formularza</label>
                    <select name="<?= self::OPTION_FORM_REGISTRATION_TYPE ?>" class="es-form-type-select">
                        <option <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[self::OPTION_FORM_REGISTRATION_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_SINGLE_OPT_IN; ?>">Single Opt-In</option>
                        <option <?php if ( self::OPTION_VALUE_DOUBLE_OPT_IN === $options[self::OPTION_FORM_REGISTRATION_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_DOUBLE_OPT_IN; ?>">Double Opt-In</option>
                    </select>
                </div>
                <div class="input-wrap">
                    <label>ID wiadomości potwierdzającej w trybie Double Opt-In</label>
                    <input type="number" name="<?= self::OPTION_FORM_REGISTRATION_MESSAGE_ID; ?>" value="<?= $options[self::OPTION_FORM_REGISTRATION_MESSAGE_ID]; ?>" <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[ self::OPTION_FORM_REGISTRATION_TYPE ] ) { echo 'disabled'; } else { echo 'required="true"'; } ?> />
                </div>
                <div class="es-divider"></div>
                <h3><?= __( 'Edycja profilu', 'expertsender_cdp' ); ?></h3>
                <div class="input-wrap">
                    <label>Tekst wyświetlany przed zgodami</label>
                    <input type="text" name="<?= self::OPTION_FORM_CUSTOMER_SETTINGS_TEXT_BEFORE; ?>" value="<?= $options[self::OPTION_FORM_CUSTOMER_SETTINGS_TEXT_BEFORE]; ?>" />
                </div>
                <div class="input-wrap">
                    <label>Tryb formularza</label>
                    <select name="<?= self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE ?>" class="es-form-type-select">
                        <option <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_SINGLE_OPT_IN; ?>">Single Opt-In</option>
                        <option <?php if ( self::OPTION_VALUE_DOUBLE_OPT_IN === $options[self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_DOUBLE_OPT_IN; ?>">Double Opt-In</option>
                    </select>
                </div>
                <div class="input-wrap">
                    <label>ID wiadomości potwierdzającej w trybie Double Opt-In</label>
                    <input type="number" name="<?= self::OPTION_FORM_CUSTOMER_SETTINGS_MESSAGE_ID; ?>" value="<?= $options[self::OPTION_FORM_CUSTOMER_SETTINGS_MESSAGE_ID]; ?>" <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[ self::OPTION_FORM_CUSTOMER_SETTINGS_TYPE ] ) { echo 'disabled'; } else { echo 'required="true"'; } ?>/>
                </div>
                <div class="es-divider"></div>
                <h3>Checkout</h3>
                <div class="input-wrap">
                    <label>Tekst wyświetlany przed zgodami</label>
                    <input type="text" name="<?= self::OPTION_FORM_CHECKOUT_TEXT_BEFORE; ?>" value="<?= $options[self::OPTION_FORM_CHECKOUT_TEXT_BEFORE]; ?>" />
                </div>
                <div class="input-wrap">
                    <label>Tryb formularza</label>
                    <select name="<?= self::OPTION_FORM_CHECKOUT_TYPE ?>" class="es-form-type-select">
                        <option <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[self::OPTION_FORM_CHECKOUT_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_SINGLE_OPT_IN; ?>">Single Opt-In</option>
                        <option <?php if ( self::OPTION_VALUE_DOUBLE_OPT_IN === $options[self::OPTION_FORM_CHECKOUT_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_DOUBLE_OPT_IN; ?>">Double Opt-In</option>
                    </select>
                </div>
                <div class="input-wrap">
                    <label>ID wiadomości potwierdzającej w trybie Double Opt-In</label>
                    <input type="number" name="<?= self::OPTION_FORM_CHECKOUT_MESSAGE_ID; ?>" value="<?= $options[self::OPTION_FORM_CHECKOUT_MESSAGE_ID]; ?>" <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[ self::OPTION_FORM_CHECKOUT_TYPE ] ) { echo 'disabled'; } else { echo 'required="true"'; } ?> />
                </div>
                <div class="es-divider"></div>
                <h3>Newsletter</h3>
                <div class="input-wrap">
                    <label><?= __( 'Form type', 'expertsender_cdp' ); ?></label>
                    <select name="<?= self::OPTION_FORM_NEWSLETTER_TYPE ?>" class="es-form-type-select">
                        <option <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[self::OPTION_FORM_NEWSLETTER_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_SINGLE_OPT_IN; ?>">Single Opt-In</option>
                        <option <?php if ( self::OPTION_VALUE_DOUBLE_OPT_IN === $options[self::OPTION_FORM_NEWSLETTER_TYPE] ) echo 'selected'; ?> value="<?= self::OPTION_VALUE_DOUBLE_OPT_IN; ?>">Double Opt-In</option>
                    </select>
                </div>
                <div class="input-wrap">
                    <label>ID wiadomości potwierdzającej w trybie Double Opt-In</label>
                    <input type="number" name="<?= self::OPTION_FORM_NEWSLETTER_MESSAGE_ID; ?>" value="<?= $options[self::OPTION_FORM_NEWSLETTER_MESSAGE_ID]; ?>" <?php if ( self::OPTION_VALUE_SINGLE_OPT_IN === $options[ self::OPTION_FORM_NEWSLETTER_TYPE ] ) { echo 'disabled'; } else { echo 'required="true"'; } ?> />
                </div>
                <button class="submit es-button" type="submit">Zapisz zmiany</button>
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
        global $wpdb;
        $table_name = $wpdb->prefix . 'expertsender_cdp_order_status_mappings';

        $orderStatusMappings = $wpdb->get_results("SELECT * FROM $table_name");
        $wpStatuses = $this->expertsender_cdp_get_wp_order_statuses();
        $ecdpStatuses = $this->expertsender_cdp_get_ecdp_order_statuses();

        $last_id = $wpdb->get_var("SELECT MAX(id) FROM $table_name") + 1;
    ?>

        <div class="wrap">
            <h1 class="es-bold"><?= esc_html( get_admin_page_title() ); ?></h1>
            <form id="expertSenderOrderStatusMappingsForm" method="post" action="">
                <input type="hidden" name="expertsender_cdp-order-status-mapping-form">
                <div id="orderStatusMapping" class="mappingSection">
                    <button type="button" class="addPairBtn es-button">Dodaj</button>
                    <?php 
                    echo '<datalist id="expertsender_wp_order_list">';
                    foreach ($wpStatuses as $status) {
                        $status = esc_html($status);
                        echo "<option value=\"$status\">$status</option>";
                    }
                    echo '</datalist>';
                    ?>
                    <div class="es-input-pairs-container" data-slug="product">
                        <?php foreach ($orderStatusMappings as $orderMapping) {
                            echo '<div class="es-input-pair">';
                            echo '<input type="text es-input-list" name="orderMapping[' . $orderMapping->id
                                . '][wp_order_status]" list="expertsender_wp_order_list" value="'
                                . $orderMapping->wp_order_status . '"/>';
                            echo '<select name="orderMapping[' . $orderMapping->id . '][ecdp_order_status]">';

                            foreach ($ecdpStatuses as $status) {
                                $selected =
                                    $orderMapping->ecdp_order_status == $status ? 'selected' : '';

                                echo "<option value=\"$status\" $selected>$status</option>";
                            }

                            echo '
		</select>
			<button class="removeButton es-button" type="button">Usuń</button></div>';
                        } ?>
                    </div>
                </div>
                <input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">
                <button class="submit es-button" type="submit">Zapisz zmiany</button>
            </form>

            <template id="wpstatus">
                <input type="text" list="expertsender_wp_order_list"/>
            </template>

            <template id="ecdpstatus">
                <select name="">
                    <?php foreach ($ecdpStatuses as $status) {
                        echo "<option value=\"$status\">$status</option>";
                    } ?>
                </select>
            </template>
        </div>

        <script>
            // Get all remove buttons
            var removeButtons = document.querySelectorAll('.removeButton');

            // Add click event listener to each remove button
            removeButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    // Find the parent element with class 'inputPair' and remove it
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

                    var template = document.querySelector("#wpstatus");
                    const wpSelect = template.content.cloneNode(true);
                    let wp = wpSelect.querySelectorAll("input")[0].name = slug + "[" + id + "][wp_order_status]";

                    var template2 = document.querySelector("#ecdpstatus");
                    const ecdpSelect = template2.content.cloneNode(true);
                    let td = ecdpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][ecdp_order_status]";

                    const removeBtn = document.createElement("button");
                    removeBtn.textContent = "Usuń";
                    removeBtn.type = "button"
                    removeBtn.classList.add('es-button');
                    removeBtn.addEventListener("click", function() {
                        pairDiv.remove();
                    });

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
            /** @var \wpdb $wpdb */
            global $wpdb;

            $table_name = $wpdb->prefix . 'expertsender_cdp_order_status_mappings';
            $current_data = $wpdb->get_results( "SELECT * FROM $table_name", ARRAY_A );
            $wpdb->query("DELETE FROM $table_name");
            $insert_query = <<<SQL
                INSERT INTO $table_name (wp_order_status, ecdp_order_status)
                VALUES 
            SQL;
            $placeholders = array();
            $values = array();

            if ( isset( $_POST['orderMapping'] ) ) {
                foreach ( $_POST['orderMapping'] as $mapping ) {
                    $placeholders[] = "('%s', '%s')";
                    array_push( $values, $mapping[ 'wp_order_status' ], $mapping[ 'ecdp_order_status' ] );
                }

                if ( ! empty( $values ) ) {
                    $query = $insert_query . implode( ', ', $placeholders );
                    $inserted = $wpdb->query( $wpdb->prepare( $query, $values ) );

                    if ( false === $inserted ) {
                        if ( ! empty ( $current_data ) ) {
                            $placeholders = array();
                            $values = array();

                            foreach ( $current_data as $current_row ) {
                                $placeholders[] = "('%s', '%s')";
                                array_push(
                                    $values,
                                    $current_row[ 'wp_order_status' ],
                                    $current_row[ 'ecdp_order_status' ]
                                );
                            }

                            $query = $insert_query . implode( ', ', $placeholders );
                            $wpdb->query( $wpdb->prepare( $query, $values) );
                        }
                        
                        $this->add_admin_error_notice(
                            __( 'Duplicate entry: each WooCommerce status should be mapped only once.' )
                        );
                    } else {
                        $this->add_admin_success_notice();
                    }
                }
            } else if (! empty ( $current_data ) ) {
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
                <label for="datefrom">Od:</label>
                <input type="datetime-local" id="datefrom" name="datefrom"><br><br>
                <label for="dateto">Do:</label>
                <input type="datetime-local" id="dateto" name="dateto"><br><br>
                <button class="submit es-button" type="submit">Synchronizuj</button>
            </form>
            <h2>Ostatnia synchronizacja</h2>
            <div>Synchronizacja zamówień: <?= count($expertSenderRequests) ?></div>
            <div>Zsynchronizowano: <?= count($sent) ?></div>
            <div>W kolejce: <?= count($toBeSend) ?></div>
            <div>Błędy: <?= count($failed) ?></div>
            <div class="es-divider"></div>
            <div class="log-container" id="logContainer">
                <?php foreach ($failed as $fail) {
                    echo '<div class=log-message>' .
                        $fail->resource_id .
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

    public function expertsender_cdp_order_synchronize_submission()
    {
        if ( isset( $_POST[ 'expertsender_cdp-order-synchronize-form' ] ) ) {
            $start_date = $_POST[ 'datefrom' ];
            $end_date = $_POST[ 'dateto' ];
            $orders = $this->expertsender_cdp_get_orders_by_dates(
                $start_date,
                $end_date
            );

            global $wpdb;
            $table_name = $wpdb->prefix . 'expertsender_cdp_requests';
            $order_request = new ExpertSender_CDP_Order_Request();

            $query = "SELECT MAX(synchronization_id) AS last_sync FROM $table_name";
            $result = $wpdb->get_row( $query );
            $sync_id = 1;

            if ( $result->last_sync > 0 ) {
                $sync_id = $result->last_sync + 1;
            }

            $order_ids = array();

            foreach ( $orders as $order ) {
                $s_order = wc_get_order( $order->id );
                $processed_id = 0;

                if ( $s_order instanceof WC_Order ) {
                    $processed_id = $s_order->get_id();
                } else {
                    $order_id = $s_order->get_parent_id();
                    $s_order = wc_get_order( $order_id );
                    $processed_id = wc_get_order( $order_id )->get_id();
                }

                if (!in_array($processed_id, $order_ids)) {
                    $order_request->expertsender_cdp_order_save_request(
                        $order->id,
                        $s_order,
                        $sync_id
                    );
                    $order_ids[] = $processed_id;
                }
            }
        }
    }

    function expertsender_cdp_get_orders_by_dates($startDate, $endDate)
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "
                    SELECT * 
                    FROM {$wpdb->prefix}wc_orders 
                    WHERE date_updated_gmt >= %s 
                    AND date_updated_gmt <= %s
                    ORDER BY id DESC
                ",
            $startDate,
            $endDate
        );

        return $wpdb->get_results($query);
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
                $message = __('Zmiany zostały zapisane.', 'exper');
            }

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
                $message = 'Serwer napotkał błąd. Spróbuj ponownie później.';
            }

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
                __(
                    'Nie masz wystarczających uprawnień do tej strony.'
                )
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
}
