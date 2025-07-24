<?php
/**
 * Description of ProductImportHook
 *
 * @author Ali2Woo Team
 *
 * @hook: a2wl_after_product_import
 * @ajax: true
 *
 */

namespace AliNext_Lite;;

use Exception;

class AfterProductImportHook
{

    public function __construct(
        protected BackgroundProcessFactory $BackgroundProcessFactory,
        
    ) {}

    public function __invoke(array $product): void
    {
        
        $this->detectAffiliate($product);
    }

    

    /**
     * todo: this feature is not available temporarily
     * @param array $Product
     * @return void
     */
    private function detectAffiliate(array $Product): void
    {
        if (!isset($Product['is_affiliate'])) {
            try {
                /**
                 * @var AffiliateCheckProcess $AffiliateCheckProcess
                 */
                $AffiliateCheckProcess = $this->BackgroundProcessFactory->createProcessByCode(
                    AffiliateCheckProcess::ACTION_CODE
                );

                $productId = $Product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID] ?? null;
                if ($productId) {
                    $AffiliateCheckProcess->pushToQueue([$productId])->dispatch();
                }
            } catch (Exception $Exception) {
                a2wl_info_log("[ProductImportHook] Dispatch failed: " . $Exception->getMessage());
            }
        }
    }
}
