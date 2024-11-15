<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    ExpertSender_CDP
 * @subpackage ExpertSender_CDP/includes
 * @author     Endora <marcin.krupa@endora.pl>
 */
class ExpertSender_CDP
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      ExpertSender_CDP_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('EXPERTSENDER_CDP_VERSION')) {
            $this->version = EXPERTSENDER_CDP_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'expertsender-cdp';

        $this->load_dependencies();
        $this->set_locale();
        $this->expertsender_cdp_define_admin_hooks();
        $this->expertsender_cdp_define_public_hooks();
        $this->define_constants();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - ExpertSender_CDP_Loader. Orchestrates the hooks of the plugin.
     * - ExpertSender_CDP_i18n. Defines internationalization functionality.
     * - ExpertSender_CDP_Admin. Defines all hooks for the admin area.
     * - ExpertSender_CDP_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expertsender-cdp-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expertsender-cdp-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) .
            'admin/class-expertsender-cdp-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) .
            'public/class-expertsender-cdp-public.php';

        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-expertsender-cdp-product-request.php';

        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expertsender-cdp-inject-consent.php';

        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expertsender-cdp-client-request.php';
        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expertsender-cdp-order-request.php';

        /**
         * Interfaces
         */
        $this->require( 'includes/interfaces/class-expertsender-cdp-log-handler-interface.php' );
        $this->require( 'includes/interfaces/class-expertsender-cdp-logger-interface.php' );

        /**
         * Abstracts
         */
        $this->require( 'includes/abstracts/abstract-expertsender-cdp-log-handler.php' );

        /**
         * Classes
         */
        $this->require( 'includes/class-expertsender-cdp-ajax.php' );
        $this->require( 'includes/class-expertsender-cdp-api.php' );
        $this->require( 'includes/class-expertsender-cdp-log-levels.php' );
        $this->require( 'includes/class-expertsender-cdp-logger.php' );
        $this->require( 'includes/log-handlers/class-expertsender-cdp-log-handler-file.php' );
        $this->require( 'includes/utilities/class-expertsender-cdp-logging-util.php' );

        $this->require( 'includes/expertsender-cdp-consent-functions.php' );
        $this->require( 'includes/expertsender-cdp-core-functions.php' );
        $this->require( 'includes/expertsender-cdp-field-mapping-functions.php' );
        $this->require( 'includes/expertsender-cdp-order-status-mapping-functions.php' );

        $this->loader = new ExpertSender_CDP_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the ExpertSender_CDP_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new ExpertSender_CDP_i18n();

        $this->loader->add_action(
            'plugins_loaded',
            $plugin_i18n,
            'load_plugin_textdomain'
        );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function expertsender_cdp_define_admin_hooks()
    {
        $plugin_admin = new ExpertSender_CDP_Admin(
            $this->get_plugin_name(),
            $this->get_version()
        );

        $this->loader->add_action(
            'admin_enqueue_scripts',
            $plugin_admin,
            'enqueue_styles'
        );

        add_action('expertsender_cdp_cron_job', [
            $this,
            'expertsender_cdp_cron_job_send_request',
        ]);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    public function expertsender_cdp_define_public_hooks()
    {
        $plugin_public = new ExpertSender_CDP_Public(
            $this->get_plugin_name(),
            $this->get_version()
        );

        $plugin_public_customer = new ExpertSender_CDP_Client_Request(
            $this->get_plugin_name(),
            $this->get_version()
        );

        $plugin_public_consent = new ExpertSender_CDP_Inject_Consent(
            $this->get_plugin_name(),
            $this->get_version()
        );

        new ExpertSender_CDP_Order_Request();
        $plugin_api = new ExpertSender_CDP_Api();
        new ExpertSender_CDP_Ajax();

        $this->loader->add_action(
            'wp_enqueue_scripts',
            $plugin_public,
            'enqueue_styles'
        );

        $this->loader->add_action(
            'wp_enqueue_scripts',
            $plugin_public,
            'enqueue_scripts'
        );

        if (get_option('expertsender_cdp_enable_script')) {
            $this->loader->add_action(
                'wp_footer',
                $plugin_public,
                'add_expertsender_cdp_script'
            );
        }

        $this->loader->add_action(
            'woocommerce_created_customer',
            $plugin_public_customer,
            'expertsender_cdp_create_customer',
            10,
            3
        );

        $this->loader->add_action(
            'woocommerce_save_account_details',
            $plugin_public_customer,
            'expertsender_cdp_edit_customer'
        );

        $this->loader->add_action(
            'woocommerce_customer_save_address',
            $plugin_public_customer,
            'expertsender_cdp_edit_billing_address',
            10,
            2
        );

        $this->loader->add_action(
            'woocommerce_customer_save_address',
            $plugin_public_customer,
            'expertsender_cdp_edit_shipping_address',
            10,
            2
        );

        $this->loader->add_action(
            'woocommerce_register_form',
            $plugin_public_consent,
            'expertsender_cdp_add_consents_in_register_form',
            10,
            1
        );

        $this->loader->add_action(
            'http_api_debug',
            $plugin_api,
            'log_request',
            10,
            5
        );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    ExpertSender_CDP_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    public function expertsender_cdp_cron_job_send_request()
    {
        global $wpdb;

        $logger = expertsender_cdp_get_logger();
        $table_name = $wpdb->prefix . 'expertsender_cdp_requests';

        $expertSenderRequests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE is_sent = %s",
                false
            )
        );

        foreach ($expertSenderRequests as $request) {
            $headers = [
                'Accept' => 'application/json',
                'x-api-key' => get_option( ExpertSender_CDP_Admin::OPTION_API_KEY ),
                'Content-Type' => 'application/json',
            ];

            $response = wp_remote_post($request->url_address, [
                'headers' => $headers,
                'body' => $request->json_body,
            ]);
            $responseCode = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body);

            if (
                is_wp_error( $response ) || $responseCode == 500 || $responseCode == 401 ||
                ( null !== $response_data && property_exists( $response_data, 'errors' ) )
            ) {
                $wpdb->update(
                    $table_name,
                    array('is_sent' => 1, 'response' => implode("\n", $response_data->errors)),
                    array('id' => $request->id),
                );
            } else {
                $wpdb->update(
                    $table_name,
                    array('is_sent' => 1),
                    array('id' => $request->id),
                );
                $logger->debug(
                    "Succeded to send request with id {$request->id} \n{$response_body}\n",
                    array( 'source' => 'cron' )
                );
            }
        }
    }

    /**
     * @param string $filepath
     * @return void
     */
    private function require( $filepath ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . $filepath;
    }

    /**
     * @return void
     */
    private function define_constants() {
        $this->define( 'ES_API_URL', 'https://api.ecdp.app/');
    }

    /**
     * @param string $name
     * @param string $value
     * 
     * @return void
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }
}
