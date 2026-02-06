<?php

/**
 * Description of CriticalMessageService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class CriticalMessageService
{
    public const TYPE_TOKEN_MISSING = 'token_missing';
    public const TYPE_TOKEN_EXPIRED = 'token_expired';
    public const TYPE_NO_DEFAULT_TOKEN_SELECTED  = 'no_default_token_selected';
    public const TYPE_TOKEN_REFRESHED = 'token_refreshed';

    private Settings $SettingsService;

    public function __construct(Settings $SettingsService)
    {
        $this->SettingsService = $SettingsService;
    }

    /**
     * Get all active (not-resolved) critical messages
     *
     * @return array<int,array{code:string,text:string,type:string,link:?string,resolved:bool}>
     */
    public function getActiveMessages(): array
    {
        $messages = $this->getMessagesData();
        return array_filter($messages, fn($m) => !$m['resolved']);
    }

    /**
     * Get first active message (if it needs to show just one)
     */
    public function getFirstActiveMessage(): ?array
    {
        foreach ($this->getMessagesData() as $message) {
            if (!$message['resolved']) {
                return $message;
            }
        }
        return null;
    }

    /**
     * Add one new critical message
     */
    public function addMessage(
        string $code,
        string $text,
        string $type = 'error',
        ?string $link = null,
        ?string $link_text = null
    ): void {
        $messages = $this->getMessagesData();

        // Check, whether such message exists
        foreach ($messages as $message) {
            if ($message['code'] === $code && !$message['resolved']) {
                return; // already active
            }
        }

        $messages[] = [
            'code' => $code,
            'text' => $text,
            'type' => $type, // error | warning | success
            'link' => $link, // optional URL
            'link_text' => $link_text, // optional link text
            'resolved'  => false,
        ];

        $this->SettingsService->set(Settings::SETTING_CRITICAL_MESSAGES, $messages);
        $this->SettingsService->commit();
    }

    /**
     * Mark message as resolved
     */
    public function resolveMessage(string $code): void
    {
        $messages = $this->getMessagesData();

        foreach ($messages as &$message) {
            if ($message['code'] === $code) {
                $message['resolved'] = true;
            }
        }

        $this->SettingsService->set(Settings::SETTING_CRITICAL_MESSAGES, $messages);
        $this->SettingsService->commit();
    }

    /**
     * Clear all critical messages
     */
    public function clear(): void
    {
        $this->SettingsService->set(Settings::SETTING_CRITICAL_MESSAGES, []);
        $this->SettingsService->commit();
    }

    /**
     * Get all messages from settings
     *
     * @return array<int,array{code:string,text:string,type:string,link:?string,resolved:bool}>
     */
    private function getMessagesData(): array
    {
        $messages = $this->SettingsService->get(Settings::SETTING_CRITICAL_MESSAGES, []);
        return is_array($messages) ? $messages : [];
    }
}
