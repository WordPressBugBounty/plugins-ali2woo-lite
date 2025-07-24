<?php

/**
 * Description of ProductSelectorService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ProductSelectorService
{
    public const PRIORITY_RESULT_FIRST = 'result_first';
    public const PRIORITY_STORE_FIRST  = 'store_first';

    /**
     * Lookup product by external ID, honoring source priority.
     *
     * @param string $externalProductId
     * @param string $priority
     * @return array
     */
    public function getByExternalId(string $externalProductId, string $priority = self::PRIORITY_RESULT_FIRST): array
    {
        $sources = $priority === self::PRIORITY_STORE_FIRST
            ? ['a2wl_search_store_result', 'a2wl_search_result']
            : ['a2wl_search_result', 'a2wl_search_store_result'];

        foreach ($sources as $key) {
            $products = a2wl_get_transient($key);
            if (is_array($products)) {
                foreach ($products as $product) {
                    if ($product['id'] == $externalProductId) {
                        //a2wl_info_log("Product found in transient source: $key for ID: $externalProductId");
                        return $product;
                    }
                }
            }
        }

        //a2wl_info_log("Product not found in transient sources for ID: $externalProductId");
        return [];
    }
}
