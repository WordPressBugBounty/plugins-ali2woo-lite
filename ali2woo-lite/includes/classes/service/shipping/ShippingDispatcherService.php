<?php

/**
 * Description of ShippingDispatcherService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Exception;

class ShippingDispatcherService
{

    public function __construct(
        protected BackgroundProcessFactory $BackgroundProcessFactory
    ) {}

    /**
     * Dispatch shipping logic for imported product.
     *
     * @param array $product
     * @throws Exception
     */
    public function dispatchForImportedProduct(array $product): void
    {
        $externalId = $product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID] ?? null;

        if (!$externalId) {
            a2wl_info_log("[ShippingDispatcher] No external ID found in product - shipping skipped.");
            return;
        }

        $countryToCode = get_setting('aliship_shipto', 'US');

        /** @var ApplyShippingMethodBulkProcess $shippingJob */
        $shippingJob = $this->BackgroundProcessFactory->createProcessByCode(
            ApplyShippingMethodBulkProcess::ACTION_CODE
        );

        $shippingJob->pushToQueue(
            [$externalId],
            $countryToCode,
            false,
            ApplyShippingMethodBulkProcess::COUNTRY_FROM_CODE_CHINA,
            ApplyShippingMethodBulkProcess::SCOPE_SELECTED_IMPORT
        )->dispatch();
    }
}
