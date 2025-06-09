<?php

/**
 * Description of PromoService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class PromoService
{
    protected array $promo_data = [];

    public function __construct()
    {
        $this->promo_data = [
            'full_plugin_link' => 'https://ali2woo.com/pricing/?utm_source=lite&utm_medium=plugin_promo&utm_campaign=alinext-lite',
            'promo_image_url' => A2WL()->plugin_url() . '/assets/img/alinext-plugin-box-268.jpg',
            'title' => 'AliNext full version',
            'description' => '<ul style="list-style: decimal;">' .
                '<li><strong>All features from the free version:</strong> All your settings/imported products/fulfilled orders are remained after upgrade</li>' .
                '<li><strong>Premium support and updates</strong></li>' .
                '<li><strong>Increased daily usage quota:</strong> Instead of 100 quota, you will get 500 or 5,000, or 50,000 depending on AliNext (Lite version)`s package you order</li>' .
                '<li><strong>Select AliExpress region:</strong> Choose the specific AliExpress region to ensure accurate pricing, stock, and shipping details.</li>' .
                '<li><strong>Import full category tree from Aliexpress</strong> for your products with increased daily limits. Lite version allows to make only 5 category requests per day.</li>' .
                '<li><strong>Order fulfillment using API:</strong> Place unlimited orders on AliExpress through the AliExpress API. In contrast to AliNext (Lite version) Lite allowing you to place only one order using the API.</li>' .
                '<li><strong>Order Sync using API:</strong> Sync unlimited orders AliExpress through the AliExpress API. In contrast to AliNext (Lite version) Lite allowing you to sync only one order using the API.</li>' .
                '<li><strong>Frontend shipping:</strong> Instead of importing product with specific shipping cost, you can allow customers to select shipping company based on their country just like shopping on AliExpress. Shipping companies can be masked to hide the fact that you work as dropshipper.</li>' .
                '<li><strong>Automatically update product price and quantity:</strong> Product price and quantity can now be synced with AliExpress automatically using CRON. If a product is out of stock/change price or is offline, you will receive email notification. You can also check the progress in the log file.</li>' .
                '<li><strong>Automatically update product reviews:</strong> When new reviews appear on AliExpress, the plugin adds them automatically to already imported products.</li>' .
                '<li><strong>Shipping markup:</strong> Add separate pricing rules for shipping options imported from AliExpress</li>' .
                '</ul>',
            'local_price' => "12.00",
            'local_regular_price' => "24.00",
            'currency' => 'EUR',
            'evaluateScore' => 4.8,
            'purchases' => 8742,
            'button_cta' => 'Get Full Version'
        ];
    }

    public function getPromoData(): array
    {
        return $this->promo_data;
    }
}
