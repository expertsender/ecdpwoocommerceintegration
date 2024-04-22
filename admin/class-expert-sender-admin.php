<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://test.pl
 * @since      1.0.0
 *
 * @package    Expert_Sender
 * @subpackage Expert_Sender/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Expert_Sender
 * @subpackage Expert_Sender/admin
 * @author     Endora <marcin.krupa@endora.pl>
 */
class Expert_Sender_Admin
{
    private static $initiated = false;

    const RESOURCE_PRODUCT = 'product';
    const RESOURCE_CUSTOMER = 'customer';
    const RESOURCE_ORDER = 'order';

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
        add_action('admin_menu', [
            $this,
            'add_plugin_admin_order_status_mapping',
        ]);

        add_action('admin_init', [
            $this,
            'expert_sender_handle_form_submission',
        ]);

        add_action('admin_init', [
            $this,
            'expert_sender_mappings_handle_form_submission',
        ]);

        add_action('admin_init', [
            $this,
            'expert_sender_consents_handle_form_submission',
        ]);

        add_action('admin_init', [
            $this,
            'expert_sender_order_status_mapping_handle_form_submission',
        ]);

        add_action('admin_notices', [$this, 'expert_sender_data_saved_notice']);

        $this->expert_sender_get_customer_attributes_from_api();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Expert_Sender_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Expert_Sender_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/expert-sender-admin.css',
            [],
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Expert_Sender_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Expert_Sender_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/expert-sender-admin.js',
            ['jquery'],
            $this->version,
            false
        );
    }

    public function add_plugin_admin_menu()
    {
        add_menu_page(
            'Expert Sender', // Page title
            'Expert Sender', // Menu title
            'manage_options', // Capability
            'expert-sender-settings', // Menu slug
            [$this, 'render_settings_page'] // Callback function to render the settings page
        );
    }

    public function add_plugin_admin_mappings()
    {
        add_submenu_page(
            'expert-sender-settings', // Page title
            'Expert Sender Mappings',
            'Mappings', // Menu title
            'manage_options', // Capability
            'expert-sender-settings-mappings', // Menu slug
            [$this, 'render_mappings_page'] // Callback function to render the settings page
        );
    }

    public function add_plugin_admin_consents()
    {
        add_submenu_page(
            'expert-sender-settings', // parent
            'Expert Sender Consents',
            'Consents', // Menu title
            'manage_options', // Capability
            'expert-sender-settings-consents', // Menu slug
            [$this, 'render_consents_page'] // Callback function to render the settings page
        );
    }

    public function add_plugin_admin_order_status_mapping()
    {
        add_submenu_page(
            'expert-sender-settings', // parent
            'Order Status Mapping',
            'Order Status Mapping', // Menu title
            'manage_options', // Capability
            'expert-sender-settings-order-status-mapping', // Menu slug
            [$this, 'render_order_status_mapping_page'] // Callback function to render the settings page
        );
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                __(
                    'You do not have sufficient permissions to access this page.'
                )
            );
        }
        $value = get_option('expert_sender_enable_script');
        $checked = $value ? 'checked' : '';
        $phoneChecked = get_option('expert_sender_enable_script')
            ? 'checked'
            : '';
        ?>

    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form id="expertSenderForm" method="post" action="options.php">
		<input type="hidden" name="expert-sender-main-form">

		<?php settings_fields('expert_sender_settings_group'); ?>
            <?php do_settings_sections('expert-sender-settings'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e(
                        'API Key',
                        'expert-sender'
                    ); ?></th>
                    <td>
                        <input type="password" name="expert_sender_key" value="<?php echo esc_attr(
                            get_option('expert_sender_key')
                        ); ?>" />
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
                    <th scope="row"><?php _e(
                        'Enable Script',
                        'expert-sender'
                    ); ?></th>
                    <td>
					<input type="checkbox" id="expert_sender_enable_script" name="expert_sender_enable_script" value="1" <?= $checked ?> />
                    </td>
					<td>
					<p>Włącz skrypt śledzący ruch</p>
					</td>
                </tr>

				<tr valign="top">
                    <th scope="row"><?php _e('Script', 'expert-sender'); ?></th>
                    <td>
                        <textarea name="expert_sender_script" id="expert_sender_script" rows="8" cols="50"><?php echo esc_textarea(
                            base64_decode(get_option('expert_sender_script'))
                        ); ?></textarea>
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
                    <th scope="row"><?php _e(
                        'Website Id',
                        'expert-sender'
                    ); ?></th>
                    <td>
                        <input type="text" name="expert_sender_website_id" value="<?php echo esc_attr(
                            get_option('expert_sender_website_id')
                        ); ?>" />
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
                    <th scope="row"><?php _e(
                        'Enable sending phone number to Expert Sender',
                        'expert-sender'
                    ); ?></th>
                    <td>
					<input type="checkbox" id="expert_sender_enable_phone" name="expert_sender_enable_phone" value="1" <?= $phoneChecked ?> />
                    </td>
					<td>
					<p>Wysyłaj numer telefonu do Expert Sender</p>
					</td>
                </tr>
            </table>
			<script type="text/javascript">
   				jQuery(document).ready(function($) {
				$("#expertSenderForm").submit(function(event) {
					event.preventDefault();
					
					var inputValue = $("#expert_sender_script").val();
					var encodedValue = btoa(inputValue);
					$("#expert_sender_script").val(encodedValue);

					event.target.submit();
			});
		});
			</script>
			<button> Save </button>
        </form>
    </div>
    <?php
    }

    public function expert_sender_handle_form_submission()
    {
        if (isset($_POST['expert-sender-main-form'])) {
            register_setting(
                'expert_sender_settings_group',
                'expert_sender_key'
            );
            register_setting(
                'expert_sender_settings_group',
                'expert_sender_enable_script'
            );
            register_setting(
                'expert_sender_settings_group',
                'expert_sender_enable_phone'
            );
            register_setting(
                'expert_sender_settings_group',
                'expert_sender_script',
                [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'show_in_rest' => false,
                ]
            );
            register_setting(
                'expert_sender_settings_group',
                'expert_sender_website_id'
            );

            $expert_sender_key = sanitize_text_field(
                $_POST['expert_sender_key']
            );
            update_option('expert_sender_key', $expert_sender_key);

            update_option(
                'expert_sender_enable_script',
                $_POST['expert_sender_enable_script']
            );

            update_option(
                'expert_sender_enable_phone',
                $_POST['expert_sender_enable_phone']
            );

            update_option(
                'expert_sender_script',
                $_POST['expert_sender_script']
            );

            $expert_sender_website_id = sanitize_text_field(
                $_POST['expert_sender_website_id']
            );
            update_option(
                'expert_sender_website_id',
                $expert_sender_website_id
            );
        }
    }

    // Display notice after data is saved
    public function expert_sender_data_saved_notice()
    {
        ?>
		<div class="notice notice-success is-dismissible">
			<p>Data saved successfully.</p>
		</div>
		<?php
    }

    public function render_mappings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                __(
                    'You do not have sufficient permissions to access this page.'
                )
            );
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'expert_sender_mappings';

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

        $customerOptions = $this->expert_sender_get_customer_attributes_from_api();

        $productOptions = $this->expert_sender_get_product_attributes_from_api();

        $orderOptions = $this->expert_sender_get_order_attributes_from_api();

        $last_id = $wpdb->get_var("SELECT MAX(id) FROM $table_name") + 1;
        ?>

    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?>
		</h1>
		<form id="expertSenderMappingsForm" method="post" action="">
	<input type="hidden" name="expert-sender-mapping-form">
			<div id="productMapping" class="mappingSection">
				<h2>Product Mapping</h2>
				<button type="button" class="addPairBtn">Add Pair</button>
	
				<div class="inputPairsContainer" data-slug="product">
					<?php foreach ($productMappings as $productMapping) {
         echo '<div class="inputPair">';

         echo '<select name="product[' .
             $productMapping->id .
             '][wp_field]">
        ';

         foreach ($productKeys as $productKey => $value) {
             $selected =
                 $productMapping->wp_field == $productKey ? 'selected' : '';

             echo "<option value=\"$value\" $selected>$value</option>";
         }

         echo '</select>';
         echo '<select name="product[' .
             $productMapping->id .
             '][ecdp_field]">
    ';

         foreach ($productOptions as $value) {
             $value = $value->name;
             $selected =
                 $productMapping->ecdp_field == $value ? 'selected' : '';

             echo "<option value=\"$value\" $selected>$value</option>";
         }

         echo '
		</select>
			<button type="button">Remove</button></div>';
     } ?>
				</div>
			</div>
	
			<div id="customerMapping" class="mappingSection">
				<h2>Customer Mapping</h2>
				<button type="button" class="addPairBtn">Add Pair</button>
	
				<div class="inputPairsContainer" data-slug="customer">
					<?php foreach ($customerMappings as $customerMapping) {
         echo '<div class="inputPair">';

         echo '<select name="customer[' .
             $customerMapping->id .
             '][wp_field]">
        ';

         foreach ($customerKeys as $customerKey => $value) {
             $selected =
                 $customerMapping->wp_field == $customerKey ? 'selected' : '';

             echo "<option value=\"$customerKey\" $selected>$customerKey</option>";
         }

         echo '</select>';

         echo '<select name="customer[' .
             $customerMapping->id .
             '][ecdp_field]">
    ';

         foreach ($customerOptions as $value) {
             $value = $value->name;
             $selected =
                 $customerMapping->ecdp_field == $value ? 'selected' : '';

             echo "<option value=\"$value\" $selected>$value</option>";
         }

         echo '
		</select>
			<button type="button">Remove</button></div>';
     } ?>
				</div>
			</div>
	
			<div id="orderMapping" class="mappingSection">
				<h2>Order Mapping</h2>
				<button type="button" class="addPairBtn">Add Pair</button>
	
				<div class="inputPairsContainer" data-slug="order">
					<?php foreach ($orderMappings as $orderMapping) {
         echo '<div class="inputPair">';

         echo '<select name="order[' .
             $orderMapping->id .
             '][wp_field]">
			';

         foreach ($orderKeys as $orderKey => $orderValue) {
             $selected = $orderMapping->wp_field == $orderKey ? 'selected' : '';

             echo "<option value=\"$orderKey\" $selected>$orderKey</option>";
         }

         echo '</select>';

         echo '<select name="order[' .
             $orderMapping->id .
             '][ecdp_field]">
			';

         foreach ($orderOptions as $value) {
             $value = $value->name;
             $selected = $orderMapping->ecdp_field == $value ? 'selected' : '';

             echo "<option value=\"$value\" $selected>$value</option>";
         }

         echo '</select>';

         echo '<button type="button">Remove</button></div>';
     } ?>
				</div>
			</div>
			<input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">
			<button class="submit" type="submit"> Save </button>
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
document.addEventListener("DOMContentLoaded", function() {
    const sections = document.querySelectorAll(".mappingSection");

    sections.forEach(function(section) {
        const inputPairsContainer = section.querySelector(".inputPairsContainer");
        const addPairBtn = section.querySelector(".addPairBtn");

        addPairBtn.addEventListener("click", function() {
            createInputPair(inputPairsContainer);
        });
    });

    function createInputPair(container) {
        const pairDiv = document.createElement("div");
        pairDiv.classList.add("inputPair");

		var id = document.getElementById("idCounter").value;
		var slug = container.getAttribute("data-slug");

        // const wpInput = document.createElement("input");
        // wpInput.type = "text";
        // wpInput.name = slug + "[" + id + "][wp_field]";
		// wpInput.placeholder = "Woocommerce Field";
		// wpInput.required = true;

        var template = document.querySelector("#" + slug + "keys");
		const wpSelect = template.content.cloneNode(true);
  		let wp = wpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][wp_field]";

		console.log("#" + slug + "select");
		var template2 = document.querySelector("#" + slug + "select");
		const ecdpSelect = template2.content.cloneNode(true);
  		let td = ecdpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][ecdp_field]";

        const removeBtn = document.createElement("button");
        removeBtn.textContent = "Remove";
		removeBtn.type = "button"
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

    public function expert_sender_mappings_handle_form_submission()
    {
        if (isset($_POST['expert-sender-mapping-form'])) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'expert_sender_mappings';
            $wpdb->query("DELETE FROM $table_name");

            if (isset($_POST['customer'])) {
                foreach ($_POST['customer'] as $mapping) {
                    $wpdb->insert($table_name, [
                        'resource_type' => $this::RESOURCE_CUSTOMER,
                        'wp_field' => $mapping['wp_field'],
                        'ecdp_field' => $mapping['ecdp_field'],
                    ]);
                }
            }

            if (isset($_POST['product'])) {
                foreach ($_POST['product'] as $mapping) {
                    $object = [
                        'resource_type' => $this::RESOURCE_PRODUCT,
                        'wp_field' => $mapping['wp_field'],
                        'ecdp_field' => $mapping['ecdp_field'],
                    ];

                    $wpdb->insert($table_name, $object);
                }
            }

            if (isset($_POST['order'])) {
                foreach ($_POST['order'] as $mapping) {
                    $object = [
                        'resource_type' => $this::RESOURCE_ORDER,
                        'wp_field' => $mapping['wp_field'],
                        'ecdp_field' => $mapping['ecdp_field'],
                    ];

                    $wpdb->insert($table_name, $object);
                }
            }
        }
    }

    public function expert_sender_get_customer_attributes_from_api()
    {
        $api_url = 'https://api.ecdp.app/customerattributes';

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option('expert_sender_key'),
        ];

        $args = [
            'headers' => $headers,
        ];

        // Send GET request to the API
        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    public function expert_sender_get_product_attributes_from_api()
    {
        $api_url = 'https://api.ecdp.app/productattributes';

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option('expert_sender_key'),
        ];

        $args = [
            'headers' => $headers,
        ];

        // Send GET request to the API
        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    public function expert_sender_get_order_attributes_from_api()
    {
        $api_url = 'https://api.ecdp.app/orderattributes';

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option('expert_sender_key'),
        ];

        $args = [
            'headers' => $headers,
        ];

        // Send GET request to the API
        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    public function render_consents_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                __(
                    'You do not have sufficient permissions to access this page.'
                )
            );
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'expert_sender_consents';

        $consents = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name")
        );

        $apiConsents = $this->expert_sender_get_consents_from_api();
        $consentLocations = $this->expert_sender_get_consents_locations();

        $last_id = $wpdb->get_var("SELECT MAX(id) FROM $table_name") + 1;
        ?>

    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?>
		</h1>
		<form id="expertSenderConsentsForm" method="post" action="">
	<input type="hidden" name="expert-sender-consents-form">
			<div id="consentSection" class="consentSection">
				<h2>Consent Mapping</h2>
				<button type="button" class="addPairBtn">Add Pair</button>
	
				<div class="elementContainer">
                    
					<?php foreach ($consents as $consent) {
         echo '<div class="inputPair">';
         echo '<select name="consent[' . $consent->id . '][api_consent_id]">';

         foreach ($apiConsents as $value) {
             $selected =
                 $consent->api_consent_id == $value->id ? 'selected' : '';

             echo "<option value=\"$value->id\" $selected>$value->name</option>";
         }

         echo '</select>';

         echo '<select name="consent[' . $consent->id . '][consent_location]">';

         foreach ($consentLocations as $value) {
             $selected = $consent->consent_location == $value ? 'selected' : '';

             echo "<option value=\"$value\" $selected>$value</option>";
         }

         echo '</select>';

         echo '<input type="text" placeholder="Consent Text" required="true" name="consent[' .
             $consent->id .
             '][consent_text]" value="' .
             $consent->consent_text .
             '">';

         echo '<button type="button">Remove</button></div>';
     } ?>
	  
			<input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">
			<button class="submit" type="submit"> Save </button>
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
                <?php foreach ($consentLocations as $value) {
                    echo "<option value=\"$value\">$value</option>";
                } ?>
            </select>
		</template>
	</div>

	<script>
