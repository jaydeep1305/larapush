<?php
/**
 * Define the internationalization functionality.
 *
 * @link       https://alarapush.com
 * @since      1.0.0
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Unlimited_Push_Notifications_By_Aalarapush
 * @subpackage Unlimited_Push_Notifications_By_Aalarapush/includes
 * @author     LaraPush <support@alarapush.com>
 */
class Unlimited_Push_Notifications_By_Aalarapush_i18n
{
    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain(
            'unlimited-push-notifications-by-alarapush',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
