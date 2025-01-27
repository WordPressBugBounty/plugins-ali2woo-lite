<?php
/**
 * Description of ProductVideoTabController
 *
 * @author Ali2Woo Team
 *
 * @autoload: init
 *
 */

namespace AliNext_Lite;;

class ProductVideoController extends AbstractController
{
    private string $videoUrl;
    private ?string $videoPoster;

    public const SHORTCODE_TAG_VIDEO = 'a2wl_product_video';

    private ImportedProductServiceFactory $ImportedProductServiceFactory;

    public function __construct(ImportedProductServiceFactory $ImportedProductServiceFactory)
    {
        parent::__construct();

        $this->ImportedProductServiceFactory = $ImportedProductServiceFactory;

        add_action('init', [$this, 'initShortcode']);
        add_filter('woocommerce_product_tabs', [$this, 'addVideoTab']);
    }

    public function initShortcode(): void
    {
        add_shortcode(self::SHORTCODE_TAG_VIDEO, [$this, 'handleVideoShortcode']);
    }

    public function handleVideoShortcode(array $attributes): string
    {
        global $product;

        $args = shortcode_atts(
            [
                'product_id' => '',
                'poster'     => '',
                'loop'       => '',
                'autoplay'   => '',
                'preload'    => 'metadata',
                'height'     => false,
                'width'      => false,
                'class'      => 'wp-video-shortcode a2wl-product-video-shortcode',
            ], $attributes
        );
        if ($args['height'] === false) {
            unset($args['height']);
        }
        if ($args['width'] === false) {
            unset( $args['width']);
        }
        if (!$args['product_id']) {
            if ($product) {
                $args['product_id'] = $product->get_id();
            }
        }
        if ($args['product_id']) {
            $WC_Product = wc_get_product($args['product_id']);
            if (!$WC_Product) {
                return '';
            }

            $ImportedProductService = $this->ImportedProductServiceFactory->createFromProduct($WC_Product);
            $videoData = $ImportedProductService->getVideoData();

            if (!empty($videoData)) {
                $videoUrl = Utils::getProductVideoUrl(['video' => $videoData]);
                $videoPoster = Utils::getProductVideoPoster(['video' => $videoData]);
                if ($videoPoster) {
                    $args['poster'] = $videoPoster;
                }

                unset($args['product_id']);
                $shortcodeAttributes = [];
                foreach ($args as $key => $value) {
                    $shortcodeAttributes[] = $key . '="' . $value . '"';
                }

                $content = sprintf(
                    '[video src="%s" %s]',
                    $videoUrl,
                    implode( ' ', $shortcodeAttributes )
                );

                return do_shortcode($content);
            }
        }

        return '';
    }

    public function addVideoTab(array $tabs): array
    {
        $WC_Product = wc_get_product(get_the_ID());
        if (!$WC_Product) {
            return $tabs;
        }

        $ImportedProductService = $this->ImportedProductServiceFactory->createFromProduct($WC_Product);

        $showVideoTabProduct = $ImportedProductService->getShouldShowVideoTab();

        if (!$showVideoTabProduct) {
            if (!get_setting(Settings::SETTING_SHOW_PRODUCT_VIDEO_TAB)) {
                return $tabs;
            }
        } else {
            if ($showVideoTabProduct === ShouldShowVideoTab::HIDE) {
                return $tabs;
            }
        }

        $videoData = $ImportedProductService->getVideoData();

        if (!empty($videoData)) {
            $this->videoUrl = Utils::getProductVideoUrl(['video' => $videoData]);
            $this->videoPoster = Utils::getProductVideoPoster(['video' => $videoData]);

            if ($this->videoUrl) {
                $tabs['a2wl_video_tab'] = [
                    'title' => __( 'Video', 'ali2woo' ),
                    'priority' => get_setting(Settings::SETTING_VIDEO_TAB_PRIORITY),
                    'callback' => [$this, 'renderVideoTab']
                ];
            }
        }

        return $tabs;
    }

    public function renderVideoTab(): void
    {
        $content = sprintf(
            '[video src="%s" poster="%s"]',
            $this->videoUrl,
            $this->videoPoster ?? 'none'
        );

        if (get_setting(Settings::SETTING_MAKE_VIDEO_FULL_TAB_WIDTH)) {
            $content = sprintf(
                '[video src="%s" poster="%s" width=""]',
                $this->videoUrl,
                $this->videoPoster ?? 'none'
            );
        }

        echo do_shortcode($content);
    }
}
