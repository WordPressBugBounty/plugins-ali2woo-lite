<?php
/**
 * @var string $originalProductUrl
 * @var string|null $videoShortcodeContent
 * @var bool $defaultGlobalSettingText
 * @var bool $showVideoTabProduct
 * @var ImportedProductService $ImportedProductService
 * @var int $wcProductId
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
<p>
<?php $resetProductShippingCacheLabel = _x('Reset shipping cache', 'product editing page', 'ali2woo'); ?>
<?php echo wp_kses_post(
    sprintf(
        '<a class="reset_product_shipping_cache" target="_blank" href="#">%s</a>',
        $resetProductShippingCacheLabel
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
<script>
    (function ($) {
        let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';

        $(".reset_product_shipping_cache").on("click", function (event) {
            event.preventDefault();

            if (confirm(
                "Confirm Cache Reset: Are you sure you want to reset the product shipping cache? This will refresh all shipping-related data."
            )) {
                const data = {
                    'action': 'a2wl_reset_product_shipping_cache',
                    'id': '<?php echo $wcProductId; ?>',
                    "ali2woo_nonce": nonce_action,
                };
                $.post(ajaxurl, data).done(function (response) {
                    const json = JSON.parse(response);
                    if (json.state !== 'ok') {
                        console.log(json);
                    } else {
                        alert('The product shipping cache has been reset.')
                    }
                }).fail(function (xhr, status, error) {
                    console.log(error);
                });
            }
        });
    })(jQuery);
</script>

