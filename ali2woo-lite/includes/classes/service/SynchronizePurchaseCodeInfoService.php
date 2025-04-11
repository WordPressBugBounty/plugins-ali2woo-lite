<?php

/**
 * Description of PurchaseCodeInfoService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class SynchronizePurchaseCodeInfoService
{
    protected SynchronizePurchaseCodeInfoProcess $SynchronizePurchaseCodeInfoProcess;

    public function __construct(
        SynchronizePurchaseCodeInfoProcess $SynchronizePurchaseCodeInfoProcess
    ) {
        $this->SynchronizePurchaseCodeInfoProcess = $SynchronizePurchaseCodeInfoProcess;
    }

    public function runSyncPurchaseCodeInfoProcess(): void
    {
        if (!Account::getInstance()->get_purchase_code()) {
            return;
        }

        if (!$this->SynchronizePurchaseCodeInfoProcess->isQueued()) {
            //schedule new job, only if previous is finished
            $this->SynchronizePurchaseCodeInfoProcess->pushToQueue()->save()->dispatch();
        }
    }

}