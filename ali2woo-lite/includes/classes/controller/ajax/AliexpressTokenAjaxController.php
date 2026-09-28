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
use Throwable;

class AliexpressTokenAjaxController extends AbstractController
{

    public function __construct(
        protected AliexpressToken $TokenStore,
        protected GlobalSystemMessageService $GlobalSystemMessageService,
        protected AliexpressTokenService $AliexpressTokenService,
    ) {
        parent::__construct();

        add_action('wp_ajax_a2wl_build_aliexpress_api_auth_url', [$this, 'buildAliexpressApiAuthUrl']);
        add_action('wp_ajax_a2wl_save_access_token', [$this, 'saveAccessToken']);
        add_action('wp_ajax_a2wl_delete_access_token', [$this, 'deleteAccessToken']);
        add_action('wp_ajax_a2wl_refresh_access_token', [$this, 'refreshAccessToken']);
        add_action('wp_ajax_a2wl_save_access_token_from_server', [$this, 'saveAccessTokenFromServer']);
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

        if (A2WL()->isFreePlugin()) {
            $tempPc = 'lite_' . bin2hex(random_bytes(8));
            $state = $state . ";" . $tempPc;
            $result = [
                'state' => 'ok',
                'url' => $this->buildAuthEndpointUrl($state),
                'tokenKey' => $tempPc,
            ];
        }
        


        echo wp_json_encode($result);
        wp_die();
    }

    public function saveAccessTokenFromServer(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            echo wp_json_encode([
                'state'   => 'error',
                'message' => $this->getErrorTextNoPermissions(),
            ]);
            wp_die();
        }

        $tokenKey = isset($_POST['token_key'])
            ? sanitize_text_field(wp_unslash($_POST['token_key']))
            : '';

        if ($tokenKey === '') {
            echo wp_json_encode([
                'state'   => 'error',
                'message' => __('Wrong params', 'ali2woo'),
            ]);
            wp_die();
        }

        try {
            $dto = $this->AliexpressTokenService->getTokenDataFromServer($tokenKey);

            if ($dto === null) {
                echo wp_json_encode([
                    'state'   => 'error',
                    'message' => __('Token not found or expired', 'ali2woo'),
                ]);
                wp_die();
            }

            $result = $this->persistToken($dto);

            echo wp_json_encode($result);
            wp_die();

        } catch (Throwable $e) {
            error_log('saveAccessTokenFromServer exception: ' . $e->getMessage());
            echo wp_json_encode([
                'state'   => 'error',
                'message' => __('Server error', 'ali2woo'),
            ]);
            wp_die();
        }
    }

    public function saveAccessToken(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            echo wp_json_encode(ResultBuilder::buildError($this->getErrorTextNoPermissions()));
            wp_die();
        }

        if (!empty($_POST['token']) && is_array($_POST['token'])) {
            $rawTokens = array_map(
                static fn($token) => sanitize_text_field(wp_unslash($token)),
                $_POST['token']
            );

            $dto = AliexpressTokenDto::build($rawTokens);

            $result = $this->persistToken($dto);
        } else {
            $result = [
                'state'   => 'error',
                'message' => __('Wrong params', 'ali2woo'),
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
        $redirectUri = get_setting('api_endpoint').'auth&state=' . $state;
        $clientId = get_setting('client_id');

        return sprintf(
            '%s?response_type=code&force_auth=true&redirect_uri=%s&client_id=%s',
            $authEndpoint,
            $redirectUri,
            $clientId
        );
    }

    /**
     * Save DTO token and return tokens HTML-table
     *
     * @param AliexpressTokenDto $dto
     * @return array
     */
    private function persistToken(AliexpressTokenDto $dto): array
    {
        $tokenStore = $this->TokenStore;
        $tokenStore->add($dto);

        $tokens = $tokenStore->tokens();
        $rows   = [];

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

        return [
            'state' => 'ok',
            'data'  => implode('', $rows),
        ];
    }

}

