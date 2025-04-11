<?php

/**
 * Description of ApplyPricingRulesProcess
 *
 * @author Ali2Woo Team
 *
 */

namespace AliNext_Lite;;

class SynchronizePurchaseCodeInfoProcess extends BaseJob implements SynchronizePurchaseCodeInfoInterface
{
    public const ACTION_CODE = 'a2wl_synchronize_purchase_code_info_process';

    protected $action = self::ACTION_CODE;
    protected string $title = 'Synchronize Purchase Code Info Process';

    protected PurchaseCodeInfoService $PurchaseCodeInfoService;

    public function __construct(PurchaseCodeInfoService $PurchaseCodeInfoService) {
        parent::__construct();

        $this->PurchaseCodeInfoService = $PurchaseCodeInfoService;
    }

    public function pushToQueue(): SynchronizePurchaseCodeInfoInterface
    {
        $this->push_to_queue([]);
        $this->save();
        $size = $this->getSize();
        a2wl_info_log(sprintf(
            "Add new job: %s [size: %d]",
            $this->getTitle(), $size
        ));

        return $this;
    }

    protected function task($item): bool
    {
        a2wl_info_log(sprintf(
            "Start job: %s",
            $this->getTitle()
        ));

        if (!Account::getInstance()->get_purchase_code()) {
            a2wl_info_log(sprintf(
                "stop job: %s, no purchase code",
                $this->getTitle()
            ));

            return false;
        }

        try {
            $this->PurchaseCodeInfoService->getFromApi();
        } catch (PlatformException $PlatformException) {
            a2wl_info_log(sprintf(
                "stop job: %s, error",
                $this->getTitle()
            ));
            a2wl_error_log($PlatformException->getMessage());

            return false;
        }

        a2wl_info_log(sprintf(
            "Finish job: %s",
            $this->getTitle()
        ));

        return false;
    }
}
