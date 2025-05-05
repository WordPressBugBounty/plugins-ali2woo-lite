<?php

/**
 * Description of AddProductToImportListProcess
 *
 * @author Ali2Woo Team
 *
 */

namespace AliNext_Lite;;

use Throwable;

class AddProductToImportListProcess extends BaseJob implements AddProductToImportListInterface
{

    public const ACTION_CODE = 'a2wl_add_product_to_import_list_process';

    public const PARAM_EXTERNAL_PRODUCT_ID = 'externalProductId';

    protected $action = self::ACTION_CODE;
    protected string $title = 'Add Product To Import List';

    protected Aliexpress $AliexpressModel;
    protected PriceFormulaService $PriceFormulaService;
    protected ProductImport $ProductImportModel;

    public function __construct(
        Aliexpress $AliexpressModel, PriceFormulaService $PriceFormulaService, ProductImport $ProductImportModel
    ) {
        parent::__construct();

        $this->AliexpressModel = $AliexpressModel;
        $this->PriceFormulaService = $PriceFormulaService;
        $this->ProductImportModel = $ProductImportModel;
    }

    public function pushToQueue(string $externalProductId): AddProductToImportListInterface
    {
        $this->push_to_queue([
            self::PARAM_EXTERNAL_PRODUCT_ID => $externalProductId,
        ]);
        $this->save();
        $size = $this->getSize();
        a2wl_info_log(sprintf(
            "Add new job: %s [external product id: %s; queue size: %d;]",
            $this->getTitle(), $externalProductId, $size
        ));

        return $this;
    }

    protected function task($item)
    {

        a2wl_init_error_handler();
        try {
            $timeStart = microtime(true);

            $externalProductId = $item[self::PARAM_EXTERNAL_PRODUCT_ID];
            $product = $this->getProductFromImportList($externalProductId);

            global $wpdb;
            $productId = $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $wpdb->prepare(
                    "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_a2w_external_id' AND meta_value=%s LIMIT 1",
                    $externalProductId
                )
            );

            if (get_setting('allow_product_duplication') || !$productId) {
                $res = $this->AliexpressModel->load_product($externalProductId);
                if ($res['state'] !== 'error') {
                    $product = array_replace_recursive($product, $res['product']);

                    if ($product) {
                        $product = $this->PriceFormulaService->applyFormula($product);
                        $this->ProductImportModel->add_product($product);
                    } else {

                        a2wl_info_log(sprintf(
                            "Process job: %s, Skip product external id: %s (because cant match product fields);]",
                            $this->getTitle(), $externalProductId,
                        ));
                    }
                } else {
                    a2wl_info_log(sprintf(
                        "Process job: %s, Skip product external id: %s (because of aliexpress import error %s;]",
                        $this->getTitle(), $externalProductId, $res['message'] ?? ''
                    ));
                }
            }

            $size = $this->getSize();
            $time = microtime(true)-$timeStart;
            a2wl_info_log(sprintf(
                "Done job: %s [time: %s, queue size: %d, external product id: %s;]",
                $this->getTitle(), $time, $size, $externalProductId
            ));
        }
        catch (Throwable $Exception) {
            a2wl_print_throwable($Exception);
        }

        return false;
    }

    private function getProductFromImportList(string $externalProductId): array
    {
        $products = a2wl_get_transient('a2wl_search_result');

        if ($products && is_array($products)) {
            foreach ($products as $product) {
                if ($product['id'] == $externalProductId) {
                    return $product;
                }
            }
        }

        return [];
    }
}
