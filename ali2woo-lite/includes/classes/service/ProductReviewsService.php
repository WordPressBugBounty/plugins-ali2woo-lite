<?php

/**
 * Description of ProductService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Throwable;

class ProductReviewsService
{
    protected Review $ReviewModel;
    protected Woocommerce $WoocommerceModel;
    protected PurchaseCodeInfoService $PurchaseCodeInfoService;
    protected SynchronizePurchaseCodeInfoService $SynchronizePurchaseCodeInfoService;

    public function __construct(
        Review $ReviewModel, Woocommerce $WoocommerceModel,
        PurchaseCodeInfoService $PurchaseCodeInfoService,
        SynchronizePurchaseCodeInfoService $SynchronizePurchaseCodeInfoService
    ) {
        $this->ReviewModel = $ReviewModel;
        $this->WoocommerceModel = $WoocommerceModel;
        $this->PurchaseCodeInfoService = $PurchaseCodeInfoService;
        $this->SynchronizePurchaseCodeInfoService = $SynchronizePurchaseCodeInfoService;
    }

    /**
     * Load more reviews for products based on the oldest last update date
     * This function is intended to be used in the automatic product review update flow.
     *
     * @return void
     */
    public function loadReviewsForOldestUpdatedProducts(): void
    {
        $this->SynchronizePurchaseCodeInfoService->runSyncPurchaseCodeInfoProcess();
        $allowAutoUpdate = $this->PurchaseCodeInfoService->checkAutoUpdateMaxQuota();

        if (!$allowAutoUpdate) {
          return;
        }

        $productIds = $this->WoocommerceModel->get_sorted_products_ids("_a2w_reviews_last_update", 20);
        foreach ($productIds as $id) {
            $this->ReviewModel->load($id, false);
        }
    }

    /**
     * Load more reviews for given product ids and return error count
     * @param array $ids
     * @return int
     */
    public function loadReviewsForProductIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $this->SynchronizePurchaseCodeInfoService->runSyncPurchaseCodeInfoProcess();

        $error = 0;
        foreach ($ids as $productId) {
            $external_id = $this->WoocommerceModel->get_product_external_id($productId);
            if ($external_id) {
                try {
                    $this->ReviewModel->load($productId, true);
                } catch (Throwable $e) {
                    a2wl_print_throwable($e);
                    $error++;
                }
            } else {
                $error++;
            }
        }

        return $error;
    }

}
