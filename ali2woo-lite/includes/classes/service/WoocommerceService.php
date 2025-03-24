<?php
namespace AliNext_Lite;;

use WC_Order;
use WC_Product;

/**
 * Description of WoocommerceService
 *
 * @author Ali2Woo Team
 */

class WoocommerceService
{
    protected Woocommerce $WoocommerceModel;
    protected ImportedProductServiceFactory $ImportedProductServiceFactory;

    protected ProductShippingDataRepository $ProductShippingDataRepository;
    protected Aliexpress $AliexpressLoader;
    protected ProductService $ProductService;
    protected ProductShippingDataService $ProductShippingDataService;

    public function __construct(
        Woocommerce $WoocommerceModel,
        ImportedProductServiceFactory $ImportedProductServiceFactory,
        ProductShippingDataRepository $ProductShippingDataRepository,
        Aliexpress $AliexpressLoader, ProductService $ProductService,
        ProductShippingDataService $ProductShippingDataService
    ) {
        $this->WoocommerceModel = $WoocommerceModel;
        $this->ImportedProductServiceFactory = $ImportedProductServiceFactory;
        $this->ProductShippingDataRepository = $ProductShippingDataRepository;
        $this->AliexpressLoader = $AliexpressLoader;
        $this->ProductService = $ProductService;
        $this->ProductShippingDataService = $ProductShippingDataService;
    }

    /**
     * @throws RepositoryException|ServiceException
     */
    public function getProductShippingInfo(
        WC_Product $WC_ProductOrVariation, ?string $countryToCode, int $quantity = 1,
    ): array {
        $ImportedProductService = $this->ImportedProductServiceFactory
            ->createFromProduct($WC_ProductOrVariation);

        $wcProductId = $ImportedProductService->getParentId();
        $importedProduct = $this->getProductWithVariations($wcProductId);

        $account = Account::getInstance();
        if ($account->get_purchase_code()) {
            //shipping data is available only if purchase code is added
            return $this->ProductService->updateProductShippingInfo(
                $importedProduct,
                $ImportedProductService->getShippingFromCountryCode(),
                $countryToCode,
                $ImportedProductService->getExternalSkuId(),
                $ImportedProductService->getExtraData()
            );
        } else {
            return $importedProduct;
        }
    }

    /**
     * @throws RepositoryException|ServiceException
     */
    public function updateProductShippingInfo(
        WC_Product $WC_ProductOrVariation, ?string $countryToCode, int $quantity = 1,
    ): array {
        $importedProduct = $this->getProductShippingInfo(
            $WC_ProductOrVariation, $countryToCode, $quantity
        );

        $wcProductId = $importedProduct['post_id'];

        try {
           $this->ProductShippingDataService->updateFromProduct($wcProductId, $importedProduct);
        } catch (RepositoryException $RepositoryException) {
            a2wl_error_log('Can`t update product shipping info cache' . $RepositoryException->getMessage());
        }

        return $importedProduct;
    }

    public function getShippingFromByProduct(WC_Product $WC_ProductOrVariation): string
    {
        $ImportedProductService = $this->ImportedProductServiceFactory
            ->createFromProduct($WC_ProductOrVariation);

        return $ImportedProductService->getShippingFromCountryCode();
    }

    /**
     * @throws RepositoryException|ServiceException
     */
    public function updateProductShippingItems(
        WC_Product $WC_ProductOrVariation,
        ?string $countryToCode, string $countryFromCode = 'CN', int $quantity = 1,
    ): array {
        $importedProduct = $this->getProductShippingInfo(
            $WC_ProductOrVariation, $countryToCode, $quantity
        );

        $wcProductId = $importedProduct['post_id'];
        $countryCode = ProductShippingData::meta_key($countryFromCode, $countryToCode);

        if (empty($importedProduct[ImportedProductService::FIELD_SHIPPING_INFO][$countryCode])) {
            return $importedProduct;
        }

        try {
            $this->ProductShippingDataService->saveItems(
                $wcProductId, $countryFromCode, $countryToCode,
                $importedProduct[ImportedProductService::FIELD_SHIPPING_INFO][$countryCode]
            );
        } catch (RepositoryException $RepositoryException) {
            a2wl_error_log('Can`t update product shipping items cache' . $RepositoryException->getMessage());
        }

        return $importedProduct;
    }

    /**
     * @throws ServiceException
     */
    public function syncOrderWithAliexpress(WC_Order $WC_Order): void
    {
        $orderId = $WC_Order->get_id();
        $external_order_ids = array();
        $order_items = $WC_Order->get_items();
        foreach ($order_items as $item) {
            $a2wl_order_item = new WooCommerceOrderItem($item);
            $external_order_id = $a2wl_order_item->get_external_order_id();
            if (!empty($external_order_id)) {
                $external_order_ids[] = $external_order_id;
            }
        }

        foreach ($external_order_ids as $external_order_id) {
            $apiResult =  $this->AliexpressLoader->load_order($external_order_id);
            $isNotAvailableOrder = $apiResult['state'] === 'error' &&
                isset($apiResult['error_code']) && $apiResult['error_code'] === 404;
            if ($isNotAvailableOrder) {
                // remove external order id (decided to not make this, because it can erase data if token aliexpress account changed)
                // $this->delete_external_order_id($order_id, $external_order_id);
            } else if ($apiResult['state'] === 'ok') {
                $this->WoocommerceModel->save_tracking_code(
                    $orderId,
                    $external_order_id,
                    $apiResult['order']['tracking_codes'],
                    $apiResult['order']['courier_name'],
                    '',
                    $apiResult['order']['tracking_status']
                );
            } else {
                $errorMessage = esc_html_x(
                    'Unhandled error during order sync',
                    'error text',
                    'ali2woo'
                );

                throw new ServiceException($apiResult['message'] ?? $errorMessage);
            }
        }
    }

    /**
     * @throws RepositoryException|ServiceException
     */
    public function getProduct(int $wcProductId): array
    {
        $importedProduct = $this->WoocommerceModel->get_product_by_post_id($wcProductId, false);

        return $this->ProductService->fillProductShippingInfo($importedProduct);
    }

    /**
     * @throws RepositoryException|ServiceException
     */
    public function getProductWithVariations(int $wcProductId): array
    {
        $importedProduct = $this->WoocommerceModel->get_product_by_post_id($wcProductId, true);

        return $this->ProductService->fillProductShippingInfo($importedProduct);
    }

}
