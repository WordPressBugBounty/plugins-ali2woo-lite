<?php

/**
 * Description of ProductShippingData
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ProductShippingData
{

    public const FIELD_SHIPPING_INFO = 'shipping_info';
    public const FIELD_COUNTRY_TO = 'country_to';
    public const FIELD_COUNTRY_FROM = 'country_from';
    public const FIELD_METHOD = 'method';
    public const FIELD_COST = 'cost';
    public const FIELD_VARIATION_KEY = 'variation_key';

    private array $shippingInfo = [];
    private ?string $countryFrom = null;
    private ?string $countryTo = null;
    private ?string $method = null;
    private ?string $cost = null;
    private ?string $variationKey = null;


    public function getShippingInfo(): array
    {
        return $this->shippingInfo;
    }

    public function setShippingInfo(array $shippingInfo): self
    {
        $this->shippingInfo = $shippingInfo;

        return $this;
    }

    public function getCountryFrom(): ?string
    {
        return $this->countryFrom;
    }

    public function setCountryFrom(?string $countryFrom): self
    {
        $this->countryFrom = $countryFrom;

        return $this;
    }

    public function getCountryTo(): ?string
    {
        return $this->countryTo;
    }

    public function setCountryTo(?string $countryTo): self
    {
        $this->countryTo = $countryTo;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function getVariationKey(): ?string
    {
        return $this->variationKey;
    }

    public function setVariationKey(?string $variationKey): self
    {
        $this->variationKey = $variationKey;

        return $this;
    }

    public function getShippingByQuantity(int $quantity): array
    {
        if (empty($this->shippingInfo)) {
            return [];
        }

        $shippingInfoByQuantity = [];

        foreach ($this->shippingInfo as $countryCode => $shippingInfo) {
            if (!empty($this->shippingInfo[$countryCode][$quantity])) {
                $shippingInfoByQuantity[$countryCode] = $this->shippingInfo[$countryCode][$quantity];
            }
        }

        return $shippingInfoByQuantity;
    }

    public function getItems(int $quantity, string $fromCountry, string $toCountry): ?array
    {
        //for now we save and get shipping for quantity 1 only.
        $quantity = 1;
        $countryCode = self::meta_key($fromCountry, $toCountry);

        if (isset($this->shippingInfo[$countryCode][$quantity])) {
            return $this->shippingInfo[$countryCode][$quantity];
        }

        return null;
    }

    public function setItems(int $quantity, ?string $fromCountry, string $toCountry, array $items): self
    {
        $meta_key = self::meta_key($fromCountry, $toCountry);

        $this->shippingInfo[$meta_key][$quantity] = $items;

        return $this;
    }

    public function resetDefaultShipping(): self
    {
        $this->setCost(null)
            ->setCountryTo(null)
            ->setMethod(null)
            ->setCountryTo(null)
            ->setVariationKey(null);

        return $this;
    }

    public function resetShippingInfo(): self
    {
        $this->shippingInfo = [];

        return $this;
    }

    public function toArray(): array
    {
        return [
            self::FIELD_SHIPPING_INFO => $this->getShippingInfo(),
            self::FIELD_COUNTRY_TO => $this->getCountryTo(),
            self::FIELD_COUNTRY_FROM => $this->getCountryFrom(),
            self::FIELD_METHOD => $this->getMethod(),
            self::FIELD_COST => $this->getCost(),
            self::FIELD_VARIATION_KEY => $this->getVariationKey(),
        ];
    }

    /**
     * @todo: move this code somewhere
     */
    public static function meta_key(string $from_country, string $to_country): string
    {

        return ProductShippingData::normalize_country($from_country) .
            ProductShippingData::normalize_country($to_country);
    }

    /**
     * @todo: use AliexpressHelper->convertToAliexpressCountryCode() instead
     * Convert WooCommerce country code to AliExpress country code
     */
    public static function normalize_country(?string $country): ?string
    {
        switch ($country) {
            case 'AQ':
            case 'BV':
            case 'IO':
            case 'CU':
            case 'TF':
            case 'HM':
            case 'IR':
            case 'IM':
            case 'SH':
            case 'PN':
            case 'SD':
            case 'SJ':
            case 'SY':
            case 'TK':
            case 'UM':
            case 'EH':
                $country = 'OTHER';
                break;
            case 'AX':
                $country = 'ALA';
                break;
                // case 'CN':
                //     $country = 'HK';
                break;
            case 'CD':
                $country = 'ZR';
                break;
            case 'GG':
                $country = 'GGY';
                break;
            case 'JE':
                $country = 'JEY';
                break;
            case 'ME':
                $country = 'MNE';
                break;
            case 'KP':
                $country = 'KR';
                break;
            case 'BL':
                $country = 'BLM';
                break;
            case 'MF':
                $country = 'MAF';
                break;
            case 'RS':
                $country = 'SRB';
                break;
            case 'GS':
                $country = 'SGS';
                break;
            case 'TL':
                $country = 'TLS';
                break;
            case 'GB':
                $country = 'UK';
                break;
            default:
        }

        $country = trim($country);
        if (empty($country)) {
            return 'CN';
        }

        return $country;
    }

}
