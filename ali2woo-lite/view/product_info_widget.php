<?php
/**
 * @var string $originalProductUrl
 * @var string|null $videoShortcodeContent
 * @var bool $defaultGlobalSettingText
 * @var bool $showVideoTabProduct
 * @var ImportedProductService $ImportedProductService
 */

use AliNext_Lite\AbstractController;
use AliNext_Lite\ImportedProductService;

?>
<p>
<?php echo wp_kses_post(
    sprintf(
        __( 'External ID: <a target="_blank" href="%1$s">%2$s</a>', 'ali2woo'),
        esc_url($ImportedProductService->getOriginalUrl()),
        $ImportedProductService->getExternalId()
    ));
?>
</p>
<?php
if ($videoShortcodeContent) : ?>

<?php wp_nonce_field(AbstractController::PAGE_NONCE_ACTION, AbstractController::NONCE); ?>
<?php
    $showVideoTabProduct = $ImportedProductService->getShouldShowVideoTab();
?>
    <p>
        <label for="a2wl-product-video-tab"><?php esc_html_e( 'Product video tab: ', 'ali2woo' ); ?></label>
        <select id="a2wl-product-video-tab" name="a2wl_show_product_video_tab">
            <option value=""><?php echo $defaultGlobalSettingText; ?></option>
            <option value="show" <?php selected($showVideoTabProduct, 'show') ?>><?php esc_html_e( 'Show', 'ali2woo' ); ?></option>
            <option value="hide" <?php selected($showVideoTabProduct, 'hide') ?>><?php esc_html_e( 'Hide', 'ali2woo' ); ?></option>
        </select>
    </p>

    <div class="a2wl-video-container"><?php echo do_shortcode($videoShortcodeContent); ?></div>
    <p><?php esc_html_e('Product video shortcode: ', 'ali2woo'); ?><input
            title="<?php esc_attr_e('Click here to copy this shortcode', 'ali2woo'); ?>"
            class="a2wl-video-shortcode" type="text" readonly
            value="<?php echo esc_attr('[a2wl_product_video product_id="' . $ImportedProductService->getId() . '"]'); ?>">
    </p>
<?php endif; ?>

