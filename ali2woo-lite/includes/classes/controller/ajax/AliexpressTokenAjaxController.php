<?php

/**
 * Description of AliexpressTokenAjaxController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 *
 * @ajax: true
 */

namespace AliNext_Lite;;

use Pages;

class AliexpressTokenAjaxController extends AbstractController
{

    public function __construct(
        protected AliexpressToken $TokenStore,
        protected GlobalSystemMessageService $GlobalSystemMessageService
    ) {
        parent::__construct();

        add_action('wp_ajax_a2wl_build_aliexpress_api_auth_url', [$this, 'buildAliexpressApiAuthUrl']);
        add_action('wp_ajax_a2wl_save_access_token', [$this, 'saveAccessToken']);
        add_action('wp_ajax_a2wl_delete_access_token', [$this, 'deleteAccessToken']);
        add_action('wp_ajax_a2wl_refresh_access_token', [$this, 'refreshAccessToken']);

    }

    /**
     * Build Aliexpress API authorization URL.
     */
    public function buildAliexpressApiAuthUrl(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $state = urlencode(trailingslashit(get_bloginfo('wpurl')));

        $result = [
            'state' => 'ok',
            'url' => $this->buildAuthEndpointUrl($state)
        ];

        

        echo wp_json_encode($result);
        wp_die();
    }


    public function saveAccessToken(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            wp_send_json_error(ResultBuilder::buildError($this->getErrorTextNoPermissions()));
        }

        $result = [
            'state'   => 'error',
            'message' => __('Wrong params', 'ali2woo'),
        ];

        if (!empty($_POST['token']) && is_array($_POST['token'])) {
            $raw_tokens = array_map(
                static fn($token) => sanitize_text_field(wp_unslash($token)),
                $_POST['token']
            );

            $dto = AliexpressTokenDto::build($raw_tokens);

            $tokenStore = $this->TokenStore;
            $tokenStore->add($dto);

            $tokens = $tokenStore->tokens();
            $rows = [];

            foreach ($tokens as $t) {
                $rows[] = sprintf(
                    '<tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td><input type="checkbox" class="default" value="yes"%s /></td>
                    <td><a href="#" data-token-id="%s">%s</a></td>
                </tr>',
                    esc_html($t->userNick ?? '—'),
                    esc_html($t->getTokenRegionCode()),
                    esc_html($t->getExpireDateFormatted()),
                    $t->default ? ' checked' : '',
                    esc_attr($t->userId),
                    esc_html__('Delete', 'ali2woo')
                );
            }

            $result = [
                'state' => 'ok',
                'data' => implode('', $rows),
            ];
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function deleteAccessToken(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            wp_send_json_error(
                ResultBuilder::buildError($this->getErrorTextNoPermissions())
            );
        }

        $id = isset($_POST['id'])
            ? sanitize_text_field(wp_unslash($_POST['id']))
            : null;

        if ($id === null) {
            wp_send_json_error([
                'state'   => 'error',
                'message' => __('Wrong params', 'ali2woo'),
            ]);
        }

        // clear critical messages
        $this->GlobalSystemMessageService->clearCritical();
        $this->TokenStore->del($id);

        wp_send_json_success(['state' => 'ok']);
    }

    public function refreshAccessToken(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            wp_send_json_error(
                ResultBuilder::buildError($this->getErrorTextNoPermissions())
            );
        }

        // logic for refresh_token will be here
    }

    private function buildAuthEndpointUrl(string $state): string
    {
        $authEndpoint = 'https://api-sg.aliexpress.com/oauth/authorize';
        $redirectUri = get_setting('api_endpoint').'auth.php&state=' . $state;
        $clientId = get_setting('client_id');

        return sprintf(
            '%s?response_type=code&force_auth=true&redirect_uri=%s&client_id=%s',
            $authEndpoint,
            $redirectUri,
            $clientId
        );
    }

}

