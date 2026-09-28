<?php /**
 * @var array $shipping_fields
 * @var array $order_data
 * @var array $additional_shipping_fields
 * @var \AliNext_Lite\OrderShippingDataService $OrderShippingDataService
 */
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
?>

<div class="order-edit-address-form">
    <?php
    global $thepostid;
    $old_thepostid = $thepostid;
    $thepostid = $order_data['order_id'];
    foreach ($shipping_fields as $key => $field) {
        if (!isset($field['type'])) {
            $field['type'] = 'text';
        }
        if (!isset($field['id'])) {
            $field['id'] = '_shipping_' . $key;
        }

        $field_name = 'shipping_' . $key;

        if ($key === 'country') {
            $field['value'] = $OrderShippingDataService->resolveShippingCountry($order_data['order']);
        } elseif ($key === 'phone') {
            if ($OrderShippingDataService->isShippingPhoneOverridden()) {
                $field['value'] = $OrderShippingDataService->resolveShippingPhone($order_data['order']);
                $field['custom_attributes'] = [
                    'readonly' => 'readonly',
                    'style' => 'background:#f1f1f1;',
                ];
                $field['desc_tip'] = true;
                $field['description'] = esc_html__(
                    'This phone number comes from the plugin settings and overrides the phone number stored in the order.',
                    'ali2woo'
                );
            } else {
                $field['value'] = $OrderShippingDataService->resolveShippingPhoneDisplay($order_data['order']);
            }
        } elseif (is_callable( [$order_data['order'], 'get_' . $field_name])) {
            $field['value'] = $order_data['order']->{"get_$field_name"}('edit');
        } else {
            $field['value'] = $order_data['order']->get_meta('_' . $field_name);
        }

        //we need to set the global post for woocommerce_wp_select and other functions below
        global $post;
        $post = get_post($thepostid);
        setup_postdata($post);

        if ($key === 'phone') {
            $phoneCodeField = [
                'id' => '_shipping_phone_code',
                'label' => esc_html__('Phone code', 'ali2woo'),
                'value' => $OrderShippingDataService->resolveShippingPhoneCode($order_data['order']),
                'class' => 'input-text small',
                'wrapper_class' => '_shipping_phone_code',
                'custom_attributes' => [],
            ];

            if ($OrderShippingDataService->isShippingPhoneOverridden()) {
                $phoneCodeField['custom_attributes'] = [
                    'readonly' => 'readonly',
                    'style' => 'background:#f1f1f1;',
                ];
                $phoneCodeField['desc_tip'] = true;
                $phoneCodeField['description'] = esc_html__(
                    'This phone code comes from the plugin settings and overrides the phone code stored in the order.',
                    'ali2woo'
                );
            }

            woocommerce_wp_text_input($phoneCodeField);
        }

        switch ($field['type']) {
            case 'select':
                woocommerce_wp_select($field);
                break;
            case 'checkbox':
                woocommerce_wp_checkbox($field);
                break;
            default:
                woocommerce_wp_text_input($field);
                break;
        }

        wp_reset_postdata();
    }
    $thepostid = $old_thepostid;
    ?>
    <div class="additional-fields-toggle">
        <span class="dashicons dashicons-arrow-right"></span>
        <span class="toggle-label-collapsed"><?php _ex("Show additional fields", 'popup title', 'ali2woo'); ?></span>
        <span class="toggle-label-expanded"><?php _ex("Hide additional fields", 'popup title', 'ali2woo'); ?></span>
    </div>
    <div class="additional-fields-body">
    <?php
    $additional_shipping_fields = apply_filters('a2wl_fill_additional_shipping_fields', $additional_shipping_fields, $order_data['order']);

    foreach ($additional_shipping_fields as $key => $field) {
        if (!isset($field['type'])) {
            $field['type'] = 'text';
        }
        if (!isset($field['id'])) {
            $field['id'] = '_shipping_' . $key;
        }

        $field_name = 'shipping_' . $key;

        if (empty($field['value'])) {
            if (is_callable([$order_data['order'], 'get_' . $field_name])) {
                $field['value'] = $order_data['order']->{"get_$field_name"}('edit');
            } else {
                $field['value'] = $order_data['order']->get_meta('_' . $field_name);
            }
        }

        switch ($field['type']) {
            case 'select':
                woocommerce_wp_select( $field );
                break;
            case 'checkbox':
                woocommerce_wp_checkbox( $field );
                break;
            default:
                woocommerce_wp_text_input( $field );
                break;
        }
    }
    ?>
    </div>
    <div class="button-row">
        <button id="cancel-order-address" class="a2wl-btn a2wl-btn--secondary" type="button"><?php echo esc_html__('Cancel'); ?></button>
        <button id="save-order-address" class="a2wl-btn a2wl-btn--primary" type="button"><?php echo esc_html__('Save'); ?></button>
    </div>
</div>