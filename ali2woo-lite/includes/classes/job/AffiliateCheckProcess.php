<?php
namespace AliNext_Lite;;

use Exception;

class AffiliateCheckProcess extends BaseJob
{
    public const ACTION_CODE = 'a2wl_affiliate_check_job';

    protected $action = self::ACTION_CODE;
    protected string $title = 'Affiliate Detection (Import)';

    public function __construct(
        protected Aliexpress $Aliexpress,
        protected ProductImport $ProductImportModel
    ) {
        parent::__construct();
    }

    /**
     * @param array $productIds
     * @return self
     */
    public function pushToQueue(array $productIds): self
    {

        if (empty($productIds)) {
            a2wl_info_log("[AffiliateCheckJob] No product IDs provided.");
            return $this;
        }

        foreach ($productIds as $productId) {
            $Product = $this->ProductImportModel->get_product($productId);

            if (!empty($Product['is_affiliate'])) {
                continue;
            }

            $externalId = $Product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID] ?? null;
            if (!$externalId) {
                continue;
            }

            $this->push_to_queue([
                'externalId' => $externalId
            ]);
        }

        $this->save();
        a2wl_info_log("AffiliateCheckJob queued for " . count($productIds) . " products.");

        return $this;
    }

    protected function task($item): bool
    {
        $externalId = $item['externalId'] ?? null;

        if (!$externalId) {
            a2wl_info_log("[AffiliateCheckJob] Missing externalId. Skipping.");
            return false;
        }

        try {
            $result = $this->Aliexpress->check_affiliate($externalId);
            $Product = $this->ProductImportModel->get_product($externalId);
            $Product['is_affiliate'] = $result['affiliate'] ?? false;
            $this->ProductImportModel->save_product($externalId, $Product);

        } catch (Exception $e) {
            a2wl_info_log("[AffiliateCheckJob] Failed for {$externalId}: " . $e->getMessage());
        }

        return false;
    }
}
