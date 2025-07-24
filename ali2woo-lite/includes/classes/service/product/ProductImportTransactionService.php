<?php

/**
 * Description of ProductImportTransactionService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Exception;

class ProductImportTransactionService
{
    public function __construct(
        protected ProductSelectorService $ProductSelectorService,
        protected ProductValidatorService $ProductValidatorService,
        protected Aliexpress $AliexpressModel,
        protected PriceFormulaService $PriceFormulaService,
        protected ProductImport $ProductImportModel,
    ) {}

    /**
     * Import flow: validate, enrich, apply pricing, and import.
     *
     * @param string $externalProductId
     * @param string $priority
     * @param array $apdParams
     * @return ProductImportResultDTO
     * @throws Exception
     */
    public function execute(
        string $externalProductId, string $priority = ProductSelectorService::PRIORITY_RESULT_FIRST, array $apdParams = []
    ): ProductImportResultDTO {
        $Product = $this->ProductSelectorService->getByExternalId($externalProductId, $priority);

        return $this->finalizeImport($Product, $externalProductId, $apdParams);
    }


    /**
     * Import flow: validate, enrich, apply pricing, import — using externally provided product data.
     * @param array $Product
     * @param array $apdParams
     * @return ProductImportResultDTO
     * @throws Exception
     */
    public function executeWithProductData(array $Product, array $apdParams = []): ProductImportResultDTO
    {
        $externalProductId = $Product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID];

        if (!$externalProductId) {
            return new ProductImportResultDTO(
                'error', "Missing external product ID."
            );
        }

        return $this->finalizeImport($Product, $externalProductId, $apdParams);
    }

    /**
     * @throws Exception
     */
    protected function finalizeImport(
        array $Product, string $externalProductId, array $apdParams = []
    ): ProductImportResultDTO {
        if (!$this->ProductValidatorService->isAllowedToImport($externalProductId)) {
            return new ProductImportResultDTO('error', "Product already imported.");
        }

        $response = $this->AliexpressModel->load_product($externalProductId, $apdParams);
        if ($response['state'] === 'error') {
            return new ProductImportResultDTO(
                'error', $response['message'] ?? 'Unknown error during enrichment.'
            );
        }

        $Product = array_replace_recursive($Product, $response['product'] ?? []);
        if (!$this->ProductValidatorService->isValidStructure($Product)) {
            return new ProductImportResultDTO(
                'error', "Invalid product structure after enrichment."
            );
        }

        //todo: this feature is not available temporarily
        $Product['is_affiliate'] = true;

        $Product = $this->PriceFormulaService->applyFormula($Product);
        $this->ProductImportModel->add_product($Product);

        a2wl_info_log(sprintf(
            "Product imported: %s by admin ID: %d at %s",
            $externalProductId,
            get_current_user_id(),
            current_time('mysql')
        ));

        do_action('a2wl_after_product_import', $Product);

        return new ProductImportResultDTO('ok', null, $Product);
    }
}
