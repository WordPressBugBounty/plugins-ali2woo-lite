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
        $def_phone_number = get_setting('fulfillment_phone_number');
        $def_phone_code = get_setting('fulfillment_phone_code');

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
            'mobile' => $def_phone_number !== "" ? $def_phone_number : $this->get_phone($order),
            'mobile_code' => $def_phone_code !== "" ? $def_phone_code : '',
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
        if (WC()->version < '3.0.0') {
            $result = $order->billing_phone ?: $order->shipping_phone;
        } else {
            $result = $order->get_billing_phone();
        }

        return preg_replace('/[^0-9]+/', '', $result);
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

    private function get_country_region($order)
    {
        if (WC()->version < '3.0.0') {
            $result = $order->shipping_country ? $this->format_field_country($order->shipping_country) : $this->format_field_country($order->billing_country);
        } else {
            $result = $order->get_shipping_country() ? $this->format_field_country($order->get_shipping_country()) : $this->format_field_country($order->get_billing_country());
        }

        return $this->translitirate($result);
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
