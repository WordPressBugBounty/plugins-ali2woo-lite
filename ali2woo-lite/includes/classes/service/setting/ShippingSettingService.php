<?php

/**
 * Description of ShippingSettingService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ShippingSettingService
{
    public function handle(): void
    {
        settings()->auto_commit(false);
        set_setting('aliship_shipto', isset($_POST['a2w_aliship_shipto']) ? wp_unslash($_POST['a2w_aliship_shipto']) : 'US');
        set_setting(
            Settings::SETTING_ALLOW_SHIPPING_FRONTEND,
            isset($_POST['a2wl_' . Settings::SETTING_ALLOW_SHIPPING_FRONTEND])
        );
        set_setting('default_shipping_class', !empty($_POST['a2wl_default_shipping_class']) ? $_POST['a2wl_default_shipping_class'] : false);

        if (isset($_POST['a2wl_' . Settings::SETTING_ALLOW_SHIPPING_FRONTEND])) {
            set_setting(
                Settings::SETTING_ASSIGN_SHIPPING_ON_IMPORT,
                isset($_POST['a2wl_' . Settings::SETTING_ASSIGN_SHIPPING_ON_IMPORT])
            );

            $syncProductShipping = isset($_POST['a2wl_' . Settings::SETTING_SYNC_PRODUCT_SHIPPING]);
            set_setting(Settings::SETTING_SYNC_PRODUCT_SHIPPING, $syncProductShipping);

            $this->saveDeliveryInfoDisplayMode();

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

            set_setting(
                Settings::SETTING_SHIPPING_ON_PRODUCT_PAGE,
                isset($_POST['a2wl_' . Settings::SETTING_SHIPPING_ON_PRODUCT_PAGE])
            );

            if (isset($_POST['a2wl_' . Settings::SETTING_SHIPPING_ON_PRODUCT_PAGE])) {
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
            "deliveryInfoDisplayModes" => $this->getDeliveryInfoDisplayModes(),
            "shippingOnProductPage" => get_setting(Settings::SETTING_SHIPPING_ON_PRODUCT_PAGE),
            "deliveryTimeTextFormat" => get_setting(
                Settings::SETTING_DELIVERY_TIME_TEXT_FORMAT,
                Settings::DEFAULTS[Settings::SETTING_DELIVERY_TIME_TEXT_FORMAT]
            ),
            "deliveryTimeFallbackMin" => get_setting(
                Settings::SETTING_DELIVERY_TIME_FALLBACK_MIN,
                Settings::DEFAULTS[Settings::SETTING_DELIVERY_TIME_FALLBACK_MIN]
            ),
            "deliveryTimeFallbackMax" => get_setting(
                Settings::SETTING_DELIVERY_TIME_FALLBACK_MAX,
                Settings::DEFAULTS[Settings::SETTING_DELIVERY_TIME_FALLBACK_MAX]
            ),
            "isDeliveryTimeOnlyMode" =>
                get_setting(Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE) === 'delivery_time_only',
        ];
    }

    private function saveDeliveryInfoDisplayMode(): void
    {
        $allowed = array_keys($this->getDeliveryInfoDisplayModes());
        $value = isset($_POST['a2wl_' . Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE])
            ? sanitize_text_field(wp_unslash($_POST['a2wl_' . Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE]))
            : Settings::DEFAULTS[Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE];

        if (!in_array($value, $allowed, true)) {
            $value = Settings::DEFAULTS[Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE];
        }

        set_setting(Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE, $value);

        if ($value === 'delivery_time_only') {
            set_setting(
                Settings::SETTING_DELIVERY_TIME_TEXT_FORMAT,
                sanitize_text_field($_POST['a2wl_' . Settings::SETTING_DELIVERY_TIME_TEXT_FORMAT] ??
                    Settings::DEFAULTS[Settings::SETTING_DELIVERY_TIME_TEXT_FORMAT])
            );

            set_setting(
                Settings::SETTING_DELIVERY_TIME_FALLBACK_MIN,
                intval($_POST['a2wl_' . Settings::SETTING_DELIVERY_TIME_FALLBACK_MIN] ??
                    Settings::DEFAULTS[Settings::SETTING_DELIVERY_TIME_FALLBACK_MIN])
            );

            set_setting(
                Settings::SETTING_DELIVERY_TIME_FALLBACK_MAX,
                intval($_POST['a2wl_' . Settings::SETTING_DELIVERY_TIME_FALLBACK_MAX] ??
                    Settings::DEFAULTS[Settings::SETTING_DELIVERY_TIME_FALLBACK_MAX])
            );
        }
    }

    /** @return string[] */
    private function getDeliveryInfoDisplayModes(): array
    {
        return [
            'default' => esc_html_x(
                'Full shipping options',
                'Delivery info display mode',
                'ali2woo'
            ),
            'delivery_time_only' => esc_html_x(
                'Delivery time only',
                'Delivery info display mode',
                'ali2woo'
            ),
        ];
    }

}
