<?php
namespace AliNext_Lite;;

/**
 * Description of WoocommerceCategoryService
 *
 * @author Ali2Woo Team
 */

class WoocommerceCategoryService
{
    protected Aliexpress $AliexpressModel;
    protected Woocommerce $WoocommerceModel;

    public function __construct(
        Aliexpress $AliexpressModel, Woocommerce $WoocommerceModel
    ) {
        $this->AliexpressModel = $AliexpressModel;
        $this->WoocommerceModel = $WoocommerceModel;
    }


    /**
     * @throws ApiException
     */
    public function loadAliexpressCategory(int $aliexpressCategoryId): array
    {
        $categoryDtos = $this->AliexpressModel->loadCategory($aliexpressCategoryId);
        $categoryDtos = $this->sortCategoriesByParentId($categoryDtos);

        $insertedCategories = [];
        $categoryIdMap = [];

        foreach ($categoryDtos as $categoryDto) {
            $wcParentCategoryId = 0;
            if ($categoryDto->getParentId() > 0) {
                if (!isset($categoryIdMap[$categoryDto->getParentId()])) {
                    $errorText = sprintf(
                        'WoocommerceCategoryService::loadAliexpressCategory() returned parent ID: %d does not exist in category map.',
                        $categoryDto->getParentId());
                    a2wl_error_log($errorText);
                    continue;
                }
                $wcParentCategoryId = $categoryIdMap[$categoryDto->getParentId()];
            }
            $wcNewCategoryId = $this->WoocommerceModel->addCategory($categoryDto->getName(), $wcParentCategoryId);
            $categoryIdMap[$categoryDto->getId()] = $wcNewCategoryId;
            $insertedCategories[] = $wcNewCategoryId;
        }

        return $insertedCategories;
    }

    /**
     * @param array|AliexpressCategoryDto[] $categories
     * @return array|AliexpressCategoryDto[]
     */
    private function sortCategoriesByParentId(array $categories): array
    {
        usort($categories, function($a, $b) {
            // If both have the same parent_id, maintain current order
            if ($a->getParentId() === $b->getParentId()) {
                return 0;
            }

            // Compare parent_ids; null or 0 should be considered first
            return ($a->getParentId() === 0) ? -1 : (($b->getParentId() === 0) ? 1 :
                ($a->getParentId() <=> $b->getParentId()));
        });

        return $categories;
    }


}
