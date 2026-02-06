<?php

/**
 * Service class for managing Aliexpress tokens.
 *
 * Handles token refresh logic using refresh_token,
 * ensures tokens are valid before API calls,
 * and updates the token store accordingly.
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class AliexpressTokenService
{
    private AliexpressToken $TokenStore;
    private PlatformClient $PlatformClient;

    public function __construct(
        AliexpressToken $TokenStore,
        PlatformClient $PlatformClient
    ) {
        $this->TokenStore = $TokenStore;
        $this->PlatformClient = $PlatformClient;
    }

    /**
     * Refresh the access token for a given user.
     *
     * - Checks if refresh_token exists and is still valid.
     * - If access_token is expired but refresh_token is valid,
     *   requests a new access_token from the API.
     * - Updates the token store with new values.
     *
     * @param string $userId
     * @return AliexpressTokenDto|false Updated token DTO if refresh succeeded or not needed, false otherwise.
     */
    public function refresh(string $userId): AliexpressTokenDto|false
    {
        $token = $this->TokenStore->token($userId);
        if (!$token instanceof AliexpressTokenDto || empty($token->refreshToken)) {
            return false;
        }

        // Check if refresh_token itself has expired
        if ($token->isRefreshExpired()) {
            return false;
        }

        // Attempt to refresh access_token using API
        $response = $this->PlatformClient->refreshToken($token->refreshToken);
        if (!$response->isStateOk()) {
            return false;
        }

        $data = $response->getData();

        if (isset($data['token'])) {
            $data = $data['token'];
        }

        // Validate response data
        if (empty($data['access_token']) || empty($data['expires_in'])) {
            return false;
        }

        // Build updated DTO
        $updated = AliexpressTokenDto::build(array_merge(
            $token->toArray(),
            [
                AliexpressTokenDto::FIELD_ACCESS_TOKEN => $data['access_token'],
                AliexpressTokenDto::FIELD_EXPIRE_TIME => (int)$data['expire_time'],
                AliexpressTokenDto::FIELD_REFRESH_TOKEN => $data['refresh_token'] ?? $token->refreshToken,
                AliexpressTokenDto::FIELD_REFRESH_EXPIRE_TIME => (int)$data['refresh_token_valid_time'],
                AliexpressTokenDto::FIELD_USER_ID => $data['user_id'] ?? $token->userId,
                AliexpressTokenDto::FIELD_SELLER_ID => $data['seller_id'] ?? $token->sellerId,
                AliexpressTokenDto::FIELD_USER_NICK => $data['user_nick'] ?? $token->userNick,
                AliexpressTokenDto::FIELD_ACCOUNT => $data['account'] ?? $token->account,
                AliexpressTokenDto::FIELD_LOCALE => $data['locale'] ?? $token->locale,
                AliexpressTokenDto::FIELD_SP => $data['sp'] ?? $token->sp,
                AliexpressTokenDto::FIELD_HAVANA_ID => $data['havana_id'] ?? $token->havanaId,
                AliexpressTokenDto::FIELD_ACCOUNT_PLATFORM => $data['account_platform'] ?? $token->accountPlatform,
                AliexpressTokenDto::FIELD_CODE => (int)($data['code'] ?? 0),
                AliexpressTokenDto::FIELD_REQUEST_ID => $data['request_id'] ?? '',
                AliexpressTokenDto::FIELD_TRACE_ID => $data['_trace_id_'] ?? '',
            ]
        ));

        // Save updated token back to store
        $result = $this->TokenStore->update($userId, $updated);

        return $updated;
    }

    /**
     * Get a valid token for a given user.
     *
     * - Returns the token if still valid.
     * - If expired, attempts to refresh automatically.
     * - Returns null if refresh fails.
     *
     * @param string $userId
     * @return AliexpressTokenDto|null
     */
    public function getValidToken(string $userId): ?AliexpressTokenDto
    {
        $token = $this->TokenStore->token($userId);
        if (!$token instanceof AliexpressTokenDto) {
            return null;
        }

        // If access_token expired, try to refresh
        if ($token->isExpired()) {
            $refreshed = $this->refresh($userId);
            return $refreshed instanceof AliexpressTokenDto ? $refreshed : null;
        }

        return $token;
    }
}
