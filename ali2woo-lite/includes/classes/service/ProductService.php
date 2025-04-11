<?php

/**
 * Description of ProductService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ProductService
{

    protected Aliexpress $AliexpressModel;
    protected ProductShippingDataRepository $ProductShippingDataRepository;
    protected Synchronize $SynchronizeModel;
    protected AliexpressHelper $AliexpressHelper;
    protected Woocommerce $WoocommerceModel;
    protected PriceFormulaService $PriceFormulaService;
    protected PurchaseCodeInfoService $PurchaseCodeInfoService;
    protected SynchronizePurchaseCodeInfoService $SynchronizePurchaseCodeInfoService;

    public function __construct(
        Aliexpress $AliexpressModel, ProductShippingDataRepository $ProductShippingDataRepository,
        Synchronize $SynchronizeModel, AliexpressHelper $AliexpressHelper, Woocommerce $WoocommerceModel,
        PriceFormulaService $PriceFormulaService, PurchaseCodeInfoService $PurchaseCodeInfoService,
        SynchronizePurchaseCodeInfoService $SynchronizePurchaseCodeInfoService
    ) {
        $this->AliexpressModel = $AliexpressModel;
        $this->ProductShippingDataRepository = $ProductShippingDataRepository;
        $this->SynchronizeModel = $SynchronizeModel;
        $this->AliexpressHelper = $AliexpressHelper;
        $this->WoocommerceModel = $WoocommerceModel;
        $this->PriceFormulaService = $PriceFormulaService;
        $this->PurchaseCodeInfoService = $PurchaseCodeInfoService;
        $this->SynchronizePurchaseCodeInfoService = $SynchronizePurchaseCodeInfoService;
    }

    /**
     * @param array $products
     * @param array $params
     * @return array
     * @throws ServiceException
     * @todo this method should be improved, add exception, use DTO instead array result
     */
    public function synchronizeProducts(array $products, array $params = []): array
    {
        $productIds = array_map([$this, 'generateComplexProductId'], $products);
        $productCount = $this->SynchronizeModel->get_product_cnt();
        $params['pc'] = $productCount;

        $isManualUpdate = isset($params['manual_update']) && $params['manual_update'];

        $this->SynchronizePurchaseCodeInfoService->runSyncPurchaseCodeInfoProcess();

        if (!$isManualUpdate) {
            $allowAutoUpdate = $this->PurchaseCodeInfoService->checkAutoUpdateMaxQuota();

            if (!$allowAutoUpdate) {
                $errorMessage = _x('You have reached max autoupdate quota', 'error', 'ali2woo');
                return ResultBuilder::buildError($errorMessage);
            }
        }

        $result = $this->AliexpressModel->sync_products($productIds, $params);

        $sync_default_shipping_cost = $isManualUpdate
            && a2wl_check_defined('A2WL_SYNC_PRODUCT_SHIPPING')
            && get_setting('add_shipping_to_price');

        if ($sync_default_shipping_cost) {
            /*
                This feature enables the synchronization of the shipping cost assigned to a product.
                It attempts to apply the cost of the default shipping method if it is available for the default shipping country.
                If the default shipping method is not available, it selects the cheapest shipping option.
            */

            $country_from = get_setting('aliship_shipfrom', 'CN');
            $country_to = get_setting('aliship_shipto');

            foreach ($result['products'] as $key => $product) {
                $product = $this->AliexpressModel->calculateProductPricesFromVariants($product);
                $result['products'][$key] = $this->updateProductShippingInfo(
                    $product, $country_from, $country_to, null, null
                );
            }
        }

        return $result;
    }

    /**
     * Load product and its shipping info from Aliexpress API
     * @param string $externalProductId
     * @param array $params
     * @return array
     * @throws ServiceException
     */
    public function loadProductWithShippingInfo(string $externalProductId, array $params = []): array
    {
        $result = $this->AliexpressModel->load_product($externalProductId, $params);

        if ($result['state'] !== 'error') {
            $country_from = get_setting('aliship_shipfrom', 'CN');
            $country_to = get_setting('aliship_shipto');
            $result['product'] = $this->updateProductShippingInfo(
                $result['product'], $country_from, $country_to, null, null
            );
        }

        return $result;
    }

    /**
     * Set product shipping info fields with given parameters
     * @param array $product
     * @param string $method
     * @param string|null $externalVariationId
     * @param string|null $country_to
     * @param string|null $country_from
     * @return array
     */
    public function setShippingInfo(
        array $product, string $method, ?string $externalVariationId = null,
        ?string $country_to = null, ?string $country_from = null
    ): array {
        $product_country_to = !empty($product[ImportedProductService::FIELD_COUNTRY_TO]) ?
            $product[ImportedProductService::FIELD_COUNTRY_TO] : '';

        $product_country_from = !empty($product[ImportedProductService::FIELD_COUNTRY_FROM]) ?
            $product[ImportedProductService::FIELD_COUNTRY_FROM] : '';

        $productVariationKey = !empty($product[ImportedProductService::FIELD_VARIATION_KEY]) ?
            $product[ImportedProductService::FIELD_VARIATION_KEY] : '';

        $country_to = $country_to ?? $product_country_to;
        $country_from = $country_from ?? $product_country_from;
        $externalVariationId = $externalVariationId ?? $productVariationKey;


        $country = ProductShippingData::meta_key($country_from, $country_to);

        $shouldSetShippingInfo = $country && $method;

        if ($shouldSetShippingInfo) {
            //todo: move this code to some factory
            $product[ImportedProductService::FIELD_METHOD] = $method;
            $product[ImportedProductService::FIELD_VARIATION_KEY] = $externalVariationId ?? '';
            $product[ImportedProductService::FIELD_COUNTRY_TO] =
                $this->AliexpressHelper->convertToAliexpressCountryCode($country_to);
            $product[ImportedProductService::FIELD_COUNTRY_FROM] =
                $this->AliexpressHelper->convertToAliexpressCountryCode($country_from);
            $product[ImportedProductService::FIELD_COST] = 0;

            $items = $product[ImportedProductService::FIELD_SHIPPING_INFO][$country] ?? [];
            foreach ($items as $shippingItem) {
                if ($shippingItem['serviceName'] === $product[ImportedProductService::FIELD_METHOD]) {
                    $shippingItemCost = $shippingItem['previewFreightAmount']['value'] ??
                        $shippingItem['freightAmount']['value'];
                    $product[ImportedProductService::FIELD_COST] = $shippingItemCost;
                    break;
                }
            }

            $product = $this->PriceFormulaService->applyFormula($product);
        }

        return $product;
    }

    /**
     * Check if product shipping info fields not available for given parameters
     * load data from Aliexpress API
     * @param array $product
     * @param string $country_from
     * @param string $country_to
     * @param string|null $externalSkuId
     * @param string|null $extraData
     * @return array
     * @throws ServiceException
     */
    public function updateProductShippingInfo(
        array $product, string $country_from, string $country_to,
        ?string $externalSkuId = null, ?string $extraData = null
    ): array {
        if (!isset($product[ImportedProductService::FIELD_SHIPPING_INFO])) {
            $product[ImportedProductService::FIELD_SHIPPING_INFO] = [];
        }

        //todo: perhaps we can always get $extraData from product?
        $extraData = $extraData ?? $this->getExtraDataFromProduct($product, $externalSkuId);
        $country_from = !empty($country_from) ? $country_from : 'CN';

        $country_from = $this->AliexpressHelper->convertToAliexpressCountryCode($country_from);
        $country_to = $this->AliexpressHelper->convertToAliexpressCountryCode($country_to);

        $shipping_from_country_list = [];
        if (isset($product['sku_products'])) {
            foreach ($product['sku_products']['variations'] as $var) {
                if (!empty($var[ImportedProductService::FIELD_COUNTRY_CODE])) {
                    $shipping_from_country_list[$var[ImportedProductService::FIELD_COUNTRY_CODE]] =
                        $var[ImportedProductService::FIELD_COUNTRY_CODE];
                }
               /* if ($var['id'] === $variationExternalId) {
                    $externalRealSkuId = !empty($var['skuId']) ? $var['skuId'] : null;
                }*/
            }
        }

        // TODO experimental
        if (empty($shipping_from_country_list) && isset($product['local_seller_tag']) && strlen($product['local_seller_tag']) == 2) {
            $shipping_from_country_list[$product['local_seller_tag']] = $product['local_seller_tag'];
        }

        $shipping_from_country_list = array_values($shipping_from_country_list);
        $product[ImportedProductService::FIELD_COUNTRY_FROM_LIST] = $shipping_from_country_list;

        if (count($shipping_from_country_list) > 0 && !in_array($country_from, $shipping_from_country_list)) {
            $country_from = $shipping_from_country_list[0];
        }

        $product[ImportedProductService::FIELD_COUNTRY_FROM] = $country_from;

        if ($country_to) {
            $product[ImportedProductService::FIELD_COUNTRY_TO] = $country_to;
        }

        $country = ProductShippingData::meta_key($country_from, $country_to);

        if (Account::getInstance()->get_purchase_code()) {
            if (empty($product[ImportedProductService::FIELD_SHIPPING_INFO][$country])) {
                $externalProductId = $product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID];
                $shippingItems = $this->AliexpressModel
                    ->loadShippingItems(
                        $externalProductId, 1, $country_to, $country_from,
                        $externalSkuId, $extraData
                    );
                $product[ImportedProductService::FIELD_SHIPPING_INFO][$country] = $shippingItems;
            } else {
                a2wl_info_log(sprintf('Shipping data is loaded from cache. WC Product ID: %d, Country code: %s',
                    $product['post_id'], $country
                ));
            }
        }

        $items = $product[ImportedProductService::FIELD_SHIPPING_INFO][$country] ?? [];

        $ShippingItemDto = $this->findDefaultFromShippingItems($items, $product);

        $product[ImportedProductService::FIELD_METHOD] = $ShippingItemDto->getMethodName();
        $product[ImportedProductService::FIELD_COST] = $ShippingItemDto->getCost();

        return $product;
    }

    /**
     * @param array $product
     * @param null|string $externalSkuId
     * @return string
     */
    public function getShippingFromByExternalSkuId(array $product, ?string $externalSkuId = null): string
    {
        if (!$externalSkuId) {
            return 'CN';
        }

        foreach ($product['sku_products']['variations'] as $variation) {
            if ($variation['skuId'] === $externalSkuId) {
                return $variation[ImportedProductService::FIELD_COUNTRY_CODE] ?? 'CN';
            }
        }

        return 'CN';
    }

    /**
     * Fill shipping info fields with cache data
     * @param array $product
     * @return array
     * @throws RepositoryException
     * @throws ServiceException
     */
    public function fillProductShippingInfo(array $product): array
    {
        $wcProductId = $product['post_id'] ?? '';

        if (!is_numeric(trim($wcProductId))) {
            $errorText = __('Invalid WooCommerce product ID provided.', 'ali2woo');
            throw new ServiceException($errorText);
        }

        $ProductShippingData = $this->ProductShippingDataRepository->get($wcProductId);

        $shippingInfo = $ProductShippingData->getShippingByQuantity(1);
        $product[ImportedProductService::FIELD_SHIPPING_INFO] = $shippingInfo;
        $product[ImportedProductService::FIELD_METHOD] = $ProductShippingData->getMethod();
        $product[ImportedProductService::FIELD_COUNTRY_TO] = $ProductShippingData->getCountryTo();
        $product[ImportedProductService::FIELD_COUNTRY_FROM] = $ProductShippingData->getCountryFrom();
        $product[ImportedProductService::FIELD_COST] = $ProductShippingData->getCost();
        $product[ImportedProductService::FIELD_VARIATION_KEY] = $ProductShippingData->getVariationKey();

        return $product;
    }

    public function findDefaultFromShippingItems(array $shippingItems, array $importedProduct): ShippingItemDto
    {
        $default_ff_method = get_setting('fulfillment_prefship');

        $default_method = !empty($importedProduct[ImportedProductService::FIELD_METHOD]) ?
            $importedProduct[ImportedProductService::FIELD_METHOD] :
            $default_ff_method;

        $has_shipping_method = false;
        foreach ($shippingItems as $shippingItem) {
            if ($shippingItem['serviceName'] === $default_method) {
                $has_shipping_method = true;
                break;
            }
        }

        $current_currency = apply_filters('wcml_price_currency', NULL);
        if (!$has_shipping_method) {
            $default_method = "";
            $tmp_p = -1;
            foreach ($shippingItems as $k => $shippingItem) {
                $price = $shippingItem['previewFreightAmount']['value'] ?? $shippingItem['freightAmount']['value'];
                $price = apply_filters('wcml_raw_price_amount', $price, $current_currency);
                if ($tmp_p < 0 || $price < $tmp_p || $shippingItem['serviceName'] == $default_ff_method) {
                    $tmp_p = $price;
                    $default_method = $shippingItem['serviceName'];
                    if ($default_method == $default_ff_method) {
                        break;
                    }
                }
            }
        }

        /**
         * todo: need to use hasTracking and companyName too in code which calls findDefaultFromShippingItems
         */
        $shipping_cost = 0;
        $shippingTime = '';
        $hasTracking = false;
        $companyName = '';
        foreach ($shippingItems as $shippingItem) {
            if ($shippingItem['serviceName'] == $default_method) {
                $shippingTime = $shippingItem['time'] ?? '';
                $hasTracking = isset($shippingItem['tracking']) && $shippingItem['tracking'];
                $companyName =  $shippingItem['company'] ?? $shippingItem['serviceName'];
                $shipping_cost = $shippingItem['previewFreightAmount']['value'] ?? $shippingItem['freightAmount']['value'];
                $shipping_cost = apply_filters('wcml_raw_price_amount', $shipping_cost, $current_currency);
            }
        }

        return new ShippingItemDto(
            $default_method, $shipping_cost, $companyName, $shippingTime, $hasTracking
        );
    }

    public function getShippingItems(
        array $product, string $countryToCode, string $countryFromCode = 'CN'
    ): array {
        $countryCodeKey = ProductShippingData::meta_key($countryFromCode, $countryToCode);

        return $product[ImportedProductService::FIELD_SHIPPING_INFO][$countryCodeKey] ?? [];
    }

    private function generateComplexProductId(array $product): string
    {
        $complex_id = $product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID] . ';' . $product['import_lang'];

        try {
            $ProductShippingData = $this->ProductShippingDataRepository->get($product['post_id']);

            $countryTo = $ProductShippingData->getCountryTo();
            $method = $ProductShippingData->getMethod();

            if (!is_null($countryTo)) {
                $complex_id .= ';' . $countryTo;
            }

            if (!is_null($method)) {
                $complex_id .= ';' . $method;
            }
        } catch (RepositoryException $RepositoryException) {
            a2wl_error_log($RepositoryException->getMessage());
        }

        return $complex_id;
    }

    private function getExtraDataFromProduct(array $product, ?string $externalSkuId = null): ?string
    {
        $extraData = $product[ImportedProductService::FIELD_EXTRA_DATA] ?? null;

        if (!$externalSkuId) {
            return $extraData;
        }

        if (!empty($product['sku_products'])) {
            foreach ($product['sku_products']['variations'] as $variation) {
                if ($variation[ImportedProductService::FIELD_EXTERNAL_SKU_ID] === $externalSkuId) {
                    return $variation[ImportedProductService::FIELD_EXTRA_DATA] ?? null;
                }
            }
        }

        return null;
    }
}
