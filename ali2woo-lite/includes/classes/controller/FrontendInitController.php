<?php

/**
 * Description of FrontendInitController
 *
 * @author Ali2Woo Team
 *
 * @autoload: init
 *
 * @ajax: true
 */

// phpcs:ignoreFile WordPress.Security.NonceVerification.Recommended

namespace AliNext_Lite;;

use WC_Customer;
use WC_Shipping_Rate;
use WC_Tax;

class FrontendInitController extends AbstractController
{
    protected WoocommerceService $WoocommerceService;
    protected ImportedProductServiceFactory $ImportedProductServiceFactory;
    protected ProductService $ProductService;


    public function __construct(
        WoocommerceService $WoocommerceService,
        ImportedProductServiceFactory $ImportedProductServiceFactory,
        ProductService $ProductService,
    ) {
        parent::__construct();

        $this->WoocommerceService = $WoocommerceService;
        $this->ImportedProductServiceFactory = $ImportedProductServiceFactory;
        $this->ProductService = $ProductService;

        add_action('wp_ajax_a2wl_frontend_load_shipping_info', [$this, 'ajax_frontend_load_shipping_info']);
        add_action('wp_ajax_nopriv_a2wl_frontend_load_shipping_info', [$this, 'ajax_frontend_load_shipping_info']);

        add_filter('wcml_multi_currency_ajax_actions', 'add_action_to_multi_currency_ajax', 10, 1);

        add_action('wp_ajax_a2wl_frontend_update_shipping_list', [$this, 'ajax_frontend_update_shipping_list']);
        add_action('wp_ajax_nopriv_a2wl_frontend_update_shipping_list', [$this, 'ajax_frontend_update_shipping_list']);

        if (get_setting('aliship_frontend')) {
            add_action(
                'wp_ajax_a2wl_update_shipping_method_in_cart_item',
                [$this, 'ajax_frontend_update_shipping_method_in_cart_item']
            );
            add_action(
                'wp_ajax_nopriv_a2wl_update_shipping_method_in_cart_item',
                [$this, 'ajax_frontend_update_shipping_method_in_cart_item']
            );

            if (get_setting('aliship_product_enable')) {
                add_action('woocommerce_add_to_cart_validation', array($this, 'product_shipping_fields_validation'), 10, 3);
                add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 2);

                //set country to the last added (to cart) item
                add_action('woocommerce_add_to_cart', array($this, 'set_default_cart_country'), 10, 6);
            }
        }

        if (get_setting('aliship_frontend')) {

            //calculate shipping total in the cart and checkout page
            add_filter('woocommerce_package_rates', [$this, 'woocommerce_package_rates'], 10, 2);
        }

