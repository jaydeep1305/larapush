<?php
/**
 *
 * @link       https://alarapush.com
 * @since      1.0.0
 *
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Unlimited_Push_Notifications_By_Aalarapush
 * @subpackage Unlimited_Push_Notifications_By_Aalarapush/includes
 * @author     LaraPush <support@alarapush.com>
 */
class Unlimited_Push_Notifications_By_Aalarapush_Deactivator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        // Delete Transients
        delete_transient('alarapush_error');
        delete_transient('alarapush_success');

        // Delete Files
        $files_used = get_option('unlimited_push_notifications_by_alarapush_js_filenames_for_site', []);
        foreach ($files_used as $file) {
            $filename = ABSPATH . $file;
            if (file_exists($filename)) {
                unlink($filename);
            }
        }

        // Delete Options
        $options_used = [
            // Decides if integration is done or not
            'unlimited_push_notifications_by_alarapush_panel_integration_tried',
            // Basic User Details
            'unlimited_push_notifications_by_alarapush_panel_url',
            'unlimited_push_notifications_by_alarapush_panel_email',
            'unlimited_push_notifications_by_alarapush_panel_password',
            'unlimited_push_notifications_by_alarapush_panel_plan',
            // For Panel Integration Files to be deleted
            'unlimited_push_notifications_by_alarapush_js_filenames_for_site',
            // Settings Options
            'unlimited_push_notifications_by_alarapush_push_on_publish',
            'unlimited_push_notifications_by_alarapush_push_on_publish_delay',
            'unlimited_push_notifications_by_alarapush_push_on_publish_for_webstories',
            'unlimited_push_notifications_by_alarapush_enable_push_notifications',
            // For Domains to Send Notifications
            'unlimited_push_notifications_by_alarapush_panel_domains',
            'unlimited_push_notifications_by_alarapush_panel_domains_selected',
            // For AMP
            'unlimited_push_notifications_by_alarapush_add_code_for_amp',
            'unlimited_push_notifications_by_alarapush_amp_code_location',
            // For Javascript and AMP Code
            'unlimited_push_notifications_by_alarapush_codes'
        ];
        foreach ($options_used as $option) {
            delete_option($option);
        }
    }
}
