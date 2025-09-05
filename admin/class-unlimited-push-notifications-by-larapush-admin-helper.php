<?php

/**
 * The admin-specific helper functionality of the plugin.
 *
 * @link       https://larapush.com
 * @since      1.0.0
 *
 * @package    Unlimited_Push_Notifications_By_Larapush
 * @subpackage Unlimited_Push_Notifications_By_Larapush/admin/helper
 * @author     LaraPush <support@larapush.com>
 */
class Unlimited_Push_Notifications_By_Larapush_Admin_Helper
{
    /**
     * Saves the error and redirects to the settings page.
     *
     * @since 1.0.0
     */
    public static function responseErrorAndRedirect($message)
    {
        set_transient('larapush_error', $message, 30);
        wp_redirect(admin_url('admin.php?page=unlimited-push-notifications-by-larapush-settings'));
        exit();
    }

    /**
     * Saves the error and redirects to the settings page.
     *
     * @since 1.0.5
     */
    public static function checkIfUserHasAccess()
    {
        if (current_user_can('administrator') || current_user_can('manage_options')) {
            return true;
        }

        $access = get_option('unlimited_push_notifications_by_larapush_access', []);
        // the access will be array ['editor', 'author]

        $user = wp_get_current_user();
        $user_roles = $user->roles;

        foreach ($user_roles as $role) {
            if (in_array($role, $access)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves the success and redirects to the settings page.
     *
     * @since 1.0.0
     */
    public static function responseSuccessAndRedirect($message)
    {
        set_transient('larapush_success', $message, 30);
        wp_redirect(admin_url('admin.php?page=unlimited-push-notifications-by-larapush-settings'));
        exit();
    }

    /**
     * Assembles the url for the api calls.
     *
     * @since 1.0.0
     */
    public static function assambleUrl($url, $url_path)
    {
        $panel_url = parse_url(trim($url));

        $panel_url =
            ($panel_url['scheme'] ?? 'https') .
            '://' .
            ($panel_url['host'] ?? 'localhost') .
            (isset($panel_url['port']) ? ':' . $panel_url['port'] : '');

        return $panel_url .
            $url_path .
            '?plugin_version=' .
            UNLIMITED_PUSH_NOTIFICATIONS_BY_LARAPUSH_VERSION .
            '&wordpress_version=' .
            get_bloginfo('version');
    }

    /**
     * Encodes the Important Information to be saved in the database.
     *
     * @since 1.0.0
     */
    public static function encode($msg)
    {
        // encode to base64
        $msg = base64_encode($msg);

        // split into chunks
        $msg = str_split($msg, 1);

        // convert to ascii
        $msg = array_map(function ($char) {
            return ord($char) + 415;
        }, $msg);

        // join with - saparator
        $msg = implode('-', $msg);

        return $msg;
    }

    /**
     * Decodes the Important Information to be saved in the database.
     *
     * @since 1.0.0
     */
    public static function decode($msg)
    {
        // if no - found, return the same string
        if (strpos($msg, '-') === false) {
            return $msg;
        }

        // splits with - saparator
        $msg = explode('-', $msg);

        // convert from ascii
        $msg = array_map(function ($char) {
            return chr($char - 415);
        }, $msg);

        // join all
        $msg = implode('', $msg);

        // decode from base64
        $msg = base64_decode($msg);

        return $msg;
    }

    /**
     * Checks if the connection is valid.
     *
     * @since 1.0.4
     */
    public static function checkConnection()
    {
        // get the url and path
        $url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_url', '')
        );
        $url_path = '/api/checkAuth';

        // get the email and password
        $email = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_email', '')
        );
        $password = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_password', '')
        );

        // check if all 3 fields exist
        if (empty($url) || empty($email) || empty($password)) {
            return false;
        }

        // Authenticate to LaraPush Panel
        $panel_url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::assambleUrl($url, $url_path);
        $response = wp_remote_post($panel_url, [
            'headers' => [
                'Accept' => 'application/json',
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'email' => $email,
                'password' => $password
            ])
        ]);

