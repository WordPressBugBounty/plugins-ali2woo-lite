<?php

/**
 * Description of AddProductToImportListInterface
 *
 * @author Ali2Woo Team
 *
 * @position: 2
 */

namespace AliNext_Lite;;

interface AddProductToImportListInterface extends BaseJobInterface
{
    public function pushToQueue(string $externalProductId): self;
}