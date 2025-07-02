<?php

/**
 * Description of DebugPageController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_before_admin_menu
 */

namespace AliNext_Lite;;

use Pages;

class DebugPageController extends AbstractAdminPage
{

    public function __construct()
    {
        if (a2wl_check_defined('A2WL_DEBUG_PAGE')) {
            parent::__construct(
                Pages::getLabel(Pages::DEBUG),
                Pages::getLabel(Pages::DEBUG),
                Capability::pluginAccess(),
                Pages::DEBUG,
                1100
            );
        }
    }

    public function render($params = []): void
    {
        if (!PageGuardHelper::canAccessPage(Pages::DEBUG)) {
            wp_die($this->getErrorTextNoPermissions());
        }

        echo "<br/><b>" .  Pages::getLabel(Pages::DEBUG) . "</b><br/>";
    }

}
