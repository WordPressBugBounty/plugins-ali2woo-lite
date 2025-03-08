<?php

/**
 * Description of ProductShippingDataRepository
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

/**
 * The ProductShippingDataRepository  is designed to manage shipping data for products.
 * It does not store separate shipping information for different product variations;
 * instead, it only tracks changes in shipping costs based on the shipping origin and destination.
 *
 * If an AliExpress seller offers different shipping costs for specific product variations,
 * you should utilize the split product feature to handle these variations appropriately.
 */
class ProductShippingDataRepository
{
    private ProductShippingDataFactory $ProductShippingDataFactory;

    public const META_SHIPPING_DATA = '_a2w_shipping_data';

    public function __construct(ProductShippingDataFactory $ProductShippingDataFactory)
    {
        $this->ProductShippingDataFactory = $ProductShippingDataFactory;
    }

    /**
     * @throws RepositoryException
     */
    public function get(int $productId): ProductShippingData
    {
        $meta = $this->getAsArray($productId);

        return $this->ProductShippingDataFactory->buildFromProductShippingMeta($meta);
    }

    public function save(int $productId, ProductShippingData $ProductShippingData): void
    {
        update_post_meta($productId, self::META_SHIPPING_DATA, $ProductShippingData->toArray());
    }

    public function clear(): void
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key=%s",
            self::META_SHIPPING_DATA
        );

        $wpdb->query($query);
    }

    /**
     * @throws RepositoryException
     */
    private function getAsArray(int $productId): array
    {
        $metaData = get_post_meta($productId, self::META_SHIPPING_DATA, true);

        if ($metaData === false) {
            throw new RepositoryException(
                _x( "A product shipping data with provided id does`t exist.", 'error text', 'ali2woo')
            );
        }

        if (empty($metaData)) {
            $metaData = [];
        }

        if (!isset($metaData[ProductShippingData::FIELD_SHIPPING_INFO])) {
            $metaData[ProductShippingData::FIELD_SHIPPING_INFO] = [];
        }

        return $metaData && is_array($metaData) ? $metaData : [];
    }
}