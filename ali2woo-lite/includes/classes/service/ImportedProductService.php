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

    private array $shouldShowVideoTabStates = [
        ShouldShowVideoTab::HIDE,
        ShouldShowVideoTab::SHOW,
    ];

    public function __construct(WC_Product $Product)
    {
        $this->Product = $Product;
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
        return get_post_meta($this->getId(), self::KEY_ORIGINAL_PRODUCT_URL, true);
    }

    public function getVideoData(): array
    {
        $data = get_post_meta($this->getId(), self::KEY_VIDEO_DATA, true);
        if (empty($data)) {
            return [];
        }

        return $data;
    }

    public function getExternalId(): string
    {
        return get_post_meta($this->getId(), self::KEY_EXTERNAL_ID, true);
    }
}
