<?php

/**
 * Description of ApiClient
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            get_setting(
                Settings::SETTING_API_ENDPOINT_V6,
                'https://api.ali2woo.com/v6/api/'
            ),
            '/'
        ) . '/';
    }

    public function post(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . ltrim($endpoint, '/');
        $args = [
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10,
        ];

        $response = a2wl_remote_post($url, wp_json_encode($payload), $args);

        if (is_wp_error($response)) {
            error_log('API error: ' . $response->get_error_message());
            return ['state' => 'error', 'message' => $response->get_error_message()];
        }

        if ((int)$response['response']['code'] !== 200) {
            error_log("API error code: {$response['response']['code']}, body: {$response['body']}");
            return ['state' => 'error', 'message' => 'Invalid response'];
        }

        return json_decode($response['body'], true) ?: ['state' => 'error', 'message' => 'Bad JSON'];
    }

    public function assetUrl(string $file, ?string $version = null): string
    {
        $url = $this->baseUrl . "assets/alinext-lite/{$file}";
        if ($version) {
            $url .= '?v=' . urlencode($version);
        }

        return $url;
    }
}
