<?php

/**
 * Description of ShippingItemDto
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ShippingItemDto
{
    public function __construct(
        private string $methodName, private float $cost
    ) {}

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getCost(): float
    {
        return $this->cost;
    }
}
