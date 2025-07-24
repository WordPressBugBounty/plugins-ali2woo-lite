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
            'evaluateScore' => 4.7,
            'purchases' => 37936,
            'button_cta' => 'Get Full Version',
        ];
    }

    protected function generateDescriptionHtml(): string
    {
        $features = [
            'Instant Upgrade Without Disruption' => 'Keep all your existing settings, imported products, and fulfilled orders—your store transitions seamlessly.',
            'Priority Support & Continuous Updates' => 'Get premium assistance plus ongoing updates to stay ahead of the game.',
            'Supercharged Daily Usage Quota' => 'Expand your daily limit from 100 to 500, 5,000, 50,000 or even 100,000—based on your chosen AliNext (Lite version) plan.',
            'Region-Specific Accuracy' => 'Select your preferred AliExpress region to display precise stock, pricing, and shipping options for your customers.',
            'Import Full Category Trees at Scale' => 'Access deep product categories with expanded API limits—no more 5-request-a-day restriction.',
            'Effortless Order Fulfillment via API' => 'Place unlimited orders through the official AliExpress API (Lite plan supports just one).',
            'Real-Time Order Syncing' => 'Keep order statuses in sync automatically—no manual updates needed (unlimited vs. Lite’s single sync).',
            'Smart Frontend Shipping Options' => 'Let your customers choose shipping providers by country—just like AliExpress.',
            'Auto-Sync Price & Stock' => 'Stay up-to-date with automatic price and stock syncing. Get email alerts and track progress in logs.',
            'Live Review Syncing' => 'New reviews on AliExpress? They’ll appear automatically on your product pages—no effort required.',
            'Flexible Shipping Markup Rules' => 'Set custom pricing strategies for imported shipping methods to boost your margins.',
            'Secure Staff Permissions' => 'Let your Shop Managers access key plugin areas without granting full admin rights.',
            'Bulk Shipping Assignment' => 'Assign preferred shipping options to multiple products at once—set minimum rates or carriers easily.',
        ];

        $html = '<ul style="list-style: decimal;">';
        foreach ($features as $title => $desc) {
            $html .= "<li><strong>{$title}</strong> {$desc}</li>";
        }
        $html .= '</ul>';

        return $html;
    }
}
