<?php
/**
 * @var array $order_data
 */
/** @var ProductShippingDataRepository $ProductShippingDataRepository */
/** @var ProductShippingDataService $ProductShippingDataService */
/** @var ImportedProductServiceFactory $ImportedProductServiceFactory */
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
use AliNext_Lite\ImportedProductServiceFactory;
use AliNext_Lite\ProductShippingDataRepository;
use AliNext_Lite\ProductShippingDataService;
use AliNext_Lite\RepositoryException;
use AliNext_Lite\Utils;

?>
<table class="wp-list-table widefat striped table-view-list fulfillment-order-items main-fulfillment-service">
    <thead><tr>
        <th colspan="2" class="name"><?php echo esc_html__('Item', 'ali2woo'); ?></th>
        <th class="shipping_company"><?php echo esc_html__('Shipping Company', 'ali2woo'); ?></th>
        <th class="delivery_time"><?php echo esc_html__('Delivery Time', 'ali2woo'); ?></th>
        <th class="shipping_cost"><?php echo esc_html__('Shipping Cost', 'ali2woo'); ?></th>
        <th class="cost"><?php echo esc_html__('Cost', 'ali2woo'); ?></th>
        <th class="total"><?php echo esc_html__('Total', 'ali2woo'); ?></th>
        <th class="actions"></th>
    </tr></thead>
    <body>
    <?php
    foreach ($order_data['items'] as $item) :
        $product_id = $item['product_id'];
        $variation_id = $item['variation_id'];
        $WC_ProductOrVariation = wc_get_product($variation_id);
        $external_id = get_post_meta($item['product_id'], '_a2w_external_id', true);
        try {
            $ProductShippingData = $ProductShippingDataRepository->get($item['product_id']);
            $ImportedProductService = $ImportedProductServiceFactory
                ->createFromProduct($WC_ProductOrVariation);
        } catch (RepositoryException $RepositoryException) {
            error_log($RepositoryException->getMessage());
            continue;
        }

        $shipping_cost = $ProductShippingData->getCost();
        $shipping_country_from = $ProductShippingData->getCountryFrom() ?: 'CN';
        $shipping_country_from_list = $ProductShippingDataService->getCountryFromList($item['product_id']);
        $shipping_method = $ProductShippingData->getMethod();
        $variationKey = $ImportedProductService->getExternalSkuId();

        $attributes = $item['attributes'];
        ?>
        <tr data-order_item_id="<?php echo esc_attr($item['order_item_id']); ?>">
            <td class="photo"><?php echo Utils::wp_kses_post($item['image']); ?></td>
            <td class="name">
                <a target="_blank" href="#"><?php echo esc_html__($item['name']); ?></a>
                <?php if ($attributes) : ?>
                    <div class="info attributes">
                        <strong><?php echo esc_html__('Attribute', 'ali2woo'); ?>: </strong>
                        <div><?php echo Utils::wp_kses_post($item['attributes']); ?></div>
                    </div>
                <?php endif; ?>

                <div class="info sku">
                    <strong><?php echo esc_html__('Sku', 'ali2woo'); ?>: </strong>
                    <?php echo esc_html__($item['sku']); ?>
                </div>
                <div class="item-message"></div>
            </td>
            <td class="shipping_company">
                <select class="current-shipping-company">
                    <?php if (!empty($item['shipping_items'])) : ?>
                    <?php foreach ($item['shipping_items'] as $si): ?>
                        <option value="<?php echo $si['serviceName'] . '" ' . ($si['serviceName'] == $item['current_shipping'] ? ' selected="selected"' : ''); ?>">
                            <?php echo $si['company'] . ' (' . $si['time'] . 'days, ' . $si['freightAmount']['formatedAmount'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                    <?php else : ?>
                        <option>
                        <?php echo esc_html__('Shipping unavailable for this address', 'ali2woo'); ?>
                        </option>
                    <?php endif; ?>
                </select>
                <a href="#" class="reload-companies a2wl-shipping-update-global"
                   title="<?php echo esc_attr_x('Update Shipping Companies', 'ali2woo'); ?>"
                   data-country_from_list="<?php echo esc_attr(wp_json_encode($shipping_country_from_list)); ?>"
                   data-external_id="<?php echo esc_attr($external_id); ?>"
                   data-product_id="<?php echo esc_attr($product_id); ?>"
                   data-country_from="<?php echo esc_attr($shipping_country_from); ?>"
                   data-country_to="<?php echo esc_attr($order_data['shiping_to_country']); ?>"
                   data-shipping_method="<?php echo esc_attr($shipping_method); ?>"
                   data-variation_key="<?php echo esc_attr($variationKey); ?>">
                </a>
            </td>
            <td class="delivery_time">
                <?php if (!empty($item['shipping_items'])) : ?>
                    <?php echo esc_html__($item['current_delivery_time'] . ' days', 'ali2woo'); ?>
                <?php else : ?>
                    —
                <?php endif; ?>
            </td>
            <td class="shipping_cost">
                <?php if (!empty($item['shipping_items'])) : ?>
                <?php
                    echo $item['current_shipping_cost'] ?
                        wc_price($item['current_shipping_cost'], ['currency' => $order_data['currency']]) :
                        esc_html__('Free Shipping', 'ali2woo'); ?>
                <?php else : ?>
                    —
                <?php endif; ?>
            </td>
            <td class="cost">
                <?php if (!empty($item['shipping_items'])) : ?>
                <?php echo wc_price($item['cost'], ['currency' => $order_data['currency']]) . ' x ' . esc_html__($item['quantity']); ?> =
                <strong>
                    <?php echo wc_price($item['cost'] * $item['quantity'], ['currency' => $order_data['currency']]); ?>
                </strong>
                <?php else : ?>
                    —
                <?php endif; ?>
            </td>
            <td class="total_cost">
                <?php if (!empty($item['shipping_items'])) : ?>
                <strong> <?php echo wc_price($item['total_cost'], ['currency' => $order_data['currency']]); ?></strong>
                <?php else : ?>
                    —
                <?php endif; ?>
            </td>
            <td class="actions">
                <a class="remove-item" href="#"></a>
            </td>
        </tr>
    <?php endforeach; ?>
    </body>
</table>
