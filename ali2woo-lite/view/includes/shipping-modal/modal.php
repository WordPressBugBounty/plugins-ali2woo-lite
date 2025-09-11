<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
/**
 * @var array $countries
 * @var array $variations
 */
?>
<div class="modal-overlay modal-shipping a2wl-content a2wl-modal-standard-fields">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">
                <?php _ex('Available shipping methods', 'shipping modal', 'ali2woo');?>
            </h3>
            <a class="modal-btn-close" href="#"></a>
        </div>
        <div class="modal-body">
            <div class="modal-card">
                <div class="mb20">
                <span>
                    <?php _ex('Calculate your shipping price:', 'shipping modal', 'ali2woo');?>
                </span>
                </div>

                <?php include_once 'filters.php'; ?>

                <div class="message-container">
                    <div class="shipping-method">
                    <span class="shipping-method-title">
                        <?php _ex(
                            'These are the shipping methods you will be able to select when processing orders:',
                            'shipping modal',
                            'ali2woo'
                        );?>
                    </span>
                        <div class="shipping-method">
                            <table class="shipping-table">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th>
                                        <strong>
                                            <?php _ex('Shipping Method', 'shipping modal','ali2woo');?>
                                        </strong>
                                    </th>
                                    <th>
                                        <strong>
                                            <?php _ex('Delivery Time', 'shipping modal','ali2woo');?>
                                        </strong>
                                    </th>
                                    <th>
                                        <strong>
                                            <?php _ex('Cost', 'shipping modal','ali2woo');?>
                                        </strong>
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-default modal-close" type="button">
                <?php esc_html_e('Ok', 'ali2woo');?>
            </button>
        </div>
    </div>
</div>
