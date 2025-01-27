<?php
/**
 * Description of ProductAdminWidgetController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 *
 */

namespace AliNext_Lite;;

use WC_Product;

class ProductInfoWidgetController extends AbstractController
{
    public const PARAM_SHOW_PRODUCT_VIDEO_TAB = 'a2wl_show_product_video_tab';

    private VideoShortcodeService $VideoShortcodeService;
    private ImportedProductServiceFactory $ImportedProductServiceFactory;

    public function __construct(
        VideoShortcodeService $VideoShortcodeService,
        ImportedProductServiceFactory $ImportedProductServiceFactory
    ) {
        parent::__construct();

        $this->VideoShortcodeService = $VideoShortcodeService;
        $this->ImportedProductServiceFactory = $ImportedProductServiceFactory;

        add_action('a2wl_admin_assets', [$this, 'enqueueAdminAssets'], 2);
        add_action('add_meta_boxes', [$this, 'addProductInfoMetabox']);
        add_action(
            'woocommerce_admin_process_product_object',
            [$this, 'woocommerce_admin_process_product_object']
        );
    }

    public function enqueueAdminAssets(): void
    {
        wp_localize_script(
            'a2wl-admin-script',
            'a2wl_product_info_data',
            [
                'lang' => [
                    'video_shortcode_copied' => esc_html__('Video shortcode copied', 'ali2woo')
                ]
            ]
        );
    }

    public function addProductInfoMetabox(): void
    {
        global $pagenow, $post;

        if ($pagenow === 'post.php' && $post) {
            $WC_Product = wc_get_product($post->ID);
            if (!$WC_Product) {
                return;
            }

            $ImportedProductService = $this->ImportedProductServiceFactory->createFromProduct($WC_Product);

            if ($ImportedProductService->getExternalId()) {
                add_meta_box(
                    'a2wl-product-info',
                    esc_html__( 'AliExpress product info', 'ali2woo' ),
                    [$this, 'productInfoMetaboxCallback'],
                    'product',
                    'side',
                    'high'
                );
            }
        }
    }

    public function productInfoMetaboxCallback(): void
    {
        global $post;

        $WC_Product = wc_get_product($post->ID);
        $ImportedProductService = $this->ImportedProductServiceFactory->createFromProduct($WC_Product);

        $videoData = $ImportedProductService->getVideoData();

        if ($videoData) {
            $videoShortcodeContent = $this->VideoShortcodeService->buildFromVideoData($videoData);
            $this->model_put('videoShortcodeContent', $videoShortcodeContent);
        } else {
            $this->model_put('videoShortcodeContent', null);
        }

        $showVideoTabGlobalText = get_setting(Settings::SETTING_SHOW_PRODUCT_VIDEO_TAB) ?
            esc_html__( 'Show', 'ali2woo' ) :
            esc_html__( 'Hide', 'ali2woo' );

        $defaultGlobalSettingText = wp_kses_post(
            sprintf(esc_html__( 'Global setting(%s)', 'ali2woo' ), $showVideoTabGlobalText)
        );

        $this->model_put('defaultGlobalSettingText', $defaultGlobalSettingText);
        $this->model_put('ImportedProductService', $ImportedProductService);

        $this->include_view("product_info_widget.php");
    }

    public function woocommerce_admin_process_product_object(WC_Product $WC_Product): void
    {
        if (isset($_POST[self::PARAM_SHOW_PRODUCT_VIDEO_TAB])) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
            $shouldShow = sanitize_text_field($_POST[self::PARAM_SHOW_PRODUCT_VIDEO_TAB]);
            $ImportedProductService = $this->ImportedProductServiceFactory->createFromProduct($WC_Product);
            $ImportedProductService->setShouldShowVideoTab($shouldShow);
        }
    }
}
