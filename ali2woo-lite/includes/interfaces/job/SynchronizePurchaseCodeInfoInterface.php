<?php

/**
 * Description of SynchronizePurchaseCodeInfoInterface
 *
 * @author Ali2Woo Team
 *
 * @position: 2
 */

namespace AliNext_Lite;;

interface SynchronizePurchaseCodeInfoInterface extends BaseJobInterface
{
    public function pushToQueue(): self;
}
