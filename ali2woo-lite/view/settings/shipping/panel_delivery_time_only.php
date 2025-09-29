<?php
/**
 * @var string $curDeliveryInfoDisplayMode
 * @var string $deliveryTimeTextFormat
 */
use AliNext_Lite\Settings;
?>
<div class="panel panel-default mt20 mb20 delivery-time-only-conditional" <?php if ($curDeliveryInfoDisplayMode !== 'delivery_time_only') echo 'style="display: none;"'; ?>>
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo esc_html_x('Delivery Time Only Settings', 'Panel title', 'ali2woo'); ?></h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <div class="row-comments text-muted" style="margin-bottom: 20px;">
                    <?php echo esc_html_x(
                        'These settings apply only in "Delivery time only" mode. By default, delivery time is shown on the product page next to the Add to Cart button, in the cart, and at checkout next to each item. Use the shortcode to display it elsewhere.',
                        'Panel description',
                        'ali2woo'
                    ); ?>
                </div>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label for="a2wl_<?php echo Settings::SETTING_DELIVERY_TIME_TEXT_FORMAT; ?>">
                    <strong><?php echo esc_html_x('Delivery time text format', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip"
                     data-title="<?php echo esc_html_x('Customize how delivery time is displayed. Use {delivery_time} as a placeholder.', 'setting description', 'ali2woo'); ?>"></div>
            </div>
            <div class="field__input-wrap">
                <input type="text" class="field__input form-control large-input"
                       id="a2wl_<?php echo Settings::SETTING_DELIVERY_TIME_TEXT_FORMAT; ?>"
                       name="a2wl_<?php echo Settings::SETTING_DELIVERY_TIME_TEXT_FORMAT; ?>"
                       value="<?php echo esc_attr($deliveryTimeTextFormat); ?>" />
                <div class="table-responsive">
                    <table class="table table-bordered a2wl-table-of-placeholders">
                        <thead>
                        <tr class="active">
                            <th scope="col" style="width: 50%"><?php echo esc_html_x('Placeholder', 'Table header', 'ali2woo'); ?></th>
                            <th scope="col" style="width: 50%"><?php echo esc_html_x('Purpose', 'Table header', 'ali2woo'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="a2wl-placeholder-value-container">
                                <div class="form-inline" role="form">
                                    <div class="form-group has-success has-feedback">
                                        <input class="a2wl-placeholder-value form-control" type="text" readonly value="{delivery_time}">
                                        <span class="dashicons dashicons-admin-page form-control-feedback a2wl-placeholder-value-copy"></span>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo esc_html_x('Delivery time (e.g. 15–25 days)', 'Placeholder description', 'ali2woo'); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label>
                    <strong><?php echo esc_html_x('Fallback delivery time range (days)', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip"
                     data-title="<?php echo esc_html_x('Used when delivery time is not available from AliExpress.', 'setting description', 'ali2woo'); ?>"></div>
            </div>
            <div class="field__input-wrap">
                <div class="field__fill form-group input-block no-margin">
                    <?php echo esc_html_x('From', 'Range label', 'ali2woo'); ?>
                    <input type="number" min="1" style="max-width: 60px;" class="field__input form-control"
                           name="a2wl_<?php echo Settings::SETTING_DELIVERY_TIME_FALLBACK_MIN; ?>"
                           value="<?php echo esc_attr($deliveryTimeFallbackMin); ?>" />

                    <?php echo esc_html_x('To', 'Range label', 'ali2woo'); ?>
                    <input type="number" min="1" style="max-width: 60px;" class="field__input form-control"
                           name="a2wl_<?php echo Settings::SETTING_DELIVERY_TIME_FALLBACK_MAX; ?>"
                           value="<?php echo esc_attr($deliveryTimeFallbackMax); ?>" />
                </div>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <strong><?php echo esc_html_x('Delivery-time shortcode', 'Setting title', 'ali2woo'); ?></strong>
                <div class="info-box" data-toggle="tooltip"
                     data-title="<?php echo esc_html_x(
                         'Use these shortcodes to display delivery time manually anywhere.',
                         'setting description',
                         'ali2woo'
                     ); ?>"></div>
            </div>

            <div class="field__input-wrap">
                <div class="table-responsive">
                    <table class="table table-bordered a2wl-table-of-placeholders">
                        <thead>
                        <tr class="active">
                            <th scope="col" style="width: 50%"><?php echo esc_html_x('Shortcode', 'Shortcode table header', 'ali2woo'); ?></th>
                            <th scope="col" style="width: 50%"><?php echo esc_html_x('Purpose', 'Shortcode table header', 'ali2woo'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="a2wl-placeholder-value-container">
                                <div class="form-inline" role="form">
                                    <div class="form-group has-success has-feedback">
                                        <input class="a2wl-placeholder-value form-control" type="text" readonly value='[a2wl-delivery-time id="123"]'>
                                        <span class="dashicons dashicons-admin-page form-control-feedback a2wl-placeholder-value-copy"></span>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo esc_html_x('Displays delivery time for the specified product ID.', 'Shortcode description', 'ali2woo'); ?></td>
                        </tr>
                        <tr>
                            <td class="a2wl-placeholder-value-container">
                                <div class="form-inline" role="form">
                                    <div class="form-group has-success has-feedback">
                                        <input class="a2wl-placeholder-value form-control" type="text" readonly value='[a2wl-delivery-time]'>
                                        <span class="dashicons dashicons-admin-page form-control-feedback a2wl-placeholder-value-copy"></span>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo esc_html_x(
                                    'Displays delivery time for the current product when used on a single product page (global $product). No ID parameter is needed.',
                                    'Shortcode description',
                                    'ali2woo'
                                ); ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>