        // Check if the response is a WP_Error
        if (is_wp_error($response)) {
            // Log the error
            error_log('WP_Error in wp_remote_post: ' . $response->get_error_message());

            // Log additional server details for debugging
            error_log('PHP Version: ' . phpversion());
            error_log('WordPress Version: ' . get_bloginfo('version'));
            // Add any additional server information you think might be relevant

            // Handle the error
            add_settings_error(
                'unlimited-push-notifications-by-larapush-settings',
                'my_connection_error',
                'Error: ' . $response->get_error_message(),
                'error'
            );
            return false;
        }

        try {
            $body = json_decode($response['body']);
            if (!$body->success) {
                add_settings_error(
                    'unlimited-push-notifications-by-larapush-settings',
                    'my_connection_error',
                    'Error: ' . $body->message,
                    'error'
                );
                return false;
            }

            // Store both plan and version
            if (isset($body->plan)) {
                update_option('unlimited_push_notifications_by_larapush_panel_plan', $body->plan);
            } else {
                update_option('unlimited_push_notifications_by_larapush_panel_plan', 'premium');
            }

            if (isset($body->version)) {
                update_option('unlimited_push_notifications_by_larapush_panel_version', $body->version);
            } else {
                update_option('unlimited_push_notifications_by_larapush_panel_version', '1.0.0');
            }

            return true;
        } catch (\Throwable $error) {
            add_settings_error(
                'unlimited-push-notifications-by-larapush-settings',
                'my_connection_error',
                'Error: LaraPush v3 Pro Panel not found, Make sure you are using LaraPush v3 Pro Panel.',
                'error'
            );
            return false;
        }

