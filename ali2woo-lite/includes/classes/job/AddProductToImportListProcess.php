<?php

/**
 * Description of AddProductToImportListProcess it's used for CSV loader for now
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

    public function __construct(
        protected ProductImportTransactionService $ProductImportTransactionService,
    ) {
        parent::__construct();
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

    protected function task($item): bool
    {
        a2wl_init_error_handler();
        try {
            $timeStart = microtime(true);

            $externalProductId = $item[self::PARAM_EXTERNAL_PRODUCT_ID];

            $result = $this->ProductImportTransactionService->execute(
                $externalProductId,
                ProductSelectorService::PRIORITY_RESULT_FIRST
            );

            if ($result->status === 'error') {
                a2wl_info_log(sprintf(
                    "Failed job: %s [external ID: %s; reason: %s]",
                    $this->getTitle(),
                    $externalProductId,
                    $result->message
                ));
            }

            $size = $this->getSize();
            $time = microtime(true) - $timeStart;

            a2wl_info_log(sprintf(
                "Finished job: %s [time: %0.3fs, queue size: %d, external product id: %s;]",
                $this->getTitle(), $time, $size, $externalProductId
            ));
        }
        catch (Throwable $Exception) {
            a2wl_print_throwable($Exception);
        }

        return false;
    }
}
