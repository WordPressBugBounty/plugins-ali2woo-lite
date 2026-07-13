<?php

/**
 * Description of TipOfDaySyncController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

class TipOfDaySyncController extends AbstractController
{
    private TipOfDaySyncService $TipOfDaySyncService;

    public const SYNC_PERIOD = 7200;

    public function __construct(
        TipOfDaySyncService $TipOfDaySyncService
    ) {
        parent::__construct();

        $this->TipOfDaySyncService = $TipOfDaySyncService;

        $last_update = intval(get_setting(Settings::SETTING_TIP_OF_DAY_LAST_SYNC));

        if (!$last_update || $last_update < time()) {
            set_setting(Settings::SETTING_TIP_OF_DAY_LAST_SYNC, time() + self::SYNC_PERIOD);

            $request_url = RequestHelper::build_request('sync_tips_of_day');

            $request = a2wl_remote_get($request_url);

            if (!is_wp_error($request) && intval($request['response']['code']) == 200) {
                $pluginData = json_decode($request['body'], true);

                if (is_array($pluginData)) {
                    $this->TipOfDaySyncService->syncFromApiData($pluginData);
                }
            }
        }
    }
}
