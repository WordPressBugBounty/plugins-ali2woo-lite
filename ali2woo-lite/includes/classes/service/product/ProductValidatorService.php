<?php

/**
 * Description of ProductValidatorService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use wpdb;

class ProductValidatorService
{
    protected wpdb $wpdb;

    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * Check if a product is already imported.
     */
    public function isDuplicate(string $externalProductId): bool
    {
        $existingId = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key = '_a2w_external_id' AND meta_value = %s LIMIT 1",
                $externalProductId
            )
        );

        return !empty($existingId);
    }

    /**
     * Check if product is allowed for import.
     */
    public function isAllowedToImport(string $externalProductId): bool
    {
        if (get_setting('allow_product_duplication')) {
            return true;
        }

        return !$this->isDuplicate($externalProductId);
    }

    /**
     * Check if product structure looks valid.
     */
    public function isValidStructure(array $product): bool
    {
        return isset($product['id'], $product['sku_products']['variations']) && is_array($product['sku_products']['variations']);
    }
}