<?php

/**
 * Description of ExitSurveyService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ExitSurveyService
{
    private ApiClient $ApiClient;

    public function __construct(ApiClient $ApiClient)
    {
        $this->ApiClient = $ApiClient;
    }

    public function send(ExitSurveyDTO $dto): void
    {
        $result = $this->ApiClient->post('survey/alinext-lite/exit', $dto->toArray());

        if ($result['state'] !== 'ok') {
            error_log('Exit survey send failed: ' . print_r($result, true));
        }
    }

    public function getDeactivateLinkId(): string
    {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data = get_plugin_data(A2WL_PLUGIN_FILE, true, false);

        $plugin_slug = $plugin_data['slug'] ?? sanitize_title($plugin_data['Name']);

        return 'deactivate-' . $plugin_slug;
    }
}
