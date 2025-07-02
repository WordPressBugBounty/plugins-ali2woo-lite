<?php

/**
 * Description of TransferPageController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

use Pages;

class TransferPageController extends AbstractAdminPage
{
    public const FIELD_HASH = "hash";

    public function __construct()
    {
        parent::__construct(
            Pages::getLabel(Pages::TRANSFER),
            Pages::getLabel(Pages::TRANSFER),
            Capability::pluginAccess(),
            Pages::TRANSFER,
            95
        );
    }

    public function render($params = []): void
    {
        if (!PageGuardHelper::canAccessPage(Pages::TRANSFER)) {
            wp_die($this->getErrorTextNoPermissions());
        }

        $this->saveHandler();
        $this->model_put(self::FIELD_HASH, $this->getSettingsString());
        $this->include_view("transfer.php");
    }

    private function getSettingsString(): string
    {
        $settings = get_option('a2wl_settings', []);
        $settingsJson = json_encode($settings);

        return base64_encode($settingsJson);
    }

    private function saveHandler(): void
    {
        if (isset($_POST['transfer_form']) && !empty($_POST[self::FIELD_HASH])) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);

            $settingsJson = base64_decode($_POST[self::FIELD_HASH]);

            if (!$settingsJson) {
                $this->model_put("error",
                    esc_html_x('Hash is not correct', 'error text', 'ali2woo')
                );

                return;
            }

            $settings = json_decode($settingsJson, true);

            if (!$settings) {
                $this->model_put("error",
                    esc_html_x('Hash is not correct', 'error text', 'ali2woo')
                );

                return;
            }

            update_option('a2wl_settings', $settings);
        }
    }
}
