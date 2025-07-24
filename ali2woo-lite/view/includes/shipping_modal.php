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

                <div class="container-flex-column mb20 ml20">
                    <div class="variation-select header-item hide">
                        <span class="label"><?php _ex('Variation:', 'shipping modal', 'ali2woo');?></span>
                        <select id="a2wl-modal-variation-select" class="form-control variation_list">
                            <option></option>
                            <?php if (!empty($variations)): ?>
                                <?php foreach ($variations as $variationId => $variationTitle): ?>
                                    <option value="<?php echo $variationId; ?>"><?php echo $variationTitle; ?></option>
                                <?php endforeach;?>
                            <?php endif;?>
                        </select>
                    </div>
                    <?php
                    /**
                     * @todo: remove country-select-from block
                     */
                    ?>
                    <div class="country-select country-select-from header-item hide">
                        <span class="label"><?php _ex('From:','shipping modal',  'ali2woo');?></span>
                        <select id="a2wl-modal-country-from-select" class="modal-country-select form-control country_list">
                            <option></option>
                            <?php foreach ($countries as $code => $country_name): ?>
                                <option value="<?php echo $code; ?>"<?php if (isset($filter['country']) && $filter['country'] == $code): ?> selected="selected"<?php endif;?>>
                                    <?php echo $country_name; ?>
                                </option>
                            <?php endforeach;?>
                        </select>
                    </div>
                    <div class="shipping-from-fixed header-item hide">
                        <span class="label"><?php _ex('From:', 'shipping modal','ali2woo');?></span>
                        <span class="location"></span>
                    </div>
                    <div class="country-select country-select-to header-item hide">
                        <span class="label"><?php _ex('To:', 'shipping modal','ali2woo');?></span>
                        <select <?php if (isset($disableCountryTo)) : ?> disabled <?php endif;?> id="a2wl-modal-country-select" class="modal-country-select form-control country_list">
                            <option></option>
                            <?php foreach ($countries as $code => $country_name): ?>
                                <option value="<?php echo $code; ?>"<?php if (isset($filter['country']) && $filter['country'] == $code): ?> selected="selected"<?php endif;?>>
                                    <?php echo $country_name; ?>
                                </option>
                            <?php endforeach;?>
                        </select>
                    </div>
                </div>
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
