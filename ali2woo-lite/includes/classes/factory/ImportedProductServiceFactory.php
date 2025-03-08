<?php
/**
 * Description of ImportedProductFactory
 *
 * @author Ali2Woo Team
 *
 */

namespace AliNext_Lite;;

use WC_Product;

class ImportedProductServiceFactory
{
    public function createFromProduct(WC_Product $Product): ImportedProductService
    {
        return new ImportedProductService($Product);
    }

    /**
     * @throws FactoryException
     */
    public function createFromWcProductId(int $wcProductId): ImportedProductService
    {
        $WC_Product = wc_get_product($wcProductId);
        if (!$WC_Product) {
            throw new FactoryException('Could not find product with ID ' . $wcProductId);
        }

        return $this->createFromProduct($WC_Product);
    }
}
