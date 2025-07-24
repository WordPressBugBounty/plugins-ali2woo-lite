<?php

/**
 * Description of ImportedProductService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
use WC_Product;

class ImportedProductService
{
    private WC_Product $Product;

    public const KEY_SHOW_PRODUCT_VIDEO_TAB = '_a2w_show_product_video_tab';
    public const KEY_ORIGINAL_PRODUCT_URL = '_a2w_original_product_url';
    public const KEY_VIDEO_DATA = '_a2w_video';
    public const KEY_EXTERNAL_ID = '_a2w_external_id';
    public const KEY_EXTERNAL_SKU_ID = '_a2w_ali_sku_id';
    public const KEY_COUNTRY_CODE = '_a2w_country_code';
    public const KEY_EXTRA_DATA = '_a2w_extra_data';

    public const FIELD_SHIPPING_INFO = 'shipping_info';
    public const FIELD_VARIATION_KEY = 'variation_key';
    public const FIELD_COUNTRY_TO = 'shipping_to_country';
    public const FIELD_COUNTRY_FROM = 'shipping_from_country';
    public const FIELD_METHOD = 'shipping_default_method';
    public const FIELD_COST = 'shipping_cost';
    public const FIELD_COUNTRY_FROM_LIST = 'shipping_from_country_list';
    public const FIELD_EXTRA_DATA = 'extra_data';
    public const FIELD_EXTERNAL_SKU_ID = 'skuId';
    public const FIELD_EXTERNAL_PRODUCT_ID = 'id';
    public const FIELD_COUNTRY_CODE = 'country_code';
    public const FIELD_QUANTITY = 'quantity';
    public const FIELD_ORIGINAL_QUANTITY = 'original_quantity';

    private array $shouldShowVideoTabStates = [
        ShouldShowVideoTab::HIDE,
        ShouldShowVideoTab::SHOW,
    ];

    public function __construct(WC_Product $WC_ProductOrVariation)
    {
        $this->Product = $WC_ProductOrVariation;
    }

    public function getParentId(): int
    {
        if ($this->Product->get_type() === 'variation') {
            return $this->Product->get_parent_id();
        }

        return $this->getId();
    }

    public function getId(): int
    {
        return $this->Product->get_id();
    }

    public function getShouldShowVideoTab(): ?string
    {
        return get_post_meta(
            $this->getId(),
            self::KEY_SHOW_PRODUCT_VIDEO_TAB,
            true
        );
    }

    public function setShouldShowVideoTab(?string $shouldShow): self
    {
        if ($shouldShow && !in_array($shouldShow, $this->shouldShowVideoTabStates)) {
            $shouldShow = null;
        }

        update_post_meta(
            $this->getId(),
            self::KEY_SHOW_PRODUCT_VIDEO_TAB,
            $shouldShow
        );

        return $this;
    }

    public function getOriginalUrl(): string
    {
        return get_post_meta($this->getParentId(), self::KEY_ORIGINAL_PRODUCT_URL, true);
    }

    public function getVideoData(): array
    {
        $data = get_post_meta($this->getParentId(), self::KEY_VIDEO_DATA, true);
        if (empty($data)) {
            return [];
        }

        return $data;
    }

    public function getExternalId(): ?string
    {
        $externalId = get_post_meta($this->getParentId(), self::KEY_EXTERNAL_ID, true);

        if (empty($externalId)) {
            return null;
        }

        return $externalId;
    }

    public function getExternalSkuId(): ?string
    {
        $skuId = null;

        if ($this->Product->get_type() === 'variation') {
            $skuId = get_post_meta($this->getId(), self::KEY_EXTERNAL_SKU_ID, true);
        }

        return $skuId;
    }

    public function getShippingFromCountryCode(): string
    {
        $defaultCode = "CN";
        $countryCode = get_post_meta($this->getId(), self::KEY_COUNTRY_CODE, true);

        return empty($countryCode) ? $defaultCode : $countryCode;
    }

    public function getExtraData(): ?string
    {
        $extraData = get_post_meta($this->getId(), self::KEY_EXTRA_DATA, true);
        if (empty($extraData)) {
            return null;
        }

        return $extraData;
    }

    public function isVariable(): bool
    {
        return $this->Product->get_type() === 'variation';
    }

    public function getImportedProduct(): array
    {
        //  $importedProduct = $this->WoocommerceModel->get_product_by_post_id($product_id, $withVars);
    }
}
