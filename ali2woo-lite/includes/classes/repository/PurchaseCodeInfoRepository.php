<?php

/**
 * Description of PurchaseCodeInfoRepository
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class PurchaseCodeInfoRepository
{
    const OPTION_PURCHASE_CODE_INFO = 'a2wl_purchase_code_info';
    protected PurchaseCodeInfoFactory $PurchaseCodeInfoFactory;

    public function __construct(PurchaseCodeInfoFactory $PurchaseCodeInfoFactory)
    {
        $this->PurchaseCodeInfoFactory = $PurchaseCodeInfoFactory;
    }

    public function get(): PurchaseCodeInfo
    {
        $meta = $this->getAsArray();

        return $this->PurchaseCodeInfoFactory->buildFromData($meta);
    }

    public function save(PurchaseCodeInfo $PurchaseCodeInfo): void
    {
        update_option(self::OPTION_PURCHASE_CODE_INFO, $PurchaseCodeInfo->toArray(), 'no');
    }

    private function getAsArray(): array
    {
        return get_option(self::OPTION_PURCHASE_CODE_INFO, []);
    }
}
