<?php

/**
 * Description of ProductShippingCacheService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ProductShippingDataService
{

    protected ProductShippingDataRepository $ProductShippingDataRepository;

    public function __construct(
        ProductShippingDataRepository $ProductShippingDataRepository
    ) {
        $this->ProductShippingDataRepository = $ProductShippingDataRepository;
    }

    /**
     * @throws RepositoryException
     */
    /**
     * @throws RepositoryException
     */
    public function updateFromProduct(int $productId, array $product): void
    {
        $ProductShippingData = $this->ProductShippingDataRepository->get($productId);

        $hasShippingInfo = !empty($product[ImportedProductService::FIELD_SHIPPING_INFO]) &&
            is_array($product[ImportedProductService::FIELD_SHIPPING_INFO]);

        if ($hasShippingInfo) {
            $shippingInfo = $ProductShippingData->getShippingInfo();
            foreach ($product[ImportedProductService::FIELD_SHIPPING_INFO] as $mk => $data) {
                // if shipping info was saved without quantity
                $shippingInfo[$mk] = isset($data[0]['serviceName']) ? [1 => $data] : $data;
            }
            $ProductShippingData->setShippingInfo($shippingInfo);
        }

        if (isset($product[ImportedProductService::FIELD_METHOD])) {
            $ProductShippingData->setMethod($product[ImportedProductService::FIELD_METHOD]);
        }
        if (isset($product[ImportedProductService::FIELD_COUNTRY_TO])) {
            $ProductShippingData->setCountryTo($product[ImportedProductService::FIELD_COUNTRY_TO]);
        }
        if (isset($product[ImportedProductService::FIELD_COUNTRY_FROM])) {
            $ProductShippingData->setCountryFrom($product[ImportedProductService::FIELD_COUNTRY_FROM]);
        }
        if (isset($product[ImportedProductService::FIELD_COST])) {
            $ProductShippingData->setCost($product[ImportedProductService::FIELD_COST]);
        }

        if (isset($product[ImportedProductService::FIELD_VARIATION_KEY])) {
            $ProductShippingData->setVariationKey($product[ImportedProductService::FIELD_VARIATION_KEY]);
        }

        $this->ProductShippingDataRepository->save($productId, $ProductShippingData);
    }

    /**
     * @throws RepositoryException
     */
    public function saveItems(
        int $productId, string $countryFrom, string $countryTo, array $items, int $quantity = 1
    ): void {
        $ProductShippingData = $this->ProductShippingDataRepository->get($productId);
        $ProductShippingData->setItems(1, $countryFrom, $countryTo, $items);
        $this->ProductShippingDataRepository->save($productId, $ProductShippingData);
    }

    public function getCountryFromList(int $productId): array
    {
        global $wpdb;

        $query = "SELECT DISTINCT pm.meta_value FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm " .
            "ON (pm.post_id=p.ID AND pm.meta_key=%s) WHERE p.post_parent=%d AND p.post_type=%s";

        $query = $wpdb->prepare($query,ImportedProductService::KEY_COUNTRY_CODE, $productId, 'product_variation');
        $countryFromList = $wpdb->get_col($query);

        if (empty($countryFromList)) {
            $query = "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id=%d AND pm.meta_key=%s";

            $query = $wpdb->prepare($query, $productId, ImportedProductService::KEY_COUNTRY_CODE);
            $countryFromList = $wpdb->get_col($query);
        }

        if (empty($countryFromList)) {
            $countryFromList = ['CN'];
        }

        $result = [];

        foreach ($countryFromList as $countryCode) {
            $result[$countryCode] = Country::get_country($countryCode);
        }

        return $result;
    }

    /**
     * @throws RepositoryException
     */
    public function resetProductDefaultShipping(int $wcProductId): ProductShippingData
    {
        $ProductShippingData =
            $this->ProductShippingDataRepository
                ->get($wcProductId)
                ->resetDefaultShipping();

        $this->ProductShippingDataRepository->save($wcProductId, $ProductShippingData);

        return $ProductShippingData;
    }

    /**
     * @throws RepositoryException
     */
    public function resetProductShippingCache(int $wcProductId): ProductShippingData
    {
        $ProductShippingData =
            $this->ProductShippingDataRepository
                ->get($wcProductId)
                ->resetShippingInfo();

        $this->ProductShippingDataRepository->save($wcProductId, $ProductShippingData);

        return $ProductShippingData;
    }

}
