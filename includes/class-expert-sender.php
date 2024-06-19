<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://test.pl
 * @since      1.0.0
 *
 * @package    Expert_Sender
 * @subpackage Expert_Sender/includes
 */

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
 * @package    Expert_Sender
 * @subpackage Expert_Sender/includes
 * @author     Endora <marcin.krupa@endora.pl>
 */
class Expert_Sender
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Expert_Sender_Loader    $loader    Maintains and registers all hooks for the plugin.
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
        if (defined('EXPERT_SENDER_VERSION')) {
            $this->version = EXPERT_SENDER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'expert-sender';

        $this->load_dependencies();
        $this->set_locale();
        $this->expert_sender_define_admin_hooks();
        $this->expert_sender_define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Expert_Sender_Loader. Orchestrates the hooks of the plugin.
     * - Expert_Sender_i18n. Defines internationalization functionality.
     * - Expert_Sender_Admin. Defines all hooks for the admin area.
     * - Expert_Sender_Public. Defines all hooks for the public side of the site.
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
            'includes/class-expert-sender-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expert-sender-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) .
            'admin/class-expert-sender-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) .
            'public/class-expert-sender-public.php';

        //require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-expert-sender-product-request.php';

        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expert-sender-inject-consent.php';

        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expert-sender-client-request.php';
        require_once plugin_dir_path(dirname(__FILE__)) .
            'includes/class-expert-sender-order-request.php';

        /**
         * Interfaces
         */
        $this->require( 'includes/interfaces/class-expert-sender-log-handler-interface.php' );
        $this->require( 'includes/interfaces/class-expert-sender-logger-interface.php' );

        /**
         * Abstracts
         */
        $this->require( 'includes/abstracts/abstract-expert-sender-log-handler.php' );

        /**
         * Classes
         */
        $this->require( 'includes/class-expert-sender-api.php' );
        $this->require( 'includes/class-expert-sender-log-levels.php' );
        $this->require( 'includes/class-expert-sender-logger.php' );
        $this->require( 'includes/log-handlers/class-expert-sender-log-handler-file.php' );
        $this->require( 'includes/utilities/class-expert-sender-logging-util.php' );

        $this->require( 'includes/expert-sender-core-functions.php' );

        $this->loader = new Expert_Sender_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Expert_Sender_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new Expert_Sender_i18n();

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
    private function expert_sender_define_admin_hooks()
    {
        $plugin_admin = new Expert_Sender_Admin(
            $this->get_plugin_name(),
            $this->get_version()
        );

        $this->loader->add_action(
            'admin_enqueue_scripts',
            $plugin_admin,
            'enqueue_styles'
        );
        $this->loader->add_action(
            'admin_enqueue_scripts',
            $plugin_admin,
            'enqueue_scripts'
        );
        add_action('expert_sender_cron_job', [
            $this,
            'expert_sender_cron_job_send_request',
        ]);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    public function expert_sender_define_public_hooks()
    {
        $plugin_public = new Expert_Sender_Public(
            $this->get_plugin_name(),
            $this->get_version()
        );
        $plugin_public_customer = new Expert_Sender_Client_Request(
            $this->get_plugin_name(),
            $this->get_version()
        );
        $plugin_public_consent = new Expert_Sender_Inject_Consent(
            $this->get_plugin_name(),
            $this->get_version()
        );
        $plugin_public_order = new Expert_Sender_Order_Request();
        $plugin_api = new Expert_Sender_Api();

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

        if (get_option('expert_sender_enable_script')) {
            $this->loader->add_action(
                'wp_footer',
                $plugin_public,
                'add_expert_sender_script'
            );
        }

        $this->loader->add_action(
            'woocommerce_created_customer',
            $plugin_public_customer,
            'expert_sender_create_customer',
            10,
            3
        );
        $this->loader->add_action(
            'woocommerce_save_account_details',
            $plugin_public_customer,
            'expert_sender_edit_customer'
        );
        $this->loader->add_action(
            'woocommerce_customer_save_address',
            $plugin_public_customer,
            'expert_sender_edit_billing_address',
            10,
            2
        );
        $this->loader->add_action(
            'woocommerce_customer_save_address',
            $plugin_public_customer,
            'expert_sender_edit_shipping_address',
            10,
            2
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
     * @return    Expert_Sender_Loader    Orchestrates the hooks of the plugin.
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

    public function expert_sender_cron_job_send_request()
    {
        $logger = expert_sender_get_logger();
        $logger->debug( 'Custom method executed', array( 'source' => 'cron' ) );

        global $wpdb;
        $table_name = $wpdb->prefix . 'expert_sender_requests';

        $expertSenderRequests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE is_sent = %s",
                false
            )
        );

        foreach ($expertSenderRequests as $request) {
            $headers = [
                'Accept' => 'application/json',
                'x-api-key' => get_option('expert_sender_key'),
                'Content-Type' => 'application/json',
            ];


            $response = wp_remote_post($request->url_address, [
                'headers' => $headers,
                'body' => $request->json_body,
            ]);
            $responseCode = wp_remote_retrieve_response_code($response);
            $responseBody = wp_remote_retrieve_body($response);
            $reponseData = json_decode($responseBody);

            if (is_wp_error($response) || $responseCode == 500 || $responseCode == 401 || property_exists($reponseData, "errors")) {
                $wpdb->update(
                    $table_name,
                    array('is_sent' => 1, 'response' => implode("\n", $reponseData->errors)),
                    array('id' => $request->id),
                );
            } else {
                $response_body = wp_remote_retrieve_body($response);
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
}