document.addEventListener("DOMContentLoaded", function() {
    const sections = document.querySelectorAll(".consentSection");

    sections.forEach(function(section) {
        const elementContainer = section.querySelector(".elementContainer");
        const addPairBtn = section.querySelector(".addPairBtn");

        addPairBtn.addEventListener("click", function() {
            createInputPair(elementContainer);
        });
    });

    function createInputPair(container) {
        const pairDiv = document.createElement("div");
        pairDiv.classList.add("inputPair");

		var id = document.getElementById("idCounter").value;

		var template = document.querySelector("#apiConsentsTemplate");
		const apiConsent = template.content.cloneNode(true);
  		apiConsent.querySelectorAll("select")[0].name = "consent[" + id + "][api_consent_id]";

        var template2 = document.querySelector("#consentLocationTemplate");
		const consentLocation = template2.content.cloneNode(true);
        consentLocation.querySelectorAll("select")[0].name = "consent[" + id + "][consent_location]";


        const removeBtn = document.createElement("button");
        removeBtn.textContent = "Remove";
		removeBtn.type = "button"
        removeBtn.addEventListener("click", function() {
            pairDiv.remove();
        });

        const consentTextInput = document.createElement("input");
        consentTextInput.type = "text";
        consentTextInput.name = "consent[" + id + "][consent_text]";
		consentTextInput.placeholder = "Consent Text";
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

    public function expert_sender_get_consents_from_api()
    {
        $api_url = 'https://api.ecdp.app/customerconsents';

        $headers = [
            'accept' => 'application/json',
            'x-api-key' => get_option('expert_sender_key'),
        ];

        $args = [
            'headers' => $headers,
        ];

        // Send GET request to the API
        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return json_decode($response_body)->data;
        }
    }

    public function expert_sender_get_consents_locations()
    {
        return ['customer_settings', 'checkout'];
    }

    public function expert_sender_consents_handle_form_submission()
    {
        if (isset($_POST['expert-sender-consents-form'])) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'expert_sender_consents';
            $wpdb->query("DELETE FROM $table_name");

            if (isset($_POST['consent'])) {
                foreach ($_POST['consent'] as $mapping) {
                    $wpdb->insert($table_name, [
                        'api_consent_id' => $mapping['api_consent_id'],
                        'consent_location' => $mapping['consent_location'],
                        'consent_text' => $mapping['consent_text'],
                    ]);
                }
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
        // if (class_exists('WooCommerce')) {
        //     $order = new WC_Product();
        //     return $this->flatten($order->get_data());
        // }
        $product_attribute_taxonomies = wc_get_attribute_taxonomies();
        if ($product_attribute_taxonomies) {

            $attribute_names = array_map(function ($taxonomy) {
                return $taxonomy->attribute_label;
            }, $product_attribute_taxonomies);

            return $attribute_names;
        }
        return [];
    }

    public function render_order_status_mapping_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                __(
                    'You do not have sufficient permissions to access this page.'
                )
            );
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'expert_sender_order_status_mappings';

        $orderStatusMappings = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_name")
        );
        $wpStatuses = $this->expert_sender_get_wp_order_statuses();
        $ecdpStatuses = $this->expert_sender_get_ecdp_order_statuses();

        $last_id = $wpdb->get_var("SELECT MAX(id) FROM $table_name") + 1;
        ?>

    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?>
		</h1>
		<form id="expertSenderOrderStatusMappingsForm" method="post" action="">
	<input type="hidden" name="expert-sender-order-status-mapping-form">
			<div id="orderStatusMapping" class="mappingSection">
				<h2>Order Status Mapping</h2>
				<button type="button" class="addPairBtn">Add Pair</button>
	
				<div class="inputPairsContainer" data-slug="product">
					<?php foreach ($orderStatusMappings as $orderMapping) {
         echo '<div class="inputPair">';

         echo '<select name="orderMapping[' .
             $orderMapping->id .
             '][wp_order_status]">
        ';

         foreach ($wpStatuses as $status) {
             $selected =
                 $orderMapping->wp_order_status == $status ? 'selected' : '';

             echo "<option value=\"$status\" $selected>$status</option>";
         }

         echo '</select>';
         echo '<select name="orderMapping[' .
             $orderMapping->id .
             '][ecdp_order_status]">
    ';

         foreach ($ecdpStatuses as $status) {
             $selected =
                 $orderMapping->ecdp_order_status == $status ? 'selected' : '';

             echo "<option value=\"$status\" $selected>$status</option>";
         }

         echo '
		</select>
			<button type="button">Remove</button></div>';
     } ?>
				</div>
			</div>
			<input type="hidden" name="idCounter" id="idCounter" value="<?= $last_id ?>">
			<button class="submit" type="submit"> Save </button>
		</form>

		<template id="wpstatus">
            <select name="">
                <?php foreach ($wpStatuses as $status) {
                    echo "<option value=\"$status\">$status</option>";
                } ?>
            </select>
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
document.addEventListener("DOMContentLoaded", function() {
    const sections = document.querySelectorAll(".mappingSection");

    sections.forEach(function(section) {
        const inputPairsContainer = section.querySelector(".inputPairsContainer");
        const addPairBtn = section.querySelector(".addPairBtn");

        addPairBtn.addEventListener("click", function() {
            createInputPair(inputPairsContainer);
        });
    });

    function createInputPair(container) {
        const pairDiv = document.createElement("div");
        pairDiv.classList.add("inputPair");

		var id = document.getElementById("idCounter").value;
		var slug = 'orderMapping';

        var template = document.querySelector("#wpstatus");
		const wpSelect = template.content.cloneNode(true);
  		let wp = wpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][wp_order_status]";

		var template2 = document.querySelector("#ecdpstatus");
		const ecdpSelect = template2.content.cloneNode(true);
  		let td = ecdpSelect.querySelectorAll("select")[0].name = slug + "[" + id + "][ecdp_order_status]";

        const removeBtn = document.createElement("button");
        removeBtn.textContent = "Remove";
		removeBtn.type = "button"
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

    public function expert_sender_get_wp_order_statuses()
    {
        return [
            'Placed',
            'Paid',
            'Pending',
            'Processing',
            'On-hold',
            'Completed',
            'Canceled',
            'Refunded',
            'Failed',
        ];
    }
    public function expert_sender_get_ecdp_order_statuses()
    {
        return ['Placed', 'Paid', 'Completed', 'Canceled'];
    }

    public function expert_sender_order_status_mapping_handle_form_submission()
    {
        if (isset($_POST['expert-sender-order-status-mapping-form'])) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'expert_sender_order_status_mappings';
            $wpdb->query("DELETE FROM $table_name");

            if (isset($_POST['orderMapping'])) {
                foreach ($_POST['orderMapping'] as $mapping) {
                    $wpdb->insert($table_name, [
                        'wp_order_status' => $mapping['wp_order_status'],
                        'ecdp_order_status' => $mapping['ecdp_order_status'],
                    ]);
                }
            }
        }
    }
}
