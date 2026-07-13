<?php

/**
 * Description of AdminAssetsManagerController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_assets
 *
 */

namespace AliNext_Lite;;

class AdminAssetManagerController
{
    public function __construct()
    {
        wp_enqueue_style(
            'a2wl-tip-of-day',
            A2WL()->plugin_url() . '/assets/css/tip-of-day.css',
            [],
            A2WL()->version
        );
    }
}
