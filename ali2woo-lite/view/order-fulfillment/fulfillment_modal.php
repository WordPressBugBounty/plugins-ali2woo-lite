<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
/**
 * @var mixed $purchase_code
 */
?>
<div class="modal-overlay modal-fulfillment">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php _ex('Order fulfillment', 'popup title', 'ali2woo');?></h3>
            <a class="modal-btn-close" href="#"></a>
        </div>
        <div class="modal-body"></div>
        <div class="modal-footer">
            <?php if ($purchase_code):?>
            <div style="display: inline-block;">
            <a id="pay-for-orders" target="_blank" class="btn btn-success" href="https://www.aliexpress.com/p/order/index.html" title="<?php  esc_html_e('You will be redirected to the AlIExpress portal. You must be authorized in your account to make the payment', 'ali2woo');?>"><?php  esc_html_e('Pay for order(s)', 'ali2woo');?></a>
            <button id="fulfillment-auto" class="btn btn-success" type="button">
                <div class="btn-icon-wrap cssload-container"><div class="cssload-speeding-wheel"></div></div>
                <?php  esc_html_e('Fulfill orders automatically', 'ali2woo');?>
            </button>
            </div>
            <?php endif; ?>
            <button class="btn btn-default modal-close" type="button"><?php esc_html_e('Close');?></button>
        </div>
    </div>
</div>
