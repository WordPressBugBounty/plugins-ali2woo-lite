<?php

/**
 * Builds order shipping data for external API.
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use WC_Order;
use WooCommerceOrderItem;

class OrderShippingDataService
{
    public function buildOrderContent(WC_Order $order, int $postId): array
    {
        $def_prefship = get_setting('fulfillment_prefship');
        $def_customer_note = get_setting('fulfillment_custom_note');

        $content = array(
            'id' => $postId,
            'defaultShipping' => $def_prefship,
            'note' => $def_customer_note !== "" ? $def_customer_note : $this->get_customer_note($order),
            'products' => array(),
            'countryRegion' => $this->get_country_region($order),
            'region' => strtolower($this->get_region($order)),
            'city' => $this->get_city($order),
            'contactName' => $this->get_contactName($order),
            'address1' => $this->get_address1($order),
            'address2' => $this->get_address2($order),
            'mobile' => $this->get_phone($order),
            'mobile_code' => $this->resolveShippingPhoneCode($order),
            'zip' => $this->get_zip($order),
            'autopay' => false,
            'awaitingpay' => false,
            'cpf' => $this->get_cpf($order),
            'storeurl' => get_site_url(),
            'currency' => $this->get_currency($order),
        );

        $items = $order->get_items();

        $k = 0;
        $total = 0;
        foreach ($items as $item) {

            $normalized_item = new WooCommerceOrderItem($item);
            $product_id = $normalized_item->get_product_id();
            $variation_id = $normalized_item->get_variation_id();
            $quantity = $normalized_item->get_quantity();

            $external_id = get_post_meta($product_id, '_a2w_external_id', true);

            if ($external_id) {

                $skuArray = $this->getSkuArray($normalized_item);

                if (empty($skuArray) && $variation_id && $variation_id > 0) {
                    throw new \RuntimeException('', -2);
                }

                $original_url = get_post_meta($product_id, '_a2w_product_url', true);

                if (empty($original_url)) {
                    throw new \RuntimeException('', -3);
                }

                $shipping_service_name = $normalized_item->get_ali_shipping_code();

                $content['products'][$k] = array(
                    'url' => $original_url,
                    'productId' => $external_id,
                    'originalId' => $product_id,
                    'qty' => $quantity,
                    'sku' => $skuArray,
                    'shipping' => $shipping_service_name,
                );

                $k++;
            }

            $total++;
        }

        if ($k < 1) {
            throw new \RuntimeException('', -4);
        }

        $action = $k === $total ? 'upd_ord_status' : '';

        return array('state' => 'ok', 'data' => array('content' => $content, 'id' => $postId), 'action' => $action);
    }

    private function format_field($str): string
    {
        $str = trim($str);

        if (!empty($str)) {
            $str = ucwords(strtolower($str));
        }

        return $str;
    }

    private function get_currency($order): string
    {
        return strtolower($order->get_currency());
    }

    private function get_cpf($order)
    {
        $b_cpf = $order->get_meta('_billing_cpf');
        $s_cpf = $order->get_meta('_shipping_cpf');

        $cpf = $b_cpf ?: ($s_cpf ?: '');

        return $cpf ? preg_replace("/[^0-9]/", "", $cpf) : '';
    }

    private function get_phone($order)
    {
        return preg_replace('/[^0-9]+/', '', $this->resolveShippingPhone($order));
    }

    private function get_customer_note($order)
    {
        if (WC()->version < '3.0.0') {
            $result = $order->customer_note;
        } else {
            $result = $order->get_customer_note();
        }

        return $this->translitirate($result);
    }

    /**
     * Resolve the destination country for an order.
     *
     * Falls back through the order shipping address, then the billing
     * address, and finally to the "Default Shipping Country" setting.
     *
     * @param WC_Order $order
     * @return string Uppercased WooCommerce country code.
     */
    public function resolveShippingCountry(WC_Order $order): string
    {
        $country = trim((string) $order->get_shipping_country());

        if ($country === '') {
            $country = trim((string) $order->get_billing_country());
        }

        if ($country === '') {
            $country = trim((string) get_setting('aliship_shipto'));
        }

        return strtoupper($country);
    }

    /**
     * Is the "fulfillment phone number" setting active?
     *
     * Override applies only when both the phone number and the phone code
     * are set in the settings.
     *
     * @return bool
     */
    public function isShippingPhoneOverridden(): bool
    {
        return trim((string) get_setting('fulfillment_phone_number', '')) !== ''
            && trim((string) get_setting('fulfillment_phone_code', '')) !== '';
    }

    /**
     * Resolve the phone number to use for an order.
     *
     * Uses the fulfillment phone number from settings when the override is
     * active, otherwise the phone number stored on the order by the plugin
     * (_shipping_phone_number), and finally falls back to the order shipping
     * phone and then to the billing phone.
     *
     * @param WC_Order $order
     * @return string Raw phone number as stored/configured.
     */
    public function resolveShippingPhone(WC_Order $order): string
    {
        if ($this->isShippingPhoneOverridden()) {
            return trim((string) get_setting('fulfillment_phone_number', ''));
        }

        $phone = trim((string) $order->get_meta('_shipping_phone_number'));

        if ($phone === '') {
            $phone = trim((string) $order->get_shipping_phone());
        }

        if ($phone === '') {
            $phone = trim((string) $order->get_billing_phone());
        }

        return $phone;
    }

    /**
     * Resolve the phone number for display in the fulfillment popup.
     *
     * The leading country code is stripped from the number when it matches
     * the resolved phone code, so the code is not shown twice.
     *
     * @param WC_Order $order
     * @return string
     */
    public function resolveShippingPhoneDisplay(WC_Order $order): string
    {
        return Utils::stripLeadingPhoneCode(
            $this->resolveShippingPhone($order),
            $this->resolveShippingPhoneCode($order)
        );
    }

    /**
     * Resolve the phone country code for an order.
     *
     * Falls back through the fulfillment phone code from settings (only when
     * the override is active), then the code stored on the order
     * (_shipping_phone_code), and finally derives it from the order country.
     *
     * @param WC_Order $order
     * @return string Phone country code as configured/stored.
     */
    public function resolveShippingPhoneCode(WC_Order $order): string
    {
        if ($this->isShippingPhoneOverridden()) {
            return trim((string) get_setting('fulfillment_phone_code', ''));
        }

        $orderCode = trim((string) $order->get_meta('_shipping_phone_code'));

        if ($orderCode !== '') {
            return $orderCode;
        }

        return $this->derivePhoneCodeFromCountry($order);
    }

    private function derivePhoneCodeFromCountry(WC_Order $order): string
    {
        $country = trim($this->resolveShippingCountry($order));

        if ($country === '') {
            return '';
        }

        $aliexpressCountry = ProductShippingData::normalize_country($country);

        if ($aliexpressCountry === null || $aliexpressCountry === '') {
            return '';
        }

        return trim((string) Utils::get_phone_country_code($aliexpressCountry));
    }

    private function get_country_region($order)
    {
        return $this->translitirate(
            $this->format_field_country($this->resolveShippingCountry($order))
        );
    }

    private function get_region($order)
    {
        if (WC()->version < '3.0.0') {
            $result = $order->shipping_state ? $this->format_field_state($order->shipping_country, $order->shipping_state) : $this->format_field_state($order->billing_country, $order->billing_state);
        } else {
            $result = $order->get_shipping_state() ? $this->format_field_state($order->get_shipping_country(), $order->get_shipping_state()) : $this->format_field_state($order->get_billing_country(), $order->get_billing_state());
        }

        return $this->translitirate($result);
    }

    private function get_city($order)
    {

        if (WC()->version < '3.0.0') {
            $result = $order->shipping_city ? $this->format_field($order->shipping_city) : $this->format_field($order->billing_city);
        } else {
            $result = $order->get_shipping_city() ? $this->format_field($order->get_shipping_city()) : $this->format_field($order->get_billing_city());
        }

        return $this->translitirate($result);
    }

    private function get_contactName($order)
    {
        if (WC()->version < '3.0.0') {
            if ($order->shipping_first_name) {
                $result = $order->shipping_first_name . ' ' . $order->shipping_last_name;
            } else {
                $result = $order->billing_first_name . ' ' . $order->billing_last_name;
            }

        } else {
            $result2 = $order->get_shipping_first_name() . ' ' .
                       $order->get_shipping_last_name() . ' ' . $order->get_meta('_shipping_third_name');
            $result3 = $order->get_billing_first_name() . ' ' .
                       $order->get_billing_last_name() . ' ' . $order->get_meta('_billing_third_name');
            $result = $order->get_shipping_first_name() ? $result2 : $result3;
        }

        return $this->translitirate($result);
    }

    private function get_address_number($order)
    {
        $b_number = $order->get_meta('_billing_number');
        $s_number = $order->get_meta('_shipping_number');

        $number = $b_number ?: ($s_number ?: '');

        return $number ? preg_replace("/[^0-9]/", "", $number) : '';
    }

    private function get_address1($order)
    {
        if (WC()->version < '3.0.0') {
            $result = $order->shipping_address_1 ?: $order->billing_address_1;
        } else {
            $result = $order->get_shipping_address_1() ?
                $order->get_shipping_address_1() : $order->get_billing_address_1();
        }

        $result = $result . " " . $this->get_address_number($order);

        return $this->translitirate($result);
    }

    private function get_address2($order)
    {
        if (WC()->version < '3.0.0') {
            $result = $order->shipping_address_2 ?: $order->billing_address_2;
        } else {
            $result = $order->get_shipping_address_2() ?
                $order->get_shipping_address_2() : $order->get_billing_address_2();
        }

        return $this->translitirate($result);
    }

    private function get_zip($order)
    {
        if (WC()->version < '3.0.0') {
            $result = $order->shipping_postcode ?: $order->billing_postcode;
        } else {
            $result = $order->get_shipping_postcode() ?
                $order->get_shipping_postcode() : $order->get_billing_postcode();
        }

        return $result;
    }

    private function format_field_country($str): string
    {
        $str = trim($str);

        if (!empty($str)) {
            $str = strtoupper($str);
        }

        if ($str === "GB") {
            $str = "UK";
        }

        if ($str == "RS") {
            $str = "SRB";
        }

        if ($str == "ME") {
            $str = "MNE";
        }

        return $str;
    }

    private function format_field_state($country_code, $state_code): string
    {
        $condition = isset(WC()->countries->states[$country_code]) &&
                     isset(WC()->countries->states[$country_code][$state_code]);
        if ($condition) {
            $result = $this->format_field(WC()->countries->states[$country_code][$state_code]);
        } else {
            $result = $state_code;
        }

        return html_entity_decode($result, ENT_QUOTES, 'UTF-8');
    }

    private function getSkuArray($item): array
    {
        if ($item->get_variation_id() !== 0) {
            $variation_id = $item->get_variation_id();
            $sku = $this->getSkuArrayByVariationID($variation_id);

        } else {
            $product_id = $item->get_product_id();
            $sku = $this->getSkuArrayByVariationID($product_id);
        }
        return $sku;
    }

    private function getSkuArrayByVariationID($variation_id): array
    {
        $sku = array();

        $external_var_data = get_post_meta($variation_id, '_aliexpress_sku_props', true);

        if (empty($external_var_data)) {
            return $sku;
        }

        if ($external_var_data) {
            $items = explode(';', $external_var_data);

            foreach ($items as $item) {
                list(, $sku[]) = explode(':', $item);
            }
        }

        return $sku;
    }

    private function translitirate($result)
    {
        if (get_setting('order_translitirate')) {
            $result = Utils::safeTransliterate($result);
        }

        return $result;
    }
}
