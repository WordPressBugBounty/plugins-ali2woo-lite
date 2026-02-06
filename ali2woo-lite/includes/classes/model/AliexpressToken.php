<?php

/**
 * Token storage service for Aliexpress accounts.
 *
 * Works with AliexpressTokenDto instead of raw arrays.
 *
 * @author Ali2Woo
 */

namespace AliNext_Lite;;

class AliexpressToken
{
    protected static ?AliexpressToken $_instance = null;

    public static function getInstance(): AliexpressToken
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Get all stored tokens as DTO objects.
     *
     * @return AliexpressTokenDto[]
     */
    public function tokens(): array
    {
        $rawTokens = get_setting('aliexpress_access_tokens', []);

        return array_map(fn($t) => AliexpressTokenDto::build($t), $rawTokens);
    }

    /**
     * Save all tokens back to storage.
     *
     * @param AliexpressTokenDto[] $tokens
     */
    public function save(array $tokens): void
    {
        $rawTokens = array_map(fn($dto) => $dto->toArray(), $tokens);
        set_setting('aliexpress_access_tokens', $rawTokens);
    }

    /**
     * Add a new token if it does not already exist.
     *
     * @param AliexpressTokenDto $token
     */
    public function add(AliexpressTokenDto $token): void
    {
        $tokens = $this->tokens();
        foreach ($tokens as $t) {
            if ($token->userId === $t->userId) {
                return;
            }
        }
        // mark first token as default
        if (empty($tokens)) {
            $token->default = true;
        }
        $tokens[] = $token;

        $this->save($tokens);
    }

    /**
     * Delete a token by user_id.
     *
     * @param string $id
     */
    public function del(string $id): void
    {
        $tokens = array_filter($this->tokens(), fn($t) => $t->userId !== $id);
        $this->save($tokens);
    }

    /**
     * Get a token by user_id.
     *
     * @param string $id
     * @return AliexpressTokenDto|false
     */
    public function token(string $id): AliexpressTokenDto|false
    {
        foreach ($this->tokens() as $t) {
            if ($id === $t->userId) {

                return $t;
            }
        }

        return false;
    }

    /**
     * Get the default token.
     *
     * @return AliexpressTokenDto|false
     */
    public function defaultToken(): AliexpressTokenDto|false
    {
        $tokens = $this->tokens();
        $anyAvailable = false;
        foreach ($tokens as $t) {
            $anyAvailable = $t;
            if ($t->default) {
                return $t;
            }
        }
        return $anyAvailable ?: false;
    }

    /**
     * Update an existing token by user_id.
     *
     * @param string $userId
     * @param AliexpressTokenDto $newToken
     * @return AliexpressTokenDto|false updated token if found, false otherwise
     */
    public function update(string $userId, AliexpressTokenDto $newToken): AliexpressTokenDto|false
    {
        $tokens = $this->tokens();
        $updated = false;

        foreach ($tokens as $k => $t) {
            if ($t->userId === $userId) {
                $tokens[$k] = $newToken;
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->save($tokens);
            return $newToken;
        }

        return false;
    }
}
