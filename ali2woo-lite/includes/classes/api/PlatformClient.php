<?php

/**
 * Description of PlatformClient
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class PlatformClient extends AbstractClient
{
    public function serverPing(): ApiResponse
    {
        $ping_url = RequestHelper::build_request('ping', ['r' => wp_rand()]);
        $request = a2wl_remote_get($ping_url);

        return $this->convertResponse($request);
    }

}
