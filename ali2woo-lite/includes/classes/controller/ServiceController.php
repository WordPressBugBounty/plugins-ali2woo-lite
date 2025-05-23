<?php

/**
 * Description of ServiceController
 *
 * @author Ali2Woo Team
 * 
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

class ServiceController
{
    public const SYSTEM_MESSAGE_UPDATE_PERIOD = 3600;

    public function __construct() {
        $system_message_last_update = intval(get_setting('plugin_data_last_update'));
        if (!$system_message_last_update || $system_message_last_update < time()) {
            set_setting('plugin_data_last_update', time() + self::SYSTEM_MESSAGE_UPDATE_PERIOD);
            $sync_model = new Synchronize();
            $request_url = RequestHelper::build_request(
                'sync_plugin_data', ['pc' => $sync_model->get_product_cnt()]
            );
            $request = a2wl_remote_get($request_url);
            if (!is_wp_error($request) && intval($request['response']['code']) == 200) {
                $plugin_data = json_decode($request['body'], true);
                $categories = isset($plugin_data['categories']) &&
                    is_array($plugin_data['categories']) ?
                    $plugin_data['categories'] : [];

                if (isset($plugin_data['messages'])) {
                    $GlobalSystemMessageService = A2WL()->getDI()->get('AliNext_Lite\GlobalSystemMessageService');
                    $GlobalSystemMessageService->addMessages($plugin_data['messages']);
                }

                update_option('a2wl_all_categories', $categories, 'no');
            }
        }
    }
}
