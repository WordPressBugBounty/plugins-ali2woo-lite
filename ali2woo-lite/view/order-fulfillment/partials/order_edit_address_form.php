<?php /**
 * @var array $shipping_fields
 * @var array $order_data
 * @var array $additional_shipping_fields
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

        if (is_callable( [$order_data['order'], 'get_' . $field_name])) {
            $field['value'] = $order_data['order']->{"get_$field_name"}('edit');
        } else {
            $field['value'] = $order_data['order']->get_meta('_' . $field_name);
        }

        //we need to set the global post for woocommerce_wp_select and other functions below
        global $post;
        $post = get_post($thepostid);
        setup_postdata($post);

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