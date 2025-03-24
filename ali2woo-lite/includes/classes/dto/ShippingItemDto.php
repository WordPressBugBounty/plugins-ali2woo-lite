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
        private string $methodName,
        private float $cost,
        private string $companyName,
        private string $days, /* example 20 or 15-30 */
        private bool $hasTracking,
    ) {}

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getCost(): float
    {
        return $this->cost;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getDays(): string
    {
        return $this->days;
    }

    public function hasTracking(): bool
    {
        return $this->hasTracking;
    }
}
