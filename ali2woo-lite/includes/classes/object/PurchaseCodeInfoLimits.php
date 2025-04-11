<?php

/**
 * Description of PurchaseCodeInfoLimits
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class PurchaseCodeInfoLimits
{
    public const FIELD_ALL = 'all';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_PRODUCT  = 'product';
    public const FIELD_PRODUCTS = 'products';
    public const FIELD_REVIEWS = 'reviews';
    public const FIELD_CATEGORY = 'category';
    public const FIELD_SHIPPING = 'shipping';
    public const FIELD_SYNC_PRODUCT = 'sync_product';
    public const FIELD_ORDERS = 'orders';
    public const FIELD_SITES = 'sites';


    private int $all;
    private int $description;
    private int $product;
    private int $products;
    private int $reviews;
    private int $category;
    private int $shipping;
    private int $syncProduct;
    private int $orders;
    private int $sites;

    public function getAll(): int
    {
        return $this->all;
    }

    public function setAll(int $all): self
    {
        $this->all = $all;

        return $this;
    }

    public function getDescription(): int
    {
        return $this->description;
    }

    public function setDescription(int $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getProduct(): int
    {
        return $this->product;
    }

    public function setProduct(int $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProducts(): int
    {
        return $this->products;
    }

    public function setProducts(int $products): self
    {
        $this->products = $products;

        return $this;
    }

    public function getReviews(): int
    {
        return $this->reviews;
    }

    public function setReviews(int $reviews): self
    {
        $this->reviews = $reviews;

        return $this;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    public function setCategory(int $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getShipping(): int
    {
        return $this->shipping;
    }

    public function setShipping(int $shipping): self
    {
        $this->shipping = $shipping;

        return $this;
    }

    public function getSyncProduct(): int
    {
        return $this->syncProduct;
    }

    public function setSyncProduct(int $syncProduct): self
    {
        $this->syncProduct = $syncProduct;

        return $this;
    }

    public function getOrders(): int
    {
        return $this->orders;
    }

    public function setOrders(int $orders): self
    {
        $this->orders = $orders;

        return $this;
    }

    public function getSites(): int
    {
        return $this->sites;
    }

    public function setSites(int $sites): self
    {
        $this->sites = $sites;

        return $this;
    }

    public function toArray(): array
    {
        return [
            self::FIELD_ALL => $this->getAll(),
            self::FIELD_DESCRIPTION => $this->getDescription(),
            self::FIELD_PRODUCT => $this->getProduct(),
            self::FIELD_PRODUCTS => $this->getProducts(),
            self::FIELD_REVIEWS => $this->getReviews(),
            self::FIELD_CATEGORY => $this->getCategory(),
            self::FIELD_SHIPPING => $this->getShipping(),
            self::FIELD_SYNC_PRODUCT => $this->getSyncProduct(),
            self::FIELD_ORDERS => $this->getOrders(),
            self::FIELD_SITES => $this->getSites(),
        ];
    }
}
