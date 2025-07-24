<?php

/**
 * Description of ShippingSettingService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Pages;

class ShippingSettingService
{
    public function handle(): void
    {
        settings()->auto_commit(false);
        set_setting('aliship_shipto', isset($_POST['a2w_aliship_shipto']) ? wp_unslash($_POST['a2w_aliship_shipto']) : 'US');
        set_setting('aliship_frontend', isset($_POST['a2wl_aliship_frontend']));
        set_setting('default_shipping_class', !empty($_POST['a2wl_default_shipping_class']) ? $_POST['a2wl_default_shipping_class'] : false);

        if (isset($_POST['a2wl_aliship_frontend'])) {

            set_setting(
                Settings::SETTING_ASSIGN_SHIPPING_ON_IMPORT,
                isset($_POST['a2wl_' . Settings::SETTING_ASSIGN_SHIPPING_ON_IMPORT])
            );

            if (isset($_POST['default_rule'])) {
                ShippingPriceFormula::set_default_formula(new ShippingPriceFormula($_POST['default_rule']));
            }

            set_setting('aliship_selection_type', isset($_POST['a2wl_aliship_selection_type']) ? wp_unslash($_POST['a2wl_aliship_selection_type']) : 'popup');

            set_setting('aliship_shipping_type', isset($_POST['a2wl_aliship_shipping_type']) ? wp_unslash($_POST['a2wl_aliship_shipping_type']) : 'new');

            set_setting('aliship_shipping_option_text',
                (isset($_POST['a2wl_aliship_shipping_option_text']) && !empty($_POST['a2wl_aliship_shipping_option_text'])) ?
                    wp_unslash($_POST['a2wl_aliship_shipping_option_text']) : '[{shipping_cost}] {shipping_company} ({delivery_time}) - {country}');

            set_setting('aliship_shipping_label', isset($_POST['a2wl_aliship_shipping_label']) ? wp_unslash($_POST['a2wl_aliship_shipping_label']) : 'Shipping');
            set_setting('aliship_free_shipping_label', isset($_POST['a2wl_aliship_free_shipping_label']) ? wp_unslash($_POST['a2wl_aliship_free_shipping_label']) : 'Free Shipping');

            set_setting('aliship_product_enable', isset($_POST['a2wl_aliship_product_enable']));

            if (isset($_POST['a2wl_aliship_product_enable'])) {
                set_setting('aliship_product_position', isset($_POST['a2wl_aliship_product_position']) ? wp_unslash($_POST['a2wl_aliship_product_position']) : 'after_cart');

                set_setting('aliship_product_not_available_message',
                    (isset($_POST['a2wl_aliship_product_not_available_message']) && !empty($_POST['a2wl_aliship_product_not_available_message'])) ?
                        wp_unslash($_POST['a2wl_aliship_product_not_available_message']) : 'This product can not be delivered to {country}.');
            }

            set_setting('aliship_not_available_remove', isset($_POST['a2wl_aliship_not_available_remove']));

            set_setting('aliship_not_available_message',
                (isset($_POST['a2wl_aliship_not_available_message']) && !empty($_POST['a2wl_aliship_not_available_message'])) ?
                    wp_unslash($_POST['a2wl_aliship_not_available_message']) : '[{shipping_cost}] {delivery_time} - {country}');

            $not_available_shipping_cost = (isset($_POST['a2wl_aliship_not_available_cost']) && floatval($_POST['a2wl_aliship_not_available_cost']) >= 0) ? floatval($_POST['a2wl_aliship_not_available_cost']) : 10;

            set_setting('aliship_not_available_cost', $not_available_shipping_cost);

            $min_time = (isset($_POST['a2wl_aliship_not_available_time_min']) && intval($_POST['a2wl_aliship_not_available_time_min']) > 0) ? intval($_POST['a2wl_aliship_not_available_time_min']) : 20;
            $max_time = (isset($_POST['a2wl_aliship_not_available_time_max']) && intval($_POST['a2wl_aliship_not_available_time_max']) > 0) ? intval($_POST['a2wl_aliship_not_available_time_max']) : 30;

            set_setting('aliship_not_available_time_min', $min_time);
            set_setting('aliship_not_available_time_max', $max_time);

        }

        settings()->commit();
        settings()->auto_commit(true);
        
    }

    public function collectModel(): array
    {
        $countryModel = new Country();
        $shipping_class = get_terms([
            'taxonomy' => 'product_shipping_class',
            'hide_empty' => false
        ]);

        return [
            "shipping_countries" => $countryModel->get_countries(),
            "shipping_selection_types" => Shipping::get_selection_types(),
            "shipping_types" => Shipping::get_shipping_types(),
            "selection_position_types" => Shipping::get_selection_position_types(),
            "default_formula" => ShippingPriceFormula::get_default_formula(),
            "shipping_class" => $shipping_class ?: [],
        ];
    }
}
