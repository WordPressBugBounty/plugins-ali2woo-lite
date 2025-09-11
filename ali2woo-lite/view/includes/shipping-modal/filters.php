<?php
/**
 * @var array $countries
 * @var array $variations
 * @var array|null $filter
 */

// Pre-compute commonly reused values.
$selected_country = isset($filter['country']) ? (string) $filter['country'] : '';
$disable_country_to = !empty( $disableCountryTo );
$quantity_value = isset($filter['quantity']) && is_numeric( $filter['quantity']) &&
(int) $filter['quantity'] > 0 ? (int) $filter['quantity'] : 1;


// Build countries <option> lists once, reuses it.
$country_options = '<option></option>';
foreach ( (array) $countries as $code => $country_name ) {
	$is_selected     = ( $selected_country === (string) $code ) ? ' selected="selected"' : '';
	$country_options .= sprintf(
		'<option value="%s"%s>%s</option>',
		esc_attr( (string) $code ),
		$is_selected,
		esc_html( (string) $country_name )
	);
}

// Build variations <option> lists once, reuse it.
$variation_options = '<option></option>';
if ( ! empty( $variations ) && is_array( $variations ) ) {
	foreach ( $variations as $variationId => $variationTitle ) {
		$variation_options .= sprintf(
			'<option value="%s">%s</option>',
			esc_attr( (string) $variationId ),
			esc_html( (string) $variationTitle )
		);
	}
}
?>
<div class="container-flex-column mb20 ml20">
    <div class="variation-select header-item hide">
        <span class="label"><?php _ex('Variation:', 'shipping modal', 'ali2woo'); ?></span>
        <select id="a2wl-modal-variation-select" class="form-control variation_list">
            <?php echo $variation_options; ?>
        </select>
    </div>

    <div class="country-select country-select-from header-item hide">
        <span class="label"><?php _ex('From:', 'shipping modal', 'ali2woo'); ?></span>
        <select id="a2wl-modal-country-from-select" class="modal-country-select form-control country_list">
            <?php echo $country_options; ?>
        </select>
    </div>

    <div class="shipping-from-fixed header-item hide">
        <span class="label"><?php _ex('From:', 'shipping modal', 'ali2woo'); ?></span>
        <span class="location"></span>
    </div>

    <div class="country-select country-select-to header-item hide">
        <span class="label"><?php _ex('To:', 'shipping modal', 'ali2woo'); ?></span>
        <select
            id="a2wl-modal-country-select"
            class="modal-country-select form-control country_list" <?php echo $disable_country_to ? 'disabled' : ''; ?>
        >
            <?php echo $country_options; ?>
        </select>
    </div>

    <div class="quantity-input header-item hide">
        <span class="label"><?php _ex('Quantity:', 'shipping modal', 'ali2woo'); ?></span>
        <input
                id="a2wl-modal-quantity"
                class="form-control quantity_input disabled"
                type="number"
                min="1"
                step="1"
                value="<?php echo esc_attr($quantity_value); ?>"
                disabled
                style="width: 120px; display: inline-block;"
        />
    </div>
</div>

