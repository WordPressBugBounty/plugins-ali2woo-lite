<?php

/**
 * Description of AbstractConnector
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Exception;

abstract class AbstractConnector {
    private static $_instances = [];

    public static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class();
        }
        return self::$_instances[$class];
    }

    /**
     * @param string $product_id
     * @param array $params
     * @return array
     * @throws Exception
    */
    abstract public function load_product(string $product_id, array $params = []): array;
    abstract public function load_products(array $filter, $page = 1, $per_page = 20, $params = []);
    abstract public function load_store_products($filter, $page = 1, $per_page = 20, $params = []);
    abstract public function load_reviews($product_id, $page, $page_size = 20, $params = []);
    abstract public function check_affiliate($product_id): array;

    /**
     * @param string $product_id
     * @param int $quantity
     * @param string $country_code
     * @param string $country_code_from
     * @param string $min_price
     * @param string $max_price
     * @param string $province
     * @param string $city
     * @param string $extra_data
     * @param string $sku_id
     * @return array
     * @throws Exception
     */
    abstract public function load_shipping_info(
        string $product_id, int $quantity, string $country_code, string $country_code_from = 'CN',
        string $min_price = '', string $max_price = '', string $province = '', string $city = '',
        string $extra_data = '', string $sku_id = ''
    ): array;

    /**
     * @param ExternalOrder $ExternalOrder
     * @param string $currencyCode
     * @return array
     * @throws Exception
     */
    abstract public function placeOrder(ExternalOrder $ExternalOrder, string $currencyCode): array;

    /**
     * @param string $order_id
     * @return array
     * @throws Exception
     */
    abstract public function load_order(string $order_id): array;

    /**
     * @param int $categoryId
     * @return array
     * @throws Exception
     */
    abstract public function loadCategory(int $categoryId): array;
    abstract static function get_images_from_description(array $data);
}
