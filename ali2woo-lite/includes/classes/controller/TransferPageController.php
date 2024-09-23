<?php

/**
 * Description of TransferPageController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

class TransferPageController extends AbstractAdminPage
{
    public function __construct()
    {
        parent::__construct(esc_html__('Transfer', 'ali2woo'), esc_html__('Transfer', 'ali2woo'), 'import', 'a2wl_transfer', 95);
    }

    public function render($params = array())
    {
        if (!current_user_can('manage_options')) {
            wp_die($this->getErrorTextNoPermissions());
        }

        $this->saveHandler();
        $this->model_put("hash", $this->getSettingsString());
        $this->include_view("transfer.php");
    }

    private function getSettingsString()
    {
        $settings = get_option('a2wl_settings', array());

        $hash = base64_encode(serialize($settings));

        return $hash;
    }

    private function saveHandler()
    {
        if (isset($_POST['transfer_form']) && !empty($_POST['hash'])) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
            if (!$settings = base64_decode($_POST['hash'])) {
                $this->model_put("error", esc_html__('Hash is not correct', 'ali2woo'));
                return;
            }

            if (!$settings = unserialize($settings)) {
                $this->model_put("error", esc_html__('Hash is not correct', 'ali2woo'));
                return;
            }

            update_option('a2wl_settings', $settings);
        }
    }
}
