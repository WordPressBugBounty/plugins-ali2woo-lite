<?php

/**
 * Description of AliexpressHelper
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class AliexpressHelper
{
    /**
     * @param string $countryCode
     * @return string Convert WooCommerce country code to AliExpress country code
     */
    public function convertToAliexpressCountryCode(string $countryCode): string
    {
        return ProductShippingData::normalize_country($countryCode);
    }
}
