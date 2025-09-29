<?php
/**
 * @var array $shipping_selection_types
 * @var string $curDeliveryInfoDisplayMode
 * @var array $shipping_types
 * @var array $selection_position_types
 * @var bool $shippingOnProductPage
 */

use AliNext_Lite\Settings;
use function AliNext_Lite\get_setting;
?>
<div class="panel panel-default mt20 mb20 full-shipping-options-conditional" <?php if ($curDeliveryInfoDisplayMode !== 'default') echo 'style="display: none;"'; ?>>
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo esc_html_x('Full Shipping Options Settings', 'Panel title', 'ali2woo'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="field field_inline">
            <div class="field__label">
                <label>
                    <strong><?php echo esc_html_x('Shipping selection type', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_html_x('Choose how the shipping method appears on the cart and checkout page: Popup or Select', 'setting description', 'ali2woo'); ?>"></div>
            </div>
            <div class="field__input-wrap">
                <?php $cur_a2wl_aliship_selection_type = get_setting('aliship_selection_type');?>
                <select name="a2wl_aliship_selection_type" id="a2wl_aliship_selection_type" class="field__input form-control small-input">
                    <?php foreach ($shipping_selection_types as $selection_type): ?>
                        <option value="<?php echo $selection_type; ?>"<?php if ($cur_a2wl_aliship_selection_type == $selection_type): ?> selected<?php endif;?>>
                            <?php echo ucfirst($selection_type); ?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label for="a2wl_aliship_shipping_option_text">
                    <strong><?php echo esc_html_x('AliExpress shipping option text', 'Setting title', 'ali2woo'); ?></strong>
                </label>
            </div>
            <div class="field__input-wrap">
                <input type="text" class="field__input form-control large-input" id="a2wl_aliship_shipping_option_text" name="a2wl_aliship_shipping_option_text" value="<?php echo esc_attr(get_setting('aliship_shipping_option_text')); ?>"/>

                <?php AliNext_Lite\Shipping::table_of_placeholders(array(
                    'shipping_cost' => esc_html__('Shipping cost', 'ali2woo'),
                    'shipping_company' => esc_html__('Shipping Company', 'ali2woo'),
                    'delivery_time' => esc_html__('Delivery time', 'ali2woo'),
                    'country' => esc_html__('Shipping country', 'ali2woo'),
                ));?>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label>
                    <strong><?php echo esc_html_x('Shipping calculation', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_html_x('Shipping packages are cached so if you change this option, you`ll need to update your existing cart to make changes apply.', 'setting description', 'ali2woo'); ?>"></div>
            </div>
            <div class="field__input-wrap">
                <?php $cur_a2wl_aliship_shipping_type = get_setting('aliship_shipping_type');?>
                <select name="a2wl_aliship_shipping_type" id="a2wl_aliship_shipping_type" class="field__input form-control large-input">
                    <?php foreach ($shipping_types as $key => $shipping_type): ?>
                        <option value="<?php echo $key; ?>"<?php if ($cur_a2wl_aliship_shipping_type == $key): ?> selected<?php endif;?>>
                            <?php echo $shipping_type; ?>
                        </option>
                    <?php endforeach;?>
                </select>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label for="a2wl_aliship_shipping_label">
                    <strong><?php echo esc_html_x('Shipping Label', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip" data-title='Label of added shipping method in cart/checkout'></div>
            </div>
            <div class="field__input-wrap">
                <input type="text" class="field__input form-control small-input" id="a2wl_aliship_shipping_label" name="a2wl_aliship_shipping_label" value="<?php echo esc_attr(get_setting('aliship_shipping_label')); ?>"/>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label for="a2wl_aliship_free_shipping_label">
                    <strong><?php echo esc_html_x('Free Shipping label', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip" data-title='Label of added free shipping method in cart/checkout'></div>
            </div>
            <div class="field__input-wrap">
                <input type="text" class="field__input form-control small-input" id="a2wl_aliship_free_shipping_label" name="a2wl_aliship_free_shipping_label" value="<?php echo esc_attr(get_setting('aliship_free_shipping_label')); ?>"/>
            </div>
        </div>

        <div class="panel panel-default mt20">
            <div class="panel-body _a2wfv">
                <div class="field field_inline">
                    <div class="field__label">
                        <label>
                            <strong><?php echo esc_html_x('Show on Product page', 'Setting title', 'ali2woo'); ?></strong>
                        </label>
                        <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_html_x('Show shipping selection on the product page', 'setting description', 'ali2woo'); ?>"></div>
                    </div>
                    <div class="field__input-wrap">
                        <input type="checkbox" class="field__input form-control small-input" id="a2wl_<?php echo Settings::SETTING_SHIPPING_ON_PRODUCT_PAGE; ?>" name="a2wl_<?php echo Settings::SETTING_SHIPPING_ON_PRODUCT_PAGE; ?>" <?php if ($shippingOnProductPage): ?>value="yes" checked<?php endif;?> />
                    </div>
                </div>
                <div class="field field_inline">
                    <div class="field__label">
                        <label>
                            <strong><?php echo esc_html_x('Shipping not available message', 'Setting title', 'ali2woo'); ?></strong>
                        </label>
                    </div>
                    <div class="field__input-wrap">
                        <input type="text" class="field__input form-control large-input" id="a2wl_aliship_product_not_available_message" name="a2wl_aliship_product_not_available_message" value="<?php echo esc_attr(get_setting('aliship_product_not_available_message')); ?>"/>
                        <?php AliNext_Lite\Shipping::table_of_placeholders(array('country' => esc_html__('Shipping country', 'ali2woo')));?>
                    </div>
                </div>
                <div class="field field_inline">
                    <div class="field__label">
                        <label>
                            <strong><?php echo esc_html_x('Position of shipping selection on Product page', 'Setting title', 'ali2woo'); ?></strong>
                        </label>
                    </div>
                    <div class="field__input-wrap">
                        <?php $cur_a2wl_aliship_product_position = get_setting('aliship_product_position');?>
                        <select name="a2wl_aliship_product_position" id="a2wl_aliship_product_position" class="field__input form-control small-input">
                            <?php foreach ($selection_position_types as $key => $value): ?>
                                <option value="<?php echo $key; ?>"<?php if ($cur_a2wl_aliship_product_position == $key): ?> selected<?php endif;?>>
                                    <?php echo $value; ?>
                                </option>
                            <?php endforeach;?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default mt20">
            <div class="panel-body _a2wfv">
                <div class="field field_inline">
                    <div class="field__label">
                        <label>
                            <strong><?php echo esc_html_x('Remove items that shipping is not available', 'Setting title', 'ali2woo'); ?></strong>
                            <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_html_x('When customers go to checkout, remove all items which are not available to ship to customers` country. During a customer session, items removed for this reason will be restored automatically if customer changes billing/shipping country to which the items are available to ship.', 'Setting description', 'ali2woo'); ?>"></div>
                        </label>
                    </div>
                    <div class="field__input-wrap">
                        <input type="checkbox" class="field__input form-control" id="a2wl_aliship_not_available_remove" name="a2wl_aliship_not_available_remove" <?php if (get_setting('aliship_not_available_remove')): ?>value="yes" checked<?php endif;?> />
                    </div>

                </div>
                <div class="field field_inline">
                    <div class="field__label">
                        <label>
                            <strong><?php echo esc_html_x('Default message for items that shipping is not available', 'Setting title', 'ali2woo'); ?></strong>
                        </label>
                    </div>
                    <div class="field__input-wrap">
                        <input type="text" class="field__input form-control large-input" id="a2wl_aliship_not_available_message" name="a2wl_aliship_not_available_message" value="<?php echo esc_attr(get_setting('aliship_not_available_message')); ?>"/>
                        <p><?php esc_html_e('Below placeholders can only be used if the "Remove items that shipping is not available" option is disabled. Remove placeholders from the message if you disable that feature.', 'ali2woo')?></p>
                        <?php AliNext_Lite\Shipping::table_of_placeholders(array(
                            'shipping_cost' => esc_html__('Shipping cost', 'ali2woo'),
                            'delivery_time' => esc_html__('Delivery time', 'ali2woo'),
                            'country' => esc_html__('Shipping country', 'ali2woo'),
                        ));?>
                    </div>
                </div>
                <div class="field field_inline">
                    <div class="field__label">
                        <label>
                            <strong><?php echo esc_html_x('Default shipping cost', 'Setting title', 'ali2woo'); ?></strong>
                        </label>
                    </div>
                    <div class="field__input-wrap">
                        <div class="field__input input-group input-block no-margin large-input">
                            <span class="input-group__input input-group__input_addon" id="a2wl_aliship_not_available_cost_addon"><?php echo get_woocommerce_currency_symbol(); ?></span>
                            <input type="number" min="0" step="any" class="input-group__input form-control" id="a2wl_aliship_not_available_cost" name="a2wl_aliship_not_available_cost"  value="<?php echo esc_attr(get_setting('aliship_not_available_cost')); ?>" aria-describedby="a2wl_aliship_not_available_cost_addon" />
                        </div>
                        <p><?php echo esc_html_x('Apply this shipping cost for items that shipping is not available. 0 means free shipping', 'Setting title', 'ali2woo'); ?></p>

                    </div>
                </div>
                <div class="field field_inline">
                    <div class="field__label">
                        <label>
                            <strong><?php echo esc_html_x('Default min delivery time', 'Setting title', 'ali2woo'); ?></strong>
                        </label>
                    </div>
                    <div class="field__input-wrap">
                        <div class="field__input input-group input-block no-margin large-input">
                            <input type="number" min="0" step="any" class="input-group__input form-control" id="a2wl_aliship_not_available_time_min" name="a2wl_aliship_not_available_time_min"  value="<?php echo esc_attr(get_setting('aliship_not_available_time_min')); ?>" aria-describedby="a2wl_aliship_not_available_time_min_addon" />
                            <span class="input-group__input input-group__input_addon" id="a2wl_aliship_not_available_time_min_addon"><?php echo esc_html_x('Day(s)', 'Setting title', 'ali2woo'); ?></span>
                        </div>
                        <p><?php echo esc_html_x('Min delivery time shown for items that shipping is not available', 'Setting title', 'ali2woo'); ?></p>

                    </div>
                </div>
                <div class="field field_inline">
                    <div class="field__label">
                        <label>
                            <strong><?php echo esc_html_x('Default max delivery time', 'Setting title', 'ali2woo'); ?></strong>
                        </label>
                    </div>
                    <div class="field__input-wrap">
                        <div class="field__input input-group input-block no-margin large-input">
                            <input type="number" min="0" step="any" class="input-group__input form-control" id="a2wl_aliship_not_available_time_max" name="a2wl_aliship_not_available_time_max"  value="<?php echo esc_attr(get_setting('aliship_not_available_time_max')); ?>" aria-describedby="a2wl_aliship_not_available_time_max_addon" />
                            <span class="input-group__input input-group__input_addon" id="a2wl_aliship_not_available_time_max_addon"><?php echo esc_html_x('Day(s)', 'Setting title', 'ali2woo'); ?></span>
                        </div>
                        <p><?php echo esc_html_x('Max delivery time shown for items that shipping is not available', 'Setting title', 'ali2woo'); ?></p>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>