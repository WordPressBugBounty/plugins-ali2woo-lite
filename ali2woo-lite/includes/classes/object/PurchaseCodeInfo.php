<?php

/**
 * Description of PurchaseCodeInfo
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class PurchaseCodeInfo
{

    public const TARIFF_CODE_FREE = 'free';


    public const FIELD_MESSAGE = 'message';
    public const FIELD_VALID_FROM = 'valid_from';
    public const FIELD_VALID_TO = 'valid_to';
    public const FIELD_TARIFF_CODE = 'tariff_code';
    public const FIELD_TARIFF_FROM = 'tariff_from';
    public const FIELD_TARIFF_TO = 'tariff_to';
    public const FIELD_LIMITS = 'limits';
    public const FIELD_COUNT = 'count';


    private ?string $message = null;
    private ?string $validFrom = null;
    private ?string $validTo = null;
    private ?string $tariffCode = null;
    private ?string $tariffFrom = null;
    private ?string $tariffTo = null;
    private ?PurchaseCodeInfoLimits $limits = null;
    private ?PurchaseCodeInfoCount $count = null;


    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getValidFrom(): ?string
    {
        return $this->validFrom;
    }

    public function setValidFrom(?string $validFrom): self
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidTo(): ?string
    {
        return $this->validTo;
    }

    public function setValidTo(?string $validTo): self
    {
        $this->validTo = $validTo;

        return $this;
    }

    public function getValidToStamp(): ?string
    {
        if ($this->validTo) {
            return strtotime($this->validTo);
        }

        return null;
    }

    public function getTariffCode(): ?string
    {
        return $this->tariffCode;
    }

    public function isTariffCodeFree(): bool
    {
        return ($this->tariffCode === self::TARIFF_CODE_FREE || !$this->tariffCode);
    }

    public function setTariffCode(?string $tariffCode): self
    {
        $this->tariffCode = $tariffCode;

        return $this;
    }

    public function getTariffFrom(): ?string
    {
        return $this->tariffFrom;
    }

    public function setTariffFrom(?string $tariffFrom): self
    {
        $this->tariffFrom = $tariffFrom;

        return $this;
    }

    public function getTariffTo(): ?string
    {
        return $this->tariffTo;
    }

    public function setTariffTo(?string $tariffTo): self
    {
        $this->tariffTo = $tariffTo;

        return $this;
    }

    public function getTariffToStamp(): ?string
    {
        if ($this->tariffTo) {
            return strtotime($this->tariffTo);
        }

        return null;
    }

    public function getSupportedUntilStamp(): ?string
    {
        $valid_to = $this->getValidToStamp();
        $tariff_to = $this->getTariffToStamp();

        return ($valid_to && $tariff_to && $tariff_to > $valid_to) ? $tariff_to : $valid_to;
    }

    public function getLimits(): ?PurchaseCodeInfoLimits
    {
        return $this->limits;
    }

    public function setLimits(?PurchaseCodeInfoLimits $limits): self
    {
        $this->limits = $limits;

        return $this;
    }

    public function getCount(): ?PurchaseCodeInfoCount
    {
        return $this->count;
    }

    public function setCount(?PurchaseCodeInfoCount $count): self
    {
        $this->count = $count;

        return $this;
    }

    public function getPackageUsageInPercent(): ?float
    {
        if (!$this->getLimits()) {
            return null;
        }

        if (!$this->getCount()) {
            return null;
        }

        $limitSum = $this->getLimits()->getAll();
        $countSum = $this->getCount()->getSum();

        return $countSum * 100 / $limitSum;
    }

    public function toArray(): array
    {
        return [
            self::FIELD_MESSAGE => $this->getMessage(),
            self::FIELD_VALID_FROM => $this->getValidFrom(),
            self::FIELD_VALID_TO => $this->getValidTo(),
            self::FIELD_TARIFF_CODE => $this->getTariffCode(),
            self::FIELD_TARIFF_FROM => $this->getTariffFrom(),
            self::FIELD_TARIFF_TO => $this->getTariffTo(),
            self::FIELD_LIMITS => $this->getLimits()?->toArray(),
            self::FIELD_COUNT => $this->getCount()?->toArray(),
        ];
    }

}
