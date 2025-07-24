<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
/**
 * @var array $countries
 * @var array $aliexpressRegions
 * @var string $aliexpressRegion
 * @var string $defaultShippingLabel
 * @var string $countryToCode
 * @var array $applyShippingScopes
 */

use AliNext_Lite\AbstractController;

?>

<div class="modal-overlay modal-apply-shipping-bulk a2wl-content a2wl-modal-standard-fields">
    <div class="modal-content">
        <!-- Header -->
        <div class="modal-header">
            <h3 class="modal-title">
                <?php _ex('Mass Apply Shipping Method', 'shipping modal bulk', 'ali2woo'); ?>
            </h3>
            <a class="modal-btn-close close-icon" href="#"></a>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <!-- Region and Shipping Overview -->
            <div class="_a2wfo a2wl-info"><div>This feature is available in full version of the plugin.</div><a href="https://ali2woo.com/pricing/?utm_source=lite&utm_medium=lite_banner&utm_campaign=alinext-lite" target="_blank" class="btn">GET FULL VERSION</a></div>
            <div class="_a2wfv">
                <div class="container-flex-column mb20 ml20">
                        <div class="grey-color">
                            <strong><?php _ex('Region:', 'shipping modal', 'ali2woo'); ?></strong>
                            <?php echo esc_html($aliexpressRegions[$aliexpressRegion]); ?>
                        </div>
                        <div class="grey-color">
                            <strong><?php _ex('Default Shipping:', 'shipping modal', 'ali2woo'); ?></strong>
                            <?php echo $defaultShippingLabel; ?>
                        </div>
                </div>
                <div class="container-flex-column mb20 ml20">

                    <!-- Scope -->
                    <div class="header-item">
                        <span class="label"><?php _ex('Scope:', 'shipping modal bulk', 'ali2woo'); ?></span>
                        <select data-shipping-scope class="form-control">
                            <?php foreach ($applyShippingScopes as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>">
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Shipping From -->
                    <div class="header-item">
                        <span class="label"><?php _ex('Shipping From:', 'shipping modal', 'ali2woo'); ?></span>
                        <select data-shipping-origin class="form-control country_list">
                            <option selected value="china">
                                <?php _ex('China', 'shipping modal', 'ali2woo'); ?>
                            </option>
                            <option value="region">
                                <?php _ex('Warehouse in Region (if available)', 'shipping modal', 'ali2woo'); ?>
                            </option>
                        </select>
                    </div>

                    <!-- Destination -->
                    <div class="header-item">
                        <span class="label"><?php _ex('Shipping To:', 'shipping modal bulk', 'ali2woo'); ?></span>
                        <select data-shipping-destination class="modal-country-select form-control country_list">
                            <?php foreach ($countries as $code => $name): ?>
                                <option <?php selected($countryToCode === $code) ?> value="<?php echo esc_attr($code); ?>">
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="header-item">
                        <label>
                            <input type="checkbox" checked data-shipping-ignore-existing />
                            <?php _ex('Skip products with shipping already set', 'shipping modal bulk', 'ali2woo'); ?>
                        </label>
                    </div>

                    <!-- Info Note -->
                    <div class="grey-color">
                        <?php _ex(
                            'Cheapest Shipping (or Default shipping) will be applied based on the first available product variation that matches the selected criteria. If no matching shipping option is found, it won’t be assigned to the product. You can later filter these products and manually set shipping for them as needed.',
                            'shipping modal bulk',
                            'ali2woo'
                        ); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button id="a2wl-apply-shipping-btn" class="btn btn-success btn-icon-left">
                <span class="btn-icon-wrap add">
                    <svg><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#icon-add"></use></svg>
                </span>
                <span class="btn-text"><?php esc_html_e('Apply', 'ali2woo'); ?></span>
                <div class="btn-loader-wrap"><div class="a2wl-loader"></div></div>
            </button>
        </div>
    </div>
</div>

<script>
    window.A2W = window.A2W || {};
    A2W.Modals = A2W.Modals || {};

    (function($) {
        A2W.Modals.BulkShippingModal = {
            init: function() {
                this.bindEvents();
            },

            bindEvents: function() {
                // Close
                $('.modal-apply-shipping-bulk .modal-btn-close').on('click', this.close.bind(this));

                // Apply
                $('#a2wl-apply-shipping-btn').on('click', this.handleApply.bind(this));
            },

            open: function(selectedItems = []) {
                this.selectedProductIds = selectedItems;
                $('.modal-apply-shipping-bulk').addClass('opened');
            },

            close: function(e) {
                if (e) {
                    e.preventDefault();
                }
                $('.modal-apply-shipping-bulk').removeClass('opened');

                A2W.ImportList.resetSelection();
            },

            handleApply: function(e) {
                e.preventDefault();

                const modal = $('.modal-apply-shipping-bulk');
                const scope = modal.find('[data-shipping-scope]').val();
                const countryToCode = modal.find('[data-shipping-destination]').val();
                const countryFromCode = modal.find('[data-shipping-origin]').val();
                const ignoreExisting = !modal.find('[data-shipping-ignore-existing]').is(':checked');

                if (!countryToCode || !countryFromCode) {
                   let msg = '<?php echo esc_js(_x('Please complete all modal fields.', 'shipping modal bulk', 'ali2woo')); ?>';
                    A2W.Services.Notification?.show(msg, 'error');
                    return;
                }

                let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';
                let ids = this.selectedProductIds;

                const $btn = $('#a2wl-apply-shipping-btn');
                $btn.addClass('load'); // Show loader

                A2W.Services.ShippingBulk.applyShipping(
                    scope, countryToCode, countryFromCode, ignoreExisting, ids, nonce_action, ajaxurl
                ).always(function() {
                    $btn.removeClass('load'); // Hide loader once done
                    A2W.Modals.BulkShippingModal.close();
                });
            },
        };

        $(document).on('ready', function() {
            A2W.Modals.BulkShippingModal.init();
        });
    })(jQuery);
</script>