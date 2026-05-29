<?php

/**
 * Description of AssetService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class AssetService
{
    private ApiClient $ApiClient;

    public function __construct(ApiClient $ApiClient)
    {
        $this->ApiClient = $ApiClient;
    }

    public function getLogoUrl(string $file): string
    {
        return $this->ApiClient->assetUrl($file, A2WL()->version);
    }
}
