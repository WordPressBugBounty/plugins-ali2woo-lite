<?php

/**
 * Description of GlobalSystemMessageService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class GlobalSystemMessageService
{
    public const MESSAGE_TYPE_SUCCESS ='success';
    public const MESSAGE_TYPE_ERROR ='error';
    public const MESSAGE_TYPE_WARNING = 'warning';
    public const MESSAGE_TYPE_INFO = 'info';


    /**
     * Get all messages: system + critical
     */
    public function getAllMessages(): array
    {
        $system_message = get_setting(Settings::SETTING_SYSTEM_MESSAGE);
        $messages = [];

        if (!empty($system_message)) {
            foreach ($system_message as $key => $message) {
                if (!empty($message['message'])) {
                    $message_class = 'updated';
                    if ($message['type'] === self::MESSAGE_TYPE_ERROR) {
                        $message_class = 'error';
                    } elseif ($message['type'] === self::MESSAGE_TYPE_WARNING) {
                        $message_class = 'warning';
                    }

                    $messages[] =
                        '<div id="a2wl-system-message-' . esc_attr($key) .
                        '" class="a2wl-system-message notice ' . esc_attr($message_class) .
                        ' is-dismissible"><p>' .
                        esc_html($message['message']) .
                        '</p></div>';
                }
            }
        }

// critical messages
        $critical_messages = get_setting(Settings::SETTING_CRITICAL_MESSAGES);
        if (!empty($critical_messages)) {
            foreach ($critical_messages as $key => $message) {
                if (!empty($message['text'])) {

                    $message_class = 'error';
                    if (!empty($message['type'])) {
                        if ($message['type'] === self::MESSAGE_TYPE_WARNING) {
                            $message_class = 'warning';
                        } elseif ($message['type'] === self::MESSAGE_TYPE_INFO) {
                            $message_class = 'success';
                        }
                    }

                    $text = esc_html($message['text']);
                    if (!empty($message['link'])) {
                        $link_text = !empty($message['link_text'])
                            ? esc_html($message['link_text'])
                            : esc_html__('Click here', 'ali2woo');

                        $text .= ' <a href="' . esc_url($message['link']) . '">' . $link_text . '</a>';
                    }

                    $messages[] =
                        '<div id="a2wl-critical-message-' . esc_attr($key) .
                        '" class="a2wl-critical-message notice notice-' . esc_attr($message_class) .
                        ' is-dismissible" data-code="' . esc_attr($message['code']) . '"><p>' .
                        $text .
                        '</p></div>';
                }
            }
        }

        return $messages;
    }

    public function deleteCriticalMessage(string $messageCode): void
    {
        $messages = get_setting(Settings::SETTING_CRITICAL_MESSAGES, []);

        $messages = array_filter($messages, static function ($message) use ($messageCode) {
            return $message['code'] !== $messageCode;
        });

        set_setting(Settings::SETTING_CRITICAL_MESSAGES, array_values($messages));
    }


    public function clearCritical(): void
    {
        set_setting(Settings::SETTING_CRITICAL_MESSAGES, []);
    }

    public function clearSystem(): void
    {
        set_setting(Settings::SETTING_SYSTEM_MESSAGE, []);
    }

    public function clear(): void
    {
        set_setting(Settings::SETTING_SYSTEM_MESSAGE, []);
        set_setting(Settings::SETTING_CRITICAL_MESSAGES, []);
    }

    public function addErrorMessage(string $message): void
    {
        $this->addNewMessage(self::MESSAGE_TYPE_ERROR, $message);
    }

    public function addSuccessMessage(string $message): void
    {
        $this->addNewMessage(self::MESSAGE_TYPE_SUCCESS, $message);
    }

    public function addWarningMessage(string $message): void
    {
        $this->addNewMessage(self::MESSAGE_TYPE_WARNING, $message);
    }

    public function addMessages(array $messages): void
    {
        foreach ($messages as $message) {
            if (!empty($message['type']) && !empty($message['message'])) {
                if ($message['type'] === self::MESSAGE_TYPE_SUCCESS) {
                    $this->addNewMessage(self::MESSAGE_TYPE_SUCCESS, $message['message']);
                } elseif ($message['type'] === self::MESSAGE_TYPE_ERROR) {
                    $this->addNewMessage(self::MESSAGE_TYPE_ERROR, $message['message']);
                } elseif ($message['type'] === self::MESSAGE_TYPE_WARNING) {
                    $this->addNewMessage(self::MESSAGE_TYPE_WARNING, $message['message']);
                }
            }
        }
    }

    private function getMessagesData(): array
    {
        $allMessages = get_setting(Settings::SETTING_SYSTEM_MESSAGE);

        return !empty($allMessages) ? $allMessages : [];
    }

    private function addNewMessage(string $type, string $message): void
    {
        $allMessages = $this->getMessagesData();
        $allMessages[] = ['type' => $type, 'message' => $message];
        set_setting(Settings::SETTING_SYSTEM_MESSAGE, $allMessages);
    }
}
