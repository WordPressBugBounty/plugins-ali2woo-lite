<?php

/**
 * Description of CommonSettingService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Pages;

class CommonSettingService
{
    public const PARAM_ALLOW_SHOP_MANAGER = 'a2wl_allow_shop_manager';
    public const PARAM_HIDDEN_PAGES = 'a2wl_hidden_pages';


    protected AliexpressRegionRepository $AliexpressRegionRepository;

    public function __construct(AliexpressRegionRepository $AliexpressRegionRepository) {
        $this->AliexpressRegionRepository = $AliexpressRegionRepository;
    }

    public function handle(): void
    {
        settings()->auto_commit(false);
        set_setting('item_purchase_code', isset($_POST['a2wl_item_purchase_code']) ? wp_unslash($_POST['a2wl_item_purchase_code']) : '');

        set_setting('import_language', isset($_POST['a2w_import_language']) ? wp_unslash($_POST['a2w_import_language']) : 'en');
        set_setting(
            SETTINGS::SETTING_ALIEXPRESS_REGION,
            isset($_POST['a2wl_aliexpress_region']) ? wp_unslash($_POST['a2wl_aliexpress_region']) : 'US'
        );

        if (isset($_POST['a2w_local_currency'])) {
            $currency = isset($_POST['a2w_local_currency']) ? wp_unslash($_POST['a2w_local_currency']) : 'USD';
            set_setting('local_currency', $currency);
            update_option('woocommerce_currency', $currency);
        }

        set_setting('default_product_type', isset($_POST['a2wl_default_product_type']) ? wp_unslash($_POST['a2wl_default_product_type']) : 'simple');
        set_setting('default_product_status', isset($_POST['a2wl_default_product_status']) ? wp_unslash($_POST['a2wl_default_product_status']) : 'publish');

        set_setting('delivered_order_status', isset($_POST['a2wl_delivered_order_status']) ? wp_unslash($_POST['a2wl_delivered_order_status']) : '');

        set_setting('tracking_code_order_status', isset($_POST['a2wl_tracking_code_order_status']) ? wp_unslash($_POST['a2wl_tracking_code_order_status']) : '');

        set_setting('placed_order_status', isset($_POST['a2wl_placed_order_status']) ? wp_unslash($_POST['a2wl_placed_order_status']) : '');

        set_setting('currency_conversion_factor', isset($_POST['a2wl_currency_conversion_factor']) ? wp_unslash($_POST['a2wl_currency_conversion_factor']) : '1');
        set_setting('import_product_images_limit', isset($_POST['a2wl_import_product_images_limit']) && intval($_POST['a2wl_import_product_images_limit']) ? intval($_POST['a2wl_import_product_images_limit']) : '');
        set_setting('import_extended_attribute', isset($_POST['a2wl_import_extended_attribute']) ? 1 : 0);

        set_setting('background_import', isset($_POST['a2wl_background_import']) ? 1 : 0);
        set_setting('allow_product_duplication', isset($_POST['a2wl_allow_product_duplication']) ? 1 : 0);
        set_setting('convert_attr_case', isset($_POST['a2wl_convert_attr_case']) ? wp_unslash($_POST['a2wl_convert_attr_case']) : 'original');

        set_setting('remove_ship_from', isset($_POST['a2wl_remove_ship_from']) ? 1 : 0);
        set_setting('default_ship_from', isset($_POST['a2wl_default_ship_from']) ? wp_unslash($_POST['a2wl_default_ship_from']) : 'CN');

        set_setting('use_external_image_urls', isset($_POST['a2wl_use_external_image_urls']));
        set_setting('not_import_attributes', isset($_POST['a2wl_not_import_attributes']));
        set_setting('not_import_description', isset($_POST['a2wl_not_import_description']));
        set_setting('not_import_description_images', isset($_POST['a2wl_not_import_description_images']));

        set_setting('use_random_stock', isset($_POST['a2wl_use_random_stock']));
        if (isset($_POST['a2wl_use_random_stock'])) {
            $min_stock = (!empty($_POST['a2wl_use_random_stock_min']) && intval($_POST['a2wl_use_random_stock_min']) > 0) ? intval($_POST['a2wl_use_random_stock_min']) : 1;
            $max_stock = (!empty($_POST['a2wl_use_random_stock_max']) && intval($_POST['a2wl_use_random_stock_max']) > 0) ? intval($_POST['a2wl_use_random_stock_max']) : 1;

            if ($min_stock > $max_stock) {
                $min_stock = $min_stock + $max_stock;
                $max_stock = $min_stock - $max_stock;
                $min_stock = $min_stock - $max_stock;
            }
            set_setting('use_random_stock_min', $min_stock);
            set_setting('use_random_stock_max', $max_stock);
        }

        set_setting('auto_update', isset($_POST['a2wl_auto_update']));
        
        set_setting('on_not_available_product', isset($_POST['a2wl_on_not_available_product']) ? wp_unslash($_POST['a2wl_on_not_available_product']) : 'trash');
        set_setting('on_not_available_variation', isset($_POST['a2wl_on_not_available_variation']) ? wp_unslash($_POST['a2wl_on_not_available_variation']) : 'trash');
        set_setting('on_new_variation_appearance', isset($_POST['a2wl_on_new_variation_appearance']) ? wp_unslash($_POST['a2wl_on_new_variation_appearance']) : 'add');
        set_setting('on_price_changes', isset($_POST['a2wl_on_price_changes']) ? wp_unslash($_POST['a2wl_on_price_changes']) : 'update');
        set_setting('on_stock_changes', isset($_POST['a2wl_on_stock_changes']) ? wp_unslash($_POST['a2wl_on_stock_changes']) : 'update');
        set_setting('untrash_product', isset($_POST['a2wl_untrash_product']));
        set_setting('email_alerts', isset($_POST['a2wl_email_alerts']));
        set_setting('email_alerts_email', isset($_POST['a2wl_email_alerts_email']) ? wp_unslash($_POST['a2wl_email_alerts_email']) : '');

        set_setting('fulfillment_prefship', isset($_POST['a2w_fulfillment_prefship']) ? wp_unslash($_POST['a2w_fulfillment_prefship']) : 'ePacket');
        set_setting('fulfillment_phone_code', isset($_POST['a2wl_fulfillment_phone_code']) ? wp_unslash($_POST['a2wl_fulfillment_phone_code']) : '');
        set_setting('fulfillment_phone_number', isset($_POST['a2wl_fulfillment_phone_number']) ? wp_unslash($_POST['a2wl_fulfillment_phone_number']) : '');
        set_setting('fulfillment_custom_note', isset($_POST['a2wl_fulfillment_custom_note']) ? wp_unslash($_POST['a2wl_fulfillment_custom_note']) : '');
        set_setting('fulfillment_cpf_meta_key', isset($_POST['a2wl_fulfillment_cpf_meta_key']) ? wp_unslash($_POST['a2wl_fulfillment_cpf_meta_key']) : '');
        set_setting('fulfillment_rut_meta_key', isset($_POST['a2wl_fulfillment_rut_meta_key']) ? wp_unslash($_POST['a2wl_fulfillment_rut_meta_key']) : '');

        set_setting('order_translitirate', isset($_POST['a2wl_order_translitirate']));
        set_setting('order_third_name', isset($_POST['a2wl_order_third_name']));

        

        settings()->commit();
        settings()->auto_commit(true);
        
    }

    public function collectModel(): array
    {
        $Localizator = AliexpressLocalizator::getInstance();
        $countryModel  = new Country();
        $languageModel = new Language();

        return [
            'aliexpressRegion' => $this->AliexpressRegionRepository->get(),
            'aliexpressRegions' => $this->AliexpressRegionRepository->getAllWithLabels(),
            'upgradeTariffUrl' => $this->buildUpgradeTariffUrl(),
            'shipping_options' => Utils::get_aliexpress_shipping_options(),
            'currencies' => $Localizator->getCurrencies(false),
            'custom_currencies' => $Localizator->getCurrencies(true),
            'order_statuses' => function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [],
            'shipping_countries' => $countryModel->get_countries(),
            'languages' => $languageModel->get_languages(),

            // Access Control settings
            'isShopManagerAllowed' => get_setting(Settings::SETTING_ALLOW_SHOP_MANAGER, false),
            'hiddenPages' => get_setting(Settings::SETTING_HIDDEN_PAGES, []),
            'menuPages' => $this->getMenuPages(),
        ];
    }

    private function getMenuPages(): array
    {
        $pageLabels = Pages::getLabels();

        //todo: need to move shipping list to own template to support it
        unset($pageLabels[Pages::SHIPPING]);
        unset($pageLabels[Pages::HELP]);

        return $pageLabels;
    }

    private function buildUpgradeTariffUrl(): string
    {
        $url = 'https://ali2woo.com/pricing/';
        $purchaseCode = get_setting('item_purchase_code');

        $urlComponents = [];

        if (!a2wl_check_defined('A2WL_HIDE_KEY_FIELDS') && $purchaseCode){
            $urlComponents[] = 'purchase_code=' . esc_attr($purchaseCode);
        }

        $urlComponents[] = 'utm_source=lite&utm_medium=upgrade&utm_campaign=' . A2WL()->plugin_slug;

        return $url . "?" . implode("&", $urlComponents);
    }
    
}
