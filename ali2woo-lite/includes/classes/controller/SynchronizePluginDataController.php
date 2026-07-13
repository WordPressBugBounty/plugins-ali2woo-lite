<?php

/**
 * Description of SynchronizePluginDataController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

class SynchronizePluginDataController extends AbstractController
{
    private Synchronize $Synchronize;
    private GlobalSystemMessageService $GlobalSystemMessageService;

    public const SYSTEM_MESSAGE_UPDATE_PERIOD = 3600;

    public function __construct(
        Synchronize $Synchronize,
        GlobalSystemMessageService $GlobalSystemMessageService
    ) {
        parent::__construct();

        $this->Synchronize = $Synchronize;
        $this->GlobalSystemMessageService = $GlobalSystemMessageService;

        $system_message_last_update = intval(get_setting(Settings::SETTING_PLUGIN_DATA_LAST_UPDATE));
        if (!$system_message_last_update || $system_message_last_update < time()) {
            set_setting(
                Settings::SETTING_PLUGIN_DATA_LAST_UPDATE,
                time() + self::SYSTEM_MESSAGE_UPDATE_PERIOD
            );

            $request_url = RequestHelper::build_request(
                'sync_plugin_data',
                ['pc' => $this->Synchronize->get_product_cnt()]
            );
            $request = a2wl_remote_get($request_url);
            if (!is_wp_error($request) && intval($request['response']['code']) == 200) {
                $pluginData = json_decode($request['body'], true);
                $categories = isset($pluginData['categories']) &&
                    is_array($pluginData['categories']) ?
                    $pluginData['categories'] : [];

                if (isset($pluginData['messages'])) {
                    $this->GlobalSystemMessageService->addMessages($pluginData['messages']);
                }

                update_option('a2wl_all_categories', $categories, 'no');
            }
        }
    }
}
