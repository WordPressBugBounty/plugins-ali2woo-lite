<?php

/**
 * Description of RequestHelper
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class RequestHelper {
    public static function build_request(string $function, array $params = []): string
    {
        $aliexpressToken = AliexpressToken::getInstance()->defaultToken();

        $request_url = get_setting('api_endpoint') . $function . '.php?' .
            Account::getInstance()->build_params() .
            AliexpressLocalizator::getInstance()->build_params(isset($params['lang'])) .
            "&su=" . urlencode(site_url()) .
            "&ae_token=" . ($aliexpressToken ? $aliexpressToken->accessToken : '');

        if (!empty($params)) {
            foreach ($params as $key => $val) {
                $request_url .= "&" .
                    str_replace("%7E", "~", rawurlencode($key)) . "=" .
                    str_replace("%7E", "~", rawurlencode($val));
            }
        }

        return $request_url;
    }
}
