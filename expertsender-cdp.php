<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://expertsender.com/
 * @since             1.0.0
 * @package           ExpertSender_CDP
 *
 * @wordpress-plugin
 * Plugin Name:       ExpertSender CDP
 * Plugin URI:        https://expertsender.com/
 * Description:       ExpertSender CDP Integration
 * Version:           1.0.0
 * Author:            Endora
 * Author URI:        https://endora.software/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       expertsender-cdp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'EXPERTSENDER_CDP_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-expertsender-cdp-activator.php
 */
function activate_expertsender_cdp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-expertsender-cdp-activator.php';
	ExpertSender_CDP_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-expertsender-cdp-deactivator.php
 */
function deactivate_expertsender_cdp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-expertsender-cdp-deactivator.php';
	ExpertSender_CDP_Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstall.
 * This action is documented in includes/class-expertsender-cdp-uninstaller.php
 */
function uninstall_expertsender_cdp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-expertsender-cdp-uninstaller.php';
	ExpertSender_CDP_Uninstaller::uninstall();
}

register_activation_hook( __FILE__, 'activate_expertsender_cdp' );
register_deactivation_hook( __FILE__, 'deactivate_expertsender_cdp' );
register_uninstall_hook( __FILE__, 'uninstall_expertsender_cdp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-expertsender-cdp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_expertsender_cdp() {
	$plugin = new ExpertSender_CDP();
	$plugin->run();
}

run_expertsender_cdp();
