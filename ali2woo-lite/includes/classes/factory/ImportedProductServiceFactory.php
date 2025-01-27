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
}
