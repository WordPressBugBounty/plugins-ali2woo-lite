<?php

namespace AliNext_Lite;;

class AliexpressTokenDto
{
    public const FIELD_USER_ID = 'user_id';
    public const FIELD_ACCESS_TOKEN = 'access_token';
    public const FIELD_REFRESH_TOKEN = 'refresh_token';
    public const FIELD_EXPIRE_TIME = 'expire_time';
    public const FIELD_REFRESH_EXPIRE_TIME = 'refresh_token_valid_time';
    public const FIELD_USER_NICK = 'user_nick';
    public const FIELD_ACCOUNT = 'account';
    public const FIELD_ACCOUNT_PLATFORM = 'account_platform';
    public const FIELD_LOCALE = 'locale';
    public const FIELD_SP = 'sp';
    public const FIELD_HAVANA_ID = 'havana_id';
    public const FIELD_SELLER_ID = 'seller_id';
    public const FIELD_REQUEST_ID = 'request_id';
    public const FIELD_TRACE_ID = '_trace_id_';
    public const FIELD_CODE = 'code';
    public const FIELD_DEFAULT = 'default';

    public string $userId;
    public string $accessToken;
    public string $refreshToken;
    public int $expireTime; // ms
    public int $refreshExpireTime; // ms
    public ?string $userNick = null;
    public ?string $account = null;
    public ?string $accountPlatform = null;
    public ?string $locale = null;
    public ?string $sp = null;
    public ?string $havanaId = null;
    public ?string $sellerId = null;
    public ?string $requestId = null;
    public ?string $traceId = null;
    public int $code = 0;
    public bool $default = false;

    private function __construct() {}

    public static function build(array $data): self
    {
        $dto = new self();
        $dto->userId = $data[self::FIELD_USER_ID];
        $dto->accessToken = $data[self::FIELD_ACCESS_TOKEN];
        $dto->refreshToken = $data[self::FIELD_REFRESH_TOKEN];
        $dto->expireTime = (int)$data[self::FIELD_EXPIRE_TIME];
        $dto->refreshExpireTime = (int)($data[self::FIELD_REFRESH_EXPIRE_TIME] ?? 0);
        $dto->userNick = $data[self::FIELD_USER_NICK] ?? null;
        $dto->account = $data[self::FIELD_ACCOUNT] ?? null;
        $dto->accountPlatform = $data[self::FIELD_ACCOUNT_PLATFORM] ?? null;
        $dto->locale = $data[self::FIELD_LOCALE] ?? null;
        $dto->sp = $data[self::FIELD_SP] ?? null;
        $dto->havanaId = $data[self::FIELD_HAVANA_ID] ?? null;
        $dto->sellerId = $data[self::FIELD_SELLER_ID] ?? null;
        $dto->requestId = $data[self::FIELD_REQUEST_ID] ?? null;
        $dto->traceId = $data[self::FIELD_TRACE_ID] ?? null;
        $dto->code = (int)($data[self::FIELD_CODE] ?? 0);
        $dto->default = !empty($data[self::FIELD_DEFAULT]);
        return $dto;
    }

    public function isExpired(): bool
    {
        return $this->expireTime < time() * 1000;
    }

    public function isAlmostExpired(int $thresholdSeconds = 432000): bool
    {
        $remaining = $this->expireTime - (time() * 1000);
        return $remaining <= ($thresholdSeconds * 1000);
    }

    public function isRefreshExpired(): bool
    {
        return $this->refreshExpireTime < time() * 1000;
    }

    public function getTokenRegionCode(): string
    {
        $code = '';
        if (preg_match('/^[a-zA-Z]{2,4}/', $this->userNick, $matches)) {
            $code = $matches[0];
        }

        return $code;
    }

    public function getExpireDateFormatted(): string
    {
        return gmdate("F j, Y, H:i:s", intdiv($this->expireTime, 1000));
    }

    public function toArray(): array
    {
        return [
            self::FIELD_USER_ID => $this->userId,
            self::FIELD_ACCESS_TOKEN => $this->accessToken,
            self::FIELD_REFRESH_TOKEN => $this->refreshToken,
            self::FIELD_EXPIRE_TIME => $this->expireTime,
            self::FIELD_REFRESH_EXPIRE_TIME => $this->refreshExpireTime,
            self::FIELD_USER_NICK => $this->userNick,
            self::FIELD_ACCOUNT => $this->account,
            self::FIELD_ACCOUNT_PLATFORM => $this->accountPlatform,
            self::FIELD_LOCALE => $this->locale,
            self::FIELD_SP => $this->sp,
            self::FIELD_HAVANA_ID => $this->havanaId,
            self::FIELD_SELLER_ID => $this->sellerId,
            self::FIELD_REQUEST_ID => $this->requestId,
            self::FIELD_TRACE_ID => $this->traceId,
            self::FIELD_CODE => $this->code,
            self::FIELD_DEFAULT => $this->default,
        ];
    }
}
