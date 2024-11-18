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


    public function getAllMessages(): array
    {
        $system_message = get_setting('system_message');

        $messages = [];

        if ($system_message && !empty($system_message)) {
            foreach($system_message as $key => $message){
                if (!empty($message['message'])) {
                    $message_class = 'updated';
                    if ($message['type'] == 'error') {
                        $message_class = 'error';
                    }
                    $messages[] =
                        '<div id="a2wl-system-message-' .
                        $key .
                        '" class="a2wl-system-message notice ' .
                        $message_class .
                        ' is-dismissible"><p>' .
                        $message['message'] .
                        '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
                }
            }
        }

        return $messages;
    }

    public function clear(): void
    {
        set_setting(Settings::SETTING_SYSTEM_MESSAGE, []);
    }

    public function addErrorMessage(string $message): void
    {
        $this->addNewMessage(self::MESSAGE_TYPE_ERROR, $message);
    }

    public function addSuccessMessage(string $message): void
    {
        $this->addNewMessage(self::MESSAGE_TYPE_SUCCESS, $message);
    }

    public function addMessages(array $messages): void
    {
        foreach ($messages as $message) {
            if (!empty($message['type']) && !empty($message['message'])) {
                if ($message['type'] === self::MESSAGE_TYPE_SUCCESS) {
                    $this->addNewMessage(self::MESSAGE_TYPE_SUCCESS, $message);
                }
                elseif ($message['type'] === self::MESSAGE_TYPE_ERROR) {
                    $this->addNewMessage(self::MESSAGE_TYPE_ERROR, $message);
                }
            }
        }
    }

    private function getMessagesData(): array
    {
        $allMessages = get_setting(Settings::SETTING_SYSTEM_MESSAGE);
        if (!empty($allMessages)) {
            $allMessages = [];
        }

        return $allMessages;
    }

    private function addNewMessage(string $type, string $message): void
    {
        $allMessages = $this->getMessagesData();
        $allMessages[] = ['type' => $type, 'message' => $message];
        set_setting(Settings::SETTING_SYSTEM_MESSAGE, $allMessages);
    }
}
