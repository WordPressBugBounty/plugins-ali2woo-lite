<?php

/**
 * Description of ProductImportResultDTO
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ProductImportResultDTO
{
    public function __construct(
        public string $status,
        public ?string $message = null,
        public ?array $product = null
    ) {}
}

