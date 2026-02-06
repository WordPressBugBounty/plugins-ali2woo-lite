<?php

/**
 * Description of GlobalMessageAjaxController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 *
 * @ajax: true
 */

namespace AliNext_Lite;;

use Pages;

class GlobalMessageAjaxController extends AbstractController
{

    public function __construct(
        protected GlobalSystemMessageService $GlobalSystemMessageService
    ) {
        parent::__construct();

        add_action('wp_ajax_a2wl_remove_critical_message', [$this, 'removeCriticalMessage']);
    }

    public function removeCriticalMessage(): void
    {
        $this->verifyNonceAjax();

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            wp_send_json_error(ResultBuilder::buildError($this->getErrorTextNoPermissions()));
        }

        $code = sanitize_text_field(wp_unslash($_POST['code'] ?? ''));
        if ($code) {
            $this->GlobalSystemMessageService->deleteCriticalMessage($code);
        }

        wp_send_json_success();
    }
}