        return false;
    }

    /**
     * Gets Campaign Filters from LaraPush Panel to select domains to send push notifications to.
     *
     * @since 1.0.0
     */
    public static function getCampaignFilter()
    {
        // get the url and path
        $url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_url', '')
        );
        $url_path = '/api/getCampaignFilter';

        // get the email and password
        $email = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_email', '')
        );
        $password = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_password', '')
        );

        // check if all 3 fields exists
        if (empty($url) || empty($email) || empty($password)) {
            return false;
        }

        // Authenticate to LaraPush Panel
        $panel_url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::assambleUrl($url, $url_path);
        $response = wp_remote_post($panel_url, [
            'headers' => [
                'Accept' => 'application/json',
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'email' => $email,
                'password' => $password
            ])
        ]);

        try {
            $body = json_decode($response['body']);
            if (!$body->success) {
                add_settings_error(
                    'unlimited-push-notifications-by-larapush-settings',
                    'my_connection_error',
                    'Error: ' . $body->message,
                    'error'
                );
                return false;
            }

            update_option('unlimited_push_notifications_by_larapush_panel_domains', $body->data->domains);
            if (get_option('unlimited_push_notifications_by_larapush_panel_domains_selected', null) === null) {
                update_option('unlimited_push_notifications_by_larapush_panel_domains_selected', $body->data->domains);
            }

            return true;
        } catch (\Throwable $error) {
            add_settings_error(
                'unlimited-push-notifications-by-larapush-settings',
                'my_connection_error',
                'Error: LaraPush v3 Pro Panel not found, Make sure you are using LaraPush v3 Pro Panel.',
                'error'
            );
            return false;
        }

        return false;
    }

    public static function codeIntegration()
    {
        // get the url and path
        $url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_url', '')
        );
        $url_path = '/api/codeIntegration';

        // get the email and password
        $email = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_email', '')
        );
        $password = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_password', '')
        );

        // check if all 3 fields exists
        if (empty($url) || empty($email) || empty($password)) {
            return false;
        }

        // Get site url
        $site_url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::getDomain();

        // Authenticate to LaraPush Panel
        $panel_url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::assambleUrl($url, $url_path);
        $response = wp_remote_post($panel_url, [
            'headers' => [
                'Accept' => 'application/json',
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'domain' => $site_url,
                'email' => $email,
                'password' => $password
            ])
        ]);

        try {
            // check if the response is valid
            $body = json_decode($response['body']);
            if (!$body->success) {
                add_settings_error(
                    'unlimited-push-notifications-by-larapush-settings',
                    'my_connection_error',
                    'Error: ' . $body->message,
                    'error'
                );
                return false;
            }

            // check if root of the website is writable, if yes, write the js file
            if (is_writable(ABSPATH)) {
                // Writing javascript file
                $old_files_name = get_option('unlimited_push_notifications_by_larapush_js_filenames_for_site', []);
                // Delete old files
                foreach ($old_files_name as $old_file_name) {
                    $old_file = ABSPATH . $old_file_name;
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                #Files Setup
                $file_names = [];

                // Add js_code_filename if it exists
                $file_names[] = $body->data->integration->integrationCode->js_code_filename ?? null;
                $file_names[] = $body->data->integration->integrationCode->sw_firebase_code_filename ?? null;
                $file_names[] = $body->data->integration->ampIntegrationCode->helper_frame_filename ?? null;
                $file_names[] = $body->data->integration->ampIntegrationCode->permission_dialog_filename ?? null;

                // Filter out null values from the array
                $file_names = array_filter($file_names, function ($value) {
                    return $value !== null;
                });

                update_option('unlimited_push_notifications_by_larapush_js_filenames_for_site', $file_names);

                $codes = [];

                // Writing javascript files
                $js_filename = $body->data->integration->integrationCode->js_code_filename ?? null;
                $js_code = $body->data->integration->integrationCode->js_code ?? null;
                if ($js_filename && $js_code) {
                    $js_file = ABSPATH . $js_filename;
                    file_put_contents($js_file, $js_code);
                }

                // Writing service worker file
                $sw_filename = $body->data->integration->integrationCode->sw_firebase_code_filename ?? null;
                $sw_code = $body->data->integration->integrationCode->sw_firebase_code ?? null;
                if ($sw_filename && $sw_code) {
                    $sw_file = ABSPATH . $sw_filename;
                    file_put_contents($sw_file, $sw_code);
                    $codes = array_merge($codes, [
                        'sw_filename' => $sw_filename
                    ]);
                }

                // Getting WEB Header code URLs and data
                $header_script = $body->data->integration->integrationCode->header_script ?? null;
                $header_additional_js_code =
                    $body->data->integration->integrationCode->header_additional_js_code ?? null;

                if ($header_script && $header_additional_js_code) {
                    $code_to_be_added_in_header_data = [
                        'script_url' => $header_script
                    ];
                } elseif ($js_filename) {
                    $script_url = esc_url(get_site_url() . '/' . $js_filename);
                    $code_to_be_added_in_header_data = [
                        'script_url' => $script_url
                    ];
                } else {
                    $code_to_be_added_in_header_data = [];
                }

                // Check if PWA iOS is enabled and save manifest.json file
                $pwa_ios_enabled = get_option('unlimited_push_notifications_by_larapush_configure_pwa_ios', false);
                if ($pwa_ios_enabled) {
                    // Save manifest.json file
                    $manifest_data = $body->data->integration->manifest ?? null;
                    if ($manifest_data) {
                        // Get site icon URL for 512x512 size
                        $site_icon_url = get_site_icon_url(512);

                        // If no site icon, use plugin's default icon
                        if (!$site_icon_url) {
                            // Get the plugin's base URL
                            $plugin_url = plugin_dir_url(dirname(__FILE__));
                            $site_icon_url = $plugin_url . 'public/images/website-default-icon.png';
                        }

                        // Get site name and URL
                        $site_name = get_bloginfo('name');
                        $site_url = get_site_url();

                        // Update manifest data with site-specific values
                        $manifest_data->name = $site_name;
                        $manifest_data->short_name = $site_name;
                        $manifest_data->start_url = $site_url;
                        $manifest_data->icons = [
                            [
                                'src' => $site_icon_url,
                                'sizes' => '512x512',
                                'type' => 'image/png'
                            ]
                        ];

                        $manifest_file = ABSPATH . 'manifest.json';
                        // Use JSON_UNESCAPED_SLASHES to prevent escaping of forward slashes
                        file_put_contents(
                            $manifest_file,
                            json_encode($manifest_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        );

                        // Add PWA header code to the code_to_be_added_in_header_data
                        $pwa_header_code =
                            $body->data->integration->pwaIntegrationCode->header_additional_js_code ?? '';
                        if ($pwa_header_code) {
                            $code_to_be_added_in_header_data['pwa_header_code'] = $pwa_header_code;
                        }
                    }
                } else {
                    // Clean up PWA resources if disabled
                    // Remove manifest.json file if it exists
                    $manifest_file = ABSPATH . 'manifest.json';
                    if (file_exists($manifest_file)) {
                        unlink($manifest_file);
                    }

                    // Remove PWA header code from the code_to_be_added_in_header_data if it exists
                    if (isset($code_to_be_added_in_header_data['pwa_header_code'])) {
                        unset($code_to_be_added_in_header_data['pwa_header_code']);
                    }
                }

                if ($header_additional_js_code) {
                    $code_to_be_added_in_header_data = array_merge($code_to_be_added_in_header_data, [
                        'additional_js_code' => $header_additional_js_code
                    ]);
                }

                $codes = array_merge($codes, [
                    'code_to_be_added_in_header_data' => $code_to_be_added_in_header_data
                ]);

                if (isset($body->data->integration->ampIntegrationCode)) {
                    // Writing helper frame file
                    $helper_frame_filename =
                        $body->data->integration->ampIntegrationCode->helper_frame_filename ?? null;
                    $helper_frame = $body->data->integration->ampIntegrationCode->helper_frame ?? null;
                    if ($helper_frame_filename && $helper_frame) {
                        $helper_frame_file = ABSPATH . $helper_frame_filename;
                        file_put_contents($helper_frame_file, $helper_frame);
                    }

                    // Writing permission dialog file
                    $permission_dialog_filename =
                        $body->data->integration->ampIntegrationCode->permission_dialog_filename ?? null;
                    $permission_dialog = $body->data->integration->ampIntegrationCode->permission_dialog ?? null;
                    if ($permission_dialog_filename && $permission_dialog) {
                        $permission_dialog_file = ABSPATH . $permission_dialog_filename;
                        file_put_contents($permission_dialog_file, $permission_dialog);
                    }

                    // Getting AMP Header code URLs and data
                    $popup_data = $body->data->integration->ampIntegrationCode->popup_data ?? null;
                    $amp_code_to_be_added_in_header_data = [
                        'amp_button_color' => $popup_data->bg ?? '#0F77FF'
                    ];
                    $amp_code_widget_data = [
                        'amp_button_text' => $popup_data->button_text ?? 'Subscribe to Notifications',
                        'amp_unsubscribe_button' => $popup_data->unsubscribe_button ?? 'Unsubscribe'
                    ];

                    $codes = array_merge($codes, [
                        'amp_code_to_be_added_in_header_data' => $amp_code_to_be_added_in_header_data,
                        'amp_code_widget_data' => $amp_code_widget_data
                    ]);
                }

                update_option('unlimited_push_notifications_by_larapush_codes', $codes);
            } else {
                add_settings_error(
                    'unlimited-push-notifications-by-larapush-settings',
                    'my_connection_error',
                    'Error: ' . ABSPATH . ' is not writable, please make it writable.',
                    'error'
                );
                return false;
            }

            return true;
        } catch (\Throwable $error) {
            add_settings_error(
                'unlimited-push-notifications-by-larapush-settings',
                'my_connection_error',
                'Error: ' . $error->getMessage(),
                'error'
            );
            return false;
        }

        update_option('unlimited_push_notifications_by_larapush_panel_integration_tried', true);
        return false;
    }

    /**
     * Send notification to LaraPush Panel
     *
     * @param int $postId
     * @return bool
     *
     * @since 1.0.3
     */
    public static function send_notification($postId)
    {
        // Check if post is published
        $post = get_post($postId);
        if ($post->post_status != 'publish') {
            return false;
        }

        // get post meta
        $meta = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::get_meta($postId);

        // get url and path
        $url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_url', '')
        );
        $url_path = '/api/createCampaign';

        // get email and password
        $email = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_email', '')
        );
        $password = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::decode(
            get_option('unlimited_push_notifications_by_larapush_panel_password', '')
        );

        // check if all 3 fields exists
        if (empty($url) || empty($email) || empty($password)) {
            return false;
        }

        // Authenticate to LaraPush Panel
        $panel_url = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::assambleUrl($url, $url_path);
        $response = wp_remote_post($panel_url, [
            'headers' => [
                'Accept' => 'application/json',
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'email' => $email,
                'password' => $password,
                'domains' => get_option('unlimited_push_notifications_by_larapush_panel_domains_selected', []),
                'title' => $meta['title'],
                'message' => $meta['body'],
                'icon' => $meta['icon'],
                'image' => $meta['image'],
                'url' => $meta['url'],
                'schedule_now' => 1,
                'source' => 'Wordpress Plugin'
            ])
        ]);

        try {
            if (is_wp_error($response) || $response['response']['code'] != 200) {
                $error = is_wp_error($response)
                    ? $response->get_error_message()
                    : json_decode($response['body'])->message;
                set_transient('larapush_error', 'Error: ' . $error, 30);
                return false;
            } else {
                $body = json_decode($response['body']);
                if (!$body->success) {
                    set_transient('larapush_error', 'Error: ' . $body->message, 30);
                    return false;
                }
                set_transient('larapush_success', $body->message, 30);
                return true;
            }
        } catch (\Throwable $error) {
            set_transient('larapush_error', 'Error: ' . $error->getMessage(), 30);
            return false;
        }

        return false;
    }

    /**
     * Gets the meta data of the post
     *
     * @param int $postId
     * @return array
     */
    public static function get_meta($postId)
    {
        // Getting the post title
        $title = get_the_title($postId);

        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

        // Converting Get the description of the post
        $body = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::get_description($postId);

        // Getting the post icon
        $icon = get_site_icon_url(32) == '' ? '' : get_site_icon_url(32);

        // Getting the post url
        $url = get_permalink($postId);

        // Getting the post image
        $image = get_the_post_thumbnail_url($postId);

        // If the post image is not found, get the first image of the post
        // If the first image is not found, use example.com/image so to not show the image
        if ($image == false) {
            $image = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::first_image_of_the_post($postId);
            if ($image == false) {
                $image = '';
            }
        }

        return [
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'url' => $url,
            'image' => $image,
            'postId' => $postId
        ];
    }

    /**
     * Gets the first image url of the post
     *
     * @param int $postId
     * @return string|false
     */
    public static function first_image_of_the_post($postId)
    {
        // Getting the post from ID
        $post = get_post($postId);

        // Getting the post content
        $content = $post->post_content;

        // Use a regular expression to search for the first image in the post content
        preg_match('/<(img|amp-img).+src=[\'"](?P<src>.+?)[\'"].*>/i', $content, $image);

        // If an image is found, return the image URL
        if (!empty($image)) {
            $image_url = $image['src'];
            return $image_url;
        } else {
            return false;
        }
    }

    /**
     * Get the description of the post
     *
     * @param string $postId
     * @return string
     */
    public static function get_description($postId)
    {
        // Getting the description from the post excerpt
        $description = get_post_field('post_excerpt', $postId);

        // Yoast SEO plugin.
        if (empty($description) || !is_string($description)) {
            $description = get_post_meta($postId, '_yoast_wpseo_metadesc', true);
        }

        // Rank Math SEO plugin.
        if (empty($description) || !is_string($description)) {
            $description = get_post_meta($postId, 'rank_math_description', true);
        }

        // The SEO Framework
        if (empty($description) || !is_string($description)) {
            $description = get_post_meta($postId, '_genesis_description', true);
        }

        // SEOPress
        if (empty($description) || !is_string($description)) {
            $description = get_post_meta($postId, '_seopress_titles_desc', true);
        }

        // All In One SEO
        if (empty($description) || !is_string($description)) {
            $description = get_post_meta($postId, '_aioseo_og_description', true);
        }

        if (empty($description) || !is_string($description)) {
            // Getting the post content
            $html = get_post_field('post_content', $postId);
            $html = apply_filters('the_content', $html);

            if (empty($html)) {
                return $html;
            }

            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);

            foreach ($xpath->query('//script') as $script) {
                $script->parentNode->removeChild($script);
            }

            $dom->preserveWhiteSpace = false;
            $description = $dom->textContent;
        }

        // Limiting the description to 14 words
        $description = explode(' ', $description);
        $description = implode(' ', array_slice($description, 0, 14));

        return $description;
    }

    /**
     * Checks if firebase messaging file is present in root of the website
     *
     * @return bool
     * @since 1.0.8
     */
    public static function isFirebaseMessagingFilePresent()
    {
        try {
            $codes = get_option('unlimited_push_notifications_by_larapush_codes', []);
            $filename =
                isset($codes['sw_filename']) && $codes['sw_filename']
                    ? $codes['sw_filename']
                    : 'firebase-messaging-sw.js';

            $domain = Unlimited_Push_Notifications_By_Larapush_Admin_Helper::getDomain();
            $url = 'https://' . $domain . '/' . $filename;
            $response = wp_remote_get($url);
            return $response['response']['code'] === 200;
        } catch (\Throwable $error) {
            return true;
        }
    }

    /**
     * Checks if the user can show the push on publish delay
     *
     * @return bool
     *
     * @since 1.0.6
     */
    public static function canShowPushOnPublishDelay()
    {
        $plan = get_option('unlimited_push_notifications_by_larapush_panel_plan', '');
        $version = get_option('unlimited_push_notifications_by_larapush_panel_version', '1.0.0');

        return ($plan === 'pro' || $plan === 'premium') && version_compare($version, '5.0.0', '>=');
    }

    /**
     * Checks if the user can show the PWA iOS configuration
     *
     * @return bool
     *
     * @since 1.0.9
     */
    public static function canShowPWAiOS()
    {
        $version = get_option('unlimited_push_notifications_by_larapush_panel_version', '1.0.0');

        return version_compare($version, '5.1.9', '>=');
    }

    /**
     * Checks if the current installation is a subdirectory installation & if the panel is 5.0.0 or higher
     *
     * @return bool
     * @since 1.0.8
     */
    public static function isSubdirectoryInstallation()
    {
        $version = get_option('unlimited_push_notifications_by_larapush_panel_version', '1.0.0');
        if (version_compare($version, '5.0.0', '<')) {
            return false;
        }

        $parsed_url = parse_url(get_site_url());
        return isset($parsed_url['path']) && $parsed_url['path'] !== '/';
    }

    /**
     * Gets the main domain from a URL
     *
     * @param string $url
     * @return string
     * @since 1.0.8
     */
    public static function getDomain()
    {
        if (self::isSubdirectoryInstallation()) {
            $parsed_url = parse_url(get_site_url());
            $host = $parsed_url['host'];
            return $host;
        }

        return str_replace(['https://', 'http://'], '', get_site_url());
    }
}
