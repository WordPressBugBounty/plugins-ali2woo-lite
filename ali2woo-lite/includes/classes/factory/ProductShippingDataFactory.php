<?php
/**
 * Description of ProductShippingDataFactory
 *
 * @author Ali2Woo Team
 *
 */

namespace AliNext_Lite;;

class ProductShippingDataFactory
{
    public function buildFromProductShippingMeta(array $data): ProductShippingData
    {
        return (new ProductShippingData())
            ->setShippingInfo($data[ProductShippingData::FIELD_SHIPPING_INFO] ?? [])
            ->setCountryTo($data[ProductShippingData::FIELD_COUNTRY_TO] ?? '')
            ->setCountryFrom($data[ProductShippingData::FIELD_COUNTRY_FROM] ?? '')
            ->setMethod($data[ProductShippingData::FIELD_METHOD] ?? '')
            ->setCost($data[ProductShippingData::FIELD_COST] ?? '')
            ->setVariationKey($data[ProductShippingData::FIELD_VARIATION_KEY] ?? null);
    }
}
