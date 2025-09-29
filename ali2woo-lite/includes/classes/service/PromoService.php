<?php

/**
 * Description of PromoService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class PromoService
{
    protected array $promoData;

    public function __construct()
    {
        $this->promoData = $this->buildPromoData();
    }

    public function getPromoData(): array
    {
        return $this->promoData;
    }

    protected function buildPromoData(): array
    {
        return [
            'full_plugin_link' => 'https://ali2woo.com/pricing/?utm_source=lite&utm_medium=plugin_promo&utm_campaign=alinext-lite',
            'promo_image_url' => A2WL()->plugin_url() . '/assets/img/alinext-plugin-box-268.jpg',
            'title' => 'AliNext full version',
            'description' => $this->generateDescriptionHtml(),
            'local_price' => '12.00',
            'local_regular_price' => '24.00',
            'currency' => 'EUR',
            'evaluateScore' => 4.8,
            'purchases' => 67156,
            'button_cta' => 'Get Full Version',
        ];
    }

    protected function generateDescriptionHtml(): string
    {
        $features = [
            'Seamless Upgrade & Priority Support' =>
                'Switch to the full version in seconds—no lost settings, no downtime. Plus, enjoy premium support and continuous updates to stay ahead.',

            'Massive Daily Quotas & Region Accuracy' =>
                'Break free from Lite limits with up to 100,000 daily requests. Target the right AliExpress region for precise stock, pricing, and shipping data.',

            'Advanced Catalog & Review Automation' =>
                'Import full category trees without restrictions and keep your store fresh with automatic review syncing from AliExpress.',

            'Unlimited Orders & Real-Time Sync' =>
                'Fulfill unlimited orders via the official API and keep order statuses perfectly aligned—no manual updates needed.',

            'Automatic Product Data Sync' =>
                'Stay worry‑free with continuous syncing of prices, stock levels, and shipping details for all imported products—your catalog is always up to date.',

            'Built‑In Image Editor' =>
                'Edit imported product images directly inside the plugin. Remove seller watermarks and polish visuals without relying on external tools.',

            'Advanced Shipping Control' =>
                'Give customers real AliExpress shipping options on your storefront, while you stay in full control with flexible markup rules and bulk carrier assignments.',

            'Team-Friendly & Secure' =>
                'Grant Shop Managers controlled access to plugin features without exposing full admin rights—safe delegation made simple.',
        ];

        $html = '<ul style="list-style: decimal;">';
        foreach ($features as $title => $desc) {
            $html .= "<li><strong>{$title}</strong> {$desc}</li>";
        }
        $html .= '</ul>';

        return $html;
    }
}