        //this hook is fired on frontend and backend.
        //show shipping information on the order edit page (admin) and do not show on frontend for user (complete order page)
        //also do not show in the customers emails
        add_filter('woocommerce_order_item_get_formatted_meta_data',
            [$this, 'woocommerce_order_item_get_formatted_meta_data'], 10, 2
        );

    }

    function add_action_to_multi_currency_ajax( $ajax_actions ) {
        $ajax_actions[] = 'a2wl_frontend_load_shipping_info'; // Add a AJAX action to the array            
        return $ajax_actions;
    }

    /**
     * Get shipping info for shipping popup or drop-down selection on the detailed product page
     * @return void
     */
    public function ajax_frontend_load_shipping_info(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (empty($_POST['id']) || intval($_POST['id']) < 0) {
            echo wp_json_encode(ResultBuilder::buildError("Missing product ID..."));
        }

        if (!empty($_POST['variation_id']) && intval($_POST['variation_id']) <= 0) {
            echo wp_json_encode(ResultBuilder::buildError("Illegal product variation ID..."));
        }

        $countryToCode = isset($_POST['country']) ? wc_clean(wp_unslash($_POST['country'])) : "";

        if (!$countryToCode) {
            echo wp_json_encode(ResultBuilder::buildError("Country is required."));
            wp_die();
        }

        $wcProductId = intval($_POST['id']);
        $wcVariationId = null;
        if (!empty($_POST['variation_id']) && intval($_POST['variation_id']) > 0) {
            $wcVariationId = intval($_POST['variation_id']);
        }

        $countries = Shipping::get_countries();

        $country_label = $countries[$countryToCode];

        $page = "cart";

        if (isset($_POST['page'])) {
            if ($_POST['page'] == "product") {
                $page = "product";
            }
        }

        if ($page == "product" && get_setting('aliship_not_available_remove')) {
            //if this product page and option is enabled we use a separate not available message
            $shipping_info = str_replace(
                '{country}', $country_label, get_setting('aliship_product_not_available_message')
            );

        } else {
            $shipping_info = Shipping::get_not_available_shipping_message($country_label);
        }

        $normalized_methods = [];

        $type = "select";

        if (isset($_POST['type'])) {
            if ($_POST['type'] == "popup") {
                $type = "popup";
            }
        }

        $WC_ProductOrVariation = wc_get_product($wcProductId);
        if (!$WC_ProductOrVariation) {
            echo wp_json_encode(ResultBuilder::buildError("Bad product ID"));
            wp_die();
        }

        if ($wcVariationId) {
            $WC_ProductOrVariation = wc_get_product($wcVariationId);
            if (!$WC_ProductOrVariation) {
                echo wp_json_encode(ResultBuilder::buildError("Bad product variation ID"));
                wp_die();
            }
        }

        try {
            $countryFromCode = $this->WoocommerceService->getShippingFromByProduct($WC_ProductOrVariation);
            $importedProduct = $this->WoocommerceService->updateProductShippingItems(
                $WC_ProductOrVariation,
                $countryToCode,
                $countryFromCode
            );

            $shippingItems = $this->ProductService->getShippingItems(
                $importedProduct, $countryToCode, $countryFromCode
            );
        } catch (RepositoryException $RepositoryException) {
            a2wl_error_log($RepositoryException->getMessage());

            echo wp_json_encode(ResultBuilder::buildError(
                'Can`t get product shipping')
            );

            wp_die();
        } catch (ServiceException $ServiceException) {
            a2wl_error_log($ServiceException->getMessage());
            $shippingItems = [];
        }

        foreach ($shippingItems as $method) {
            $normalized_method = Shipping::get_normalized($method, $countryToCode, $type);
            if (!$normalized_method) {
                continue;
            }
            $normalized_methods[] = $normalized_method;
        }

        echo wp_json_encode(ResultBuilder::buildOk([
            'products' => [
                'product_id' => $WC_ProductOrVariation->get_id(),
                'default_method' => $importedProduct[ImportedProductService::FIELD_METHOD] ?? '',
                'items' => $normalized_methods,
                'shipping_cost' => $importedProduct[ImportedProductService::FIELD_COST] ?? '',
            ],
            'shipping_info' => $shipping_info
        ]));

        wp_die();
    }

    public function ajax_frontend_update_shipping_list(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (isset($_POST['items'])) {
            foreach ($_POST['items'] as $ship_way) {
                if (empty($ship_way['company']) || empty($ship_way['serviceName'])) {
                    continue;
                }

                $item = ShippingPostType::get_item($ship_way['company']);

                // skip disabled items
                if ($item === false) {
                    continue;
                }

                // if no such item yet, let`s add it and then get it
                if (!$item) {
                    ShippingPostType::add_item($ship_way['company'], $ship_way['serviceName']);
                }
            }
        }

        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }

    public function ajax_frontend_update_shipping_method_in_cart_item(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        $cart_item_key = $_POST['id'];
        $tariff_code = $_POST['value'];

        if (isset(WC()->cart->cart_contents[$cart_item_key])) {
            WC()->cart->cart_contents[$cart_item_key]['a2wl_shipping_method'] = $tariff_code;
        } else {
            $result = ResultBuilder::buildError('No cart item with given key in the cart!');

            echo wp_json_encode($result);
            wp_die();
        }

        //reset shipping post code field
        //todo: remove?
        if (!isset($_POST['calc_shipping_postcode'])) {
            $_POST['calc_shipping_postcode'] = '';
        }

        // Update country in user meta.
        //todo: remove?
        $customer_id = apply_filters('woocommerce_checkout_customer_id', get_current_user_id());
        if ($customer_id && !empty($_POST['calc_shipping_country'])) {
            $customer = new WC_Customer($customer_id);
            $customer->set_shipping_country(strval($_POST['calc_shipping_country']));
            $customer->save();
        }

        //Update shipping & totals in the cart ( and in checkout?)

        //reset shipping rates
        $packages = WC()->cart->get_shipping_packages();
        foreach ($packages as $package_key => $package) {
            WC()->session->set('shipping_for_package_' . $package_key, false);
        }

        WC()->cart->calculate_totals();

        WC()->cart->calculate_shipping();

        $result = ResultBuilder::buildOk();

        echo wp_json_encode($result);
        wp_die();
    }

    private function get_plugin_data_from_request()
    {
        $data = ['a2wl_shipping_method_field' => false, 'a2wl_to_country_field'=> false];
        if (isset($_REQUEST['a2wl_shipping_method_field']) && !empty($_REQUEST['a2wl_shipping_method_field'])){
            $data['a2wl_shipping_method_field'] = $_REQUEST['a2wl_shipping_method_field'];
        } elseif (isset($_REQUEST['data']['a2wl_shipping_method_field']) && !empty($_REQUEST['data']['a2wl_shipping_method_field'])){
            $data['a2wl_shipping_method_field'] = $_REQUEST['data']['a2wl_shipping_method_field'];
        }

        if (isset($_REQUEST['a2wl_to_country_field']) && !empty($_REQUEST['a2wl_to_country_field'])){
            $data['a2wl_to_country_field'] = $_REQUEST['a2wl_to_country_field'];
        } elseif (isset($_REQUEST['data']['a2wl_to_country_field']) && !empty($_REQUEST['data']['a2wl_to_country_field'])){
            $data['a2wl_to_country_field'] = $_REQUEST['data']['a2wl_to_country_field'];
        }

        return $data;
    }

    public function product_shipping_fields_validation($passed, $product_id, $quantity)
    {

        $external_id = get_post_meta($product_id, '_a2w_external_id', true);

        if ($external_id) {

            $plugin_request_data = $this->get_plugin_data_from_request();

            if ($plugin_request_data['a2wl_shipping_method_field'] === false) {
                wc_add_notice(esc_html__('Please select the shipping method', 'woocommerce'), 'error');
                $passed = false;
            }

            if ($plugin_request_data['a2wl_to_country_field'] === false) {
                wc_add_notice(esc_html__('Please select the country where you would like your items to be delivered', 'woocommerce'), 'error');
                $passed = false;
            }

        }

        return $passed;
    }

    public function add_cart_item_data($cart_item_meta, $product_id)
    {
        $plugin_request_data = $this->get_plugin_data_from_request();

        if ($plugin_request_data['a2wl_shipping_method_field'] !== false) {
            $cart_item_meta['a2wl_shipping_method'] =  $plugin_request_data['a2wl_shipping_method_field'];
        }

        if ($plugin_request_data['a2wl_to_country_field'] !== false) {
            $cart_item_meta['a2wl_to_country'] = $plugin_request_data['a2wl_to_country_field'];
        }

        return $cart_item_meta;
    }

    public function set_default_cart_country($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
    {

        if (isset($cart_item_data['a2wl_to_country'])) {
            $country = $cart_item_data['a2wl_to_country'];

            WC()->customer->set_billing_country($country);
            WC()->customer->set_shipping_country($country);

            //it forces the shipping calculator to update its country field
            WC()->customer->set_calculated_shipping(true);
        }

    }

    /**
     * Handle works on shipping address update in the cart and checkout
     * or when click on 'update cart' button
     *
     * @param $methods
     * @param $package
     * @return WC_Shipping_Rate[]
     */
    public function woocommerce_package_rates($methods, $package): array
    {
        if (!empty($package['contents'])) {
            $ali_shipping_type = get_setting('aliship_shipping_type', 'new');
            $countryToCode = $package["destination"]["country"];
            $default_tariff_code = get_setting('fulfillment_prefship', 'EMS_ZX_ZX_US'); //ePacket

            $ali_total_shipping = 0;
            $ali_shipping = false;
            $items_in_package = [];

            $remove_cart_item = get_setting('aliship_not_available_remove');
            $not_available_cost = get_setting('aliship_not_available_cost');

            foreach ($package['contents'] as $cart_item_key => $cart_item) {
                $WC_Product = $cart_item['data'];
                $quantity = $cart_item['quantity'];


                $importedProductService = $this->ImportedProductServiceFactory->createFromProduct($WC_Product);
                $externalId = $importedProductService->getExternalId();

                if ($externalId) {
                    $ali_shipping = true;

                    $countryFromCode = 'CN';
                    try {
                        $importedProduct = $this->WoocommerceService
                            ->updateProductShippingItems($WC_Product, $countryToCode, $countryFromCode, $quantity);
                    } catch (RepositoryException|ServiceException $Exception) {
                        a2wl_error_log($Exception->getMessage());

                        return $methods;
                    }

                    $shippingItems = $this->ProductService->getShippingItems(
                        $importedProduct, $countryToCode, $countryFromCode
                    );

                    $normalized_methods = [];

                    if (!empty($shippingItems)) {
                        $shipping_methods = $shippingItems;

                        if (!empty($shipping_methods)) {

                            $search_tariff_code = $cart_item['a2wl_shipping_method'] ?? $default_tariff_code;

                            $min_method = false;

                            foreach ($shipping_methods as $method) {

                                $normalized_method = Shipping::get_normalized($method, $countryToCode);

                                if (!$normalized_method) {
                                    continue;
                                }

                                $normalized_methods[$method['serviceName']] = $normalized_method;

                                if (!$min_method || $normalized_method['price'] < $min_method['price']) {
                                    $min_method = $normalized_method;
                                }
                            }

                            if (isset($normalized_methods[$search_tariff_code])) {
                                $chosen_method = $normalized_methods[$search_tariff_code];
                                $ali_total_shipping += $chosen_method['price'];
                            } else {
                                $chosen_method = $min_method;
                                $ali_total_shipping += $chosen_method['price'];
                            }

                            //save method cost to show in the future order
                            WC()->cart->cart_contents[$cart_item_key]['a2wl_shipping_info'] = array(
                                'company' => $chosen_method['company'],
                                'service_name' => $chosen_method['serviceName'],
                                'delivery_time' => $chosen_method['time'],
                                'shipping_cost' => $chosen_method['price']);

                            $items_in_package[] = $WC_Product->get_name() . ' &times; ' . $cart_item['quantity'];

                        } else {

                            //if product can't be delivered to the country
                            if (!$remove_cart_item && $not_available_cost) {

                                $ali_total_shipping += $not_available_cost;
                            }
                        }

                    } else {

                        //todo: it looks like rudiment condition shipping_info can't be false
                        //function can't come here, remove?

                        if (!$remove_cart_item && $not_available_cost) {
                            $ali_total_shipping += $not_available_cost;
                        }

                    }

                }
            }

            if ($ali_shipping && $ali_shipping_type !== 'none') {
                if ($ali_total_shipping) {
                    $id = 'flat_rate';
                    $label = get_setting('aliship_shipping_label');
                    if (!$label) {
                        $label = esc_html__('Shipping', 'ali2woo');
                    }
                } else {
                    $id = 'free_shipping';
                    $label = get_setting('aliship_free_shipping_label');
                    if (!$label) {
                        $label = esc_html__('Free Shipping', 'ali2woo');
                    }
                }

                switch ($ali_shipping_type) {
                    case 'new':
                        /*Create a new shipping method but still show other available shipping methods*/
                        $taxes = WC_Tax::calc_shipping_tax($ali_total_shipping, WC_Tax::get_shipping_tax_rates());
                        $methods[$id] = new WC_Shipping_Rate($id, $label, $ali_total_shipping, $taxes, $id, '');
                        if (count($items_in_package)) {
                            $methods[$id]->add_meta_data(esc_html__('Items', 'woocommerce'), implode(', ', $items_in_package));
                        }
                        break;
                    case 'new_only':
                        /*Create a new shipping method and make it the only available shipping method*/
                        $taxes = WC_Tax::calc_shipping_tax($ali_total_shipping, WC_Tax::get_shipping_tax_rates());
                        $methods = array($id => new WC_Shipping_Rate($id, $label, $ali_total_shipping, $taxes, $id, ''));
                        if (count($items_in_package)) {
                            $methods[$id]->add_meta_data(esc_html__('Items', 'woocommerce'), implode(', ', $items_in_package));
                        }
                        break;
                    case 'add':
                        /*Add shipping cost to all available shipping methods*/
                        if ($ali_total_shipping) {
                            foreach ($methods as $rate_k => $rate) {
                                if (is_a($rate, 'WC_Shipping_Rate') && $rate && $rate->get_method_id() !== 'free_shipping') {
                                    $cost = $rate->get_cost() + $ali_total_shipping;
                                    $taxes = WC_Tax::calc_shipping_tax($cost, WC_Tax::get_shipping_tax_rates());
                                    $methods[$rate_k]->set_cost($cost);
                                    $methods[$rate_k]->set_taxes($taxes);
                                }
                            }
                        }
                        break;
                    default:
                }

            } else {
                //if shipping calculation is none, do not calculate shipping
                // just keep shipping information saved in the cart item meta (earlier)

                if (!count($methods)) {

                    //we need at least one method if use doesn't create any method in woocommerce

                    $id = 'free_shipping';
                    $label = get_setting('aliship_free_shipping_label');
                    if (!$label) {
                        $label = esc_html__('Free Shipping', 'ali2woo');
                    }

                    $taxes = WC_Tax::calc_shipping_tax(0, WC_Tax::get_shipping_tax_rates());
                    $methods[$id] = new WC_Shipping_Rate('free_shipping', $label, 0, $taxes, $id, '');
                }
            }
        }

        return $methods;
    }

    public function woocommerce_order_item_get_formatted_meta_data($formatted_meta, $item)
    {
        if (!is_admin() || isset($_POST['a2wl_email_template_check'])) {
            $user_formatted_meta = array();

            foreach ($formatted_meta as $formatted_item) {
                if ($formatted_item->key === Shipping::get_order_item_shipping_meta_key()) {
                    continue;
                }

                $user_formatted_meta[$formatted_item->key] = $formatted_item;
            }

            if (!empty($user_formatted_meta)) {
                $formatted_meta = $user_formatted_meta;
            }
        }

        return $formatted_meta;
    }

}
