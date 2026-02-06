<div class="modal-overlay modal-apply-pricing-rules">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo esc_html_e('Apply pricing rules to existing products', 'ali2woo'); ?></h3>
            <a class="modal-btn-close" href="#"></a>
        </div>
        <div class="modal-body">
            <label><?php echo esc_html_e('Select the update type', 'ali2woo'); ?></label>
            <div style="padding-bottom: 20px;">
                <div class="type btn-group" role="group">
                    <button type="button" class="btn btn-default" value="price"><?php echo esc_html_e('Prices', 'ali2woo'); ?></button>
                    <button type="button" class="btn btn-default" value="regular_price"><?php echo esc_html_e('Regular Prices', 'ali2woo'); ?></button>
                    <button type="button" class="btn btn-default" value="all"><?php echo esc_html_e('Prices and Regular Prices', 'ali2woo'); ?></button>
                </div>
            </div>
            <label><?php echo esc_html_e('Select the update scope', 'ali2woo'); ?></label>
            <div>
                <div class="scope btn-group" role="group">
                    <button type="button" class="btn btn-default" value="shop"><?php echo esc_html_e('Shop', 'ali2woo'); ?></button>
                    <button type="button" class="btn btn-default" value="import"><?php echo esc_html_e('Import List', 'ali2woo'); ?></button>
                    <button type="button" class="btn btn-default" value="all"><?php echo esc_html_e('Shop and Import List', 'ali2woo'); ?></button>
                </div>
            </div>
            <p class="small row-comments">
                <?php echo esc_html_e(
                    'After applying pricing rules, please ensure that in WooCommerce either the regular price equals the sale price (no discount), or the regular price is higher than the sale price.',
                    'ali2woo'
                ); ?>
            </p>
        </div>
        <div class="modal-footer">
            <span class="status" style="padding-right: 10px;">xxx</span>
            <button class="btn btn-default close-btn" type="button"><?php echo esc_html_e('Close', 'ali2woo'); ?></button>
            <button class="btn btn-success apply-btn" type="button"><div class="btn-icon-wrap cssload-container"><div class="cssload-speeding-wheel"></div></div><?php echo esc_html_e('Apply', 'ali2woo'); ?></button>
        </div>
    </div>
</div>
