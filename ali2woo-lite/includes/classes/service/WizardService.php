<?php

/**
 * Description of WizardService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class WizardService
{
    public function __construct(
        protected AliexpressRegionRepository $AliexpressRegionRepository,
        protected AliexpressLocalizator $AliexpressLocalizator,
        protected PriceFormulaRepository $PriceFormulaRepository,
        protected PriceFormulaFactory $PriceFormulaFactory,
        protected CommonSettingService $CommonSettingService,
        protected AssetService $AssetService
    ) {
    }

    public function handle(array $post): array
    {
        $errors = [];

        settings()->auto_commit(false);
        

        if (isset($post['a2w_import_language'])) {
            set_setting('import_language', wp_unslash($post['a2w_import_language']));
        }

        

        if (isset($post['a2w_local_currency'])) {
            $currency = wp_unslash($post['a2w_local_currency']);
            set_setting('local_currency', $currency);
            update_option('woocommerce_currency', $currency);
        }

        

        // description import mode
        $mode = $post['a2wl_description_import_mode'] ?? 'use_spec';
        set_setting('not_import_attributes', false);
        set_setting('not_import_description', $mode === 'use_spec');
        set_setting('not_import_description_images', $mode === 'use_spec');

        $pricingRules = $post['a2wl_pricing_rules'] ?? 'low-ticket-fixed-3000';
        $addShipping = isset($post['a2wl_add_shipping_to_product']);

        $this->setupPricingRules($pricingRules, $addShipping);

        if (isset($post['a2wl_remove_unwanted_phrases'])) {
            PhraseFilter::deleteAll();
            foreach (['China','china','Aliexpress','AliExpress'] as $phrase) {
                (new PhraseFilter(['phrase' => $phrase, 'phrase_replace' => '']))->save();
            }
        }

        if (!empty($post['a2wl_fulfillment_phone_code']) && !empty($post['a2wl_fulfillment_phone_number'])) {
            set_setting('fulfillment_phone_code', wp_unslash($post['a2wl_fulfillment_phone_code']));
            set_setting('fulfillment_phone_number', wp_unslash($post['a2wl_fulfillment_phone_number']));
        } else {
            $errors['a2wl_fulfillment_phone_block'] = esc_html__('required fields', 'ali2woo');
        }
        

        

        settings()->commit();
        settings()->auto_commit(true);

        return $errors;
    }

    

    protected function setupPricingRules(string $pricingRules, bool $addShipping): void
    {
        set_setting('pricing_rules_type', PriceFormulaService::SALE_PRICE_AS_BASE);
        set_setting('use_extended_price_markup', false);
        set_setting('use_compared_price_markup', false);
        set_setting('price_cents', -1);
        set_setting('price_compared_cents', -1);
        set_setting('default_formula', false);

        $this->PriceFormulaRepository->deleteAll();

        if ($pricingRules === 'low-ticket-fixed-3000') {
            $defaultRule = [
                'value' => 3,
                'sign' => '*',
                'compared_value' => 1,
                'compared_sign' => '*'
            ];
            $formula = $this->PriceFormulaFactory->createFormulaFromData($defaultRule);
            $this->PriceFormulaRepository->setDefaultFormula($formula);
        }
        
    }
    
    public function collectModel(): array
    {
        $language_model = new Language();

        return [
            'aliexpressRegion' => $this->AliexpressRegionRepository->get(),
            'aliexpressRegions' => $this->AliexpressRegionRepository->getAllWithLabels(),
            'currencies' => $this->AliexpressLocalizator->getCurrencies(false),
            'custom_currencies' => $this->AliexpressLocalizator->getCurrencies(true),
            'description_import_modes' => [
                'use_spec'   => esc_html_x('Use specifications only (recommended)', 'Wizard', 'ali2woo'),
                'import_desc' => esc_html_x('Use AliExpress description', 'Wizard', 'ali2woo'),
            ],
            'pricing_rule_sets' => [
                'no' => esc_html_x('Skip for now', 'Wizard', 'ali2woo'),
                'low-ticket-fixed-3000' => esc_html_x('Fixed markup 300%', 'Wizard', 'ali2woo'),
            ],
            'shippingModes' => [
                'no' => esc_html_x('No need', 'Wizard', 'ali2woo'),
                'cart_page' => esc_html_x('Use only in cart', 'Wizard', 'ali2woo'),
                'product_page' => esc_html_x('Use in cart and product page  ', 'Wizard', 'ali2woo'),
            ],
            'languages' => $language_model->get_languages(),
            'wizardLogo' => $this->AssetService->getLogoUrl('logo-120.png')
        ];
    }
}
