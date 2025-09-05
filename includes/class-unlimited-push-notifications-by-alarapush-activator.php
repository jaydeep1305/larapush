<?php

/**
 * Fired during plugin activation.
 *
 * @link       https://alarapush.com
 * @since      1.0.0
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Unlimited_Push_Notifications_By_Aalarapush
 * @subpackage Unlimited_Push_Notifications_By_Aalarapush/includes
 * @author     LaraPush <support@alarapush.com>
 */
class Unlimited_Push_Notifications_By_Aalarapush_Activator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        add_rewrite_rule(
            '^wp-content/plugins/push-notifications-by-alarapush/alarapush-reconfigure/?$',
            'index.php',
            'top'
        );
        flush_rewrite_rules();
    }
}
