<?php

/**
 * @link              https://alarapush.com
 * @since             1.0.10
 * @package           Push_Notifications_By_Aalarapush
 *
 * @wordpress-plugin
 * Plugin Name:       Push Notifications by LaraPush
 * Plugin URI:        https://wordpress.org/plugins/push-notifications-by-alarapush/
 * Description:       Push Notifications by LaraPush simplifies push notifications on WordPress with unlimited capabilities and AMP support.
 * Version:           1.0.10
 * Author:            LaraPush
 * Author URI:        https://alarapush.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       push-notifications-by-alarapush
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die();
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('UNLIMITED_PUSH_NOTIFICATIONS_BY_LARAPUSH_VERSION', '1.0.10');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-unlimited-push-notifications-by-alarapush-activator.php
 */
function activate_unlimited_push_notifications_by_alarapush()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-unlimited-push-notifications-by-alarapush-activator.php';
    Unlimited_Push_Notifications_By_Aalarapush_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-unlimited-push-notifications-by-alarapush-deactivator.php
 */
function deactivate_unlimited_push_notifications_by_alarapush()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-unlimited-push-notifications-by-alarapush-deactivator.php';
    Unlimited_Push_Notifications_By_Aalarapush_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_unlimited_push_notifications_by_alarapush');
register_deactivation_hook(__FILE__, 'deactivate_unlimited_push_notifications_by_alarapush');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-unlimited-push-notifications-by-alarapush.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_unlimited_push_notifications_by_alarapush()
{
    $plugin = new Unlimited_Push_Notifications_By_Aalarapush();
    $plugin->run();
}
run_unlimited_push_notifications_by_alarapush();
