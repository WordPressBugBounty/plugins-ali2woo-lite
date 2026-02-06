<?php

/**
 * Service for validating Aliexpress tokens and updating critical messages.
 *
 * - Checks if a default token exists.
 * - Ensures the token is valid and attempts proactive refresh if it is about to expire.
 * - Marks token as expired if refresh fails or token lifetime is over.
 * - Updates critical messages accordingly.
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class AliexpressTokenValidationService
{
    private AliexpressToken $TokenStore;
    private AliexpressTokenService $TokenService;
    private CriticalMessageService $CriticalMessageService;

    public function __construct(
        AliexpressToken $TokenStore,
        AliexpressTokenService $TokenService,
        CriticalMessageService $CriticalMessageService
    ) {
        $this->TokenStore = $TokenStore;
        $this->TokenService = $TokenService;
        $this->CriticalMessageService = $CriticalMessageService;
    }

    /**
     * Validate the default token and update critical messages.
     *
     * - If no default token exists, add a warning or error message.
     * - If the token is almost expired (within 5 days), attempt proactive refresh.
     * - If the token is expired and refresh fails, add an error message.
     * - If valid, resolve all error messages.
     */
    public function validate(): void
    {
        $token = $this->TokenStore->defaultToken();

        if (!$token) {
            $tokens = $this->TokenStore->tokens();

            if (!empty($tokens)) {
                $this->CriticalMessageService->addMessage(
                    CriticalMessageService::TYPE_NO_DEFAULT_TOKEN_SELECTED,
                    __('You have Aliexpress accounts connected, but no default account is selected. Please mark one as default in settings.', 'ali2woo'),
                    'warning',
                    admin_url('admin.php?page=a2wl_setting&subpage=account'),
                    __('Go to Account Settings', 'ali2woo')
                );
            } else {
                $this->CriticalMessageService->addMessage(
                    CriticalMessageService::TYPE_TOKEN_MISSING,
                    __('No Aliexpress account is connected. Please connect your account.', 'ali2woo'),
                    'error',
                    admin_url('admin.php?page=a2wl_setting&subpage=account'),
                    __('Go to Account Settings', 'ali2woo')
                );
            }
            return;
        }

        // If token is about to expire soon, try proactive refresh
        if ($token->isAlmostExpired()) {
            $refreshedToken = $this->TokenService->refresh($token->userId);
            if ($refreshedToken instanceof AliexpressTokenDto) {
                // Token successfully refreshed, resolve error messages
                $this->CriticalMessageService->resolveMessage(CriticalMessageService::TYPE_TOKEN_EXPIRED);
                $this->CriticalMessageService->resolveMessage(CriticalMessageService::TYPE_TOKEN_MISSING);
                $this->CriticalMessageService->resolveMessage(CriticalMessageService::TYPE_NO_DEFAULT_TOKEN_SELECTED);

                // Add informational message about auto-refresh
                $this->CriticalMessageService->addMessage(
                    CriticalMessageService::TYPE_TOKEN_REFRESHED,
                    __('The default Aliexpress account token was automatically refreshed.', 'ali2woo'),
                    'info',
                    admin_url('admin.php?page=a2wl_setting&subpage=account'),
                    __('View Account Settings', 'ali2woo')
                );

                // Replace $token with the refreshed one for further checks
                $token = $refreshedToken;

                return;
            }
        }

        // If token is already expired, show error message
        if ($token->isExpired()) {
            $this->CriticalMessageService->addMessage(
                CriticalMessageService::TYPE_TOKEN_EXPIRED,
                __('The default Aliexpress account token has expired. Please reconnect your default account.', 'ali2woo'),
                'error',
                admin_url('admin.php?page=a2wl_setting&subpage=account'),
                __('Go to Account Settings', 'ali2woo')
            );
            return;
        }

        // If token is valid, resolve all error messages
        $this->CriticalMessageService->resolveMessage(CriticalMessageService::TYPE_TOKEN_MISSING);
        $this->CriticalMessageService->resolveMessage(CriticalMessageService::TYPE_TOKEN_EXPIRED);
        $this->CriticalMessageService->resolveMessage(CriticalMessageService::TYPE_NO_DEFAULT_TOKEN_SELECTED);
    }

}
