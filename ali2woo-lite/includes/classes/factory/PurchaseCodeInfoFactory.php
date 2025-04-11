<?php
/**
 * Description of PurchaseCodeInfoFactory
 *
 * @author Ali2Woo Team
 *
 */

namespace AliNext_Lite;;

class PurchaseCodeInfoFactory
{
    public function buildFromData(array $data): PurchaseCodeInfo
    {
        if (!empty($data[PurchaseCodeInfo::FIELD_LIMITS])) {
            $limits = (new PurchaseCodeInfoLimits())
                ->setAll($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_ALL])
                ->setProduct($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_PRODUCT])
                ->setCategory($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_CATEGORY])
                ->setProducts($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_PRODUCTS])
                ->setDescription($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_DESCRIPTION])
                ->setOrders($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_ORDERS])
                ->setShipping($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_SHIPPING])
                ->setReviews($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_REVIEWS])
                ->setSites($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_SITES])
                ->setSyncProduct($data[PurchaseCodeInfo::FIELD_LIMITS][PurchaseCodeInfoLimits::FIELD_SYNC_PRODUCT]);
        }

        if (!empty($data[PurchaseCodeInfo::FIELD_COUNT])) {
            $count = (new PurchaseCodeInfoCount())
                ->setProduct($data[PurchaseCodeInfo::FIELD_COUNT][PurchaseCodeInfoLimits::FIELD_PRODUCT] ?? null)
                ->setCategory($data[PurchaseCodeInfo::FIELD_COUNT][PurchaseCodeInfoLimits::FIELD_CATEGORY] ?? null)
                ->setProducts($data[PurchaseCodeInfo::FIELD_COUNT][PurchaseCodeInfoLimits::FIELD_PRODUCTS] ?? null)
                ->setDescription($data[PurchaseCodeInfo::FIELD_COUNT][PurchaseCodeInfoLimits::FIELD_DESCRIPTION] ?? null)
                ->setOrders($data[PurchaseCodeInfo::FIELD_COUNT][PurchaseCodeInfoLimits::FIELD_ORDERS] ?? null)
                ->setShipping($data[PurchaseCodeInfo::FIELD_COUNT][PurchaseCodeInfoLimits::FIELD_SHIPPING] ?? null)
                ->setReviews($data[PurchaseCodeInfo::FIELD_COUNT][PurchaseCodeInfoLimits::FIELD_REVIEWS] ?? null);
        }

        return (new PurchaseCodeInfo())
            ->setMessage($data[PurchaseCodeInfo::FIELD_MESSAGE] ?? null)
            ->setTariffCode($data[PurchaseCodeInfo::FIELD_TARIFF_CODE] ?? null)
            ->setTariffFrom($data[PurchaseCodeInfo::FIELD_TARIFF_FROM] ?? null)
            ->setTariffTo($data[PurchaseCodeInfo::FIELD_TARIFF_TO] ?? null)
            ->setValidFrom($data[PurchaseCodeInfo::FIELD_VALID_FROM] ?? null)
            ->setValidTo($data[PurchaseCodeInfo::FIELD_VALID_TO] ?? null)
            ->setCount($count ?? null)
            ->setLimits($limits ?? null);
    }
}
