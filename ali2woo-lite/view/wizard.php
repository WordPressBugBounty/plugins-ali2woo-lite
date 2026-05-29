<?php
use AliNext_Lite\AbstractController;
use function AliNext_Lite\get_setting;
use AliNext_Lite\Settings;

/**
 * @var string $aliexpressRegion
 * @var array $aliexpressRegions
 * @var array $languages
 * @var array $currencies
 * @var array $description_import_modes
 * @var array $pricing_rule_sets
 * @var array $errors
 * @var string $close_link
 * @var array $shippingModes
 * @var string $wizardLogo
 */
?>
<div class="a2wl-wizard">

    <div class="a2wl-wizard__header">
        <div class="a2wl-wizard__titles">
            <h1><?php echo esc_html_x('Welcome to AliNext (Lite version)!', 'Wizard', 'ali2woo'); ?></h1>
            <p class="a2wl-wizard__subtitle">
                <?php echo esc_html_x(
                    'Set up your dropshipping store in a few clicks. Press "Save" to apply - current settings may change.',
                    'Wizard',
                    'ali2woo'
                ); ?>
            </p>
        </div>

        <div class="a2wl-wizard__logo">
            <img src="<?php echo esc_attr($wizardLogo); ?>"
                 alt="AliNext (Lite version) Logo">
        </div>
    </div>

    <form method="post" class="a2wl-wizard__form">
        <?php wp_nonce_field(AbstractController::PAGE_NONCE_ACTION, AbstractController::NONCE); ?>
        <input type="hidden" name="wizard_form" value="1"/>
        

        <div class="a2wl-card">
            <div class="a2wl-field">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('AliExpress account', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-field__control a2wl-size--long">
                      <span class="a2wl-help" data-title="<?php echo esc_attr_x(
                              'Connect your AliExpress account in settings to import products and fulfill orders automatically.',
                              'Wizard',
                              'ali2woo'
                      ); ?>"></span>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=a2wl_setting&subpage=account')); ?>" target="_blank">
                        <?php echo esc_html_x('connect your account', 'Wizard', 'ali2woo'); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="a2wl-card">
            <div class="a2wl-field">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Import language', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-field__control">
                       <span class=a2wl-help" data-title="<?php echo esc_attr_x(
                               'Import AliExpress titles, specs, descriptions, and reviews in your chosen language.',
                               'Wizard',
                               'ali2woo'
                       ); ?>"></span>
                    <select name="a2w_import_language" id="a2w_import_language" class="a2wl-size--long">
                        <?php $cur_language = get_setting('import_language'); ?>
                        <?php foreach ($languages as $code => $text): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($cur_language, $code); ?>>
                                <?php echo esc_html($text); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="a2wl-card">
            <div class="a2wl-field">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Import currency', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-field__control">
                      <span class="a2wl-help" data-title="<?php echo esc_attr_x(
                              'Choose the currency for AliExpress imports. Your WooCommerce store currency will update accordingly.',
                              'Wizard',
                              'ali2woo'
                      ); ?>"></span>
                    <select name="a2w_local_currency" id="a2w_local_currency" class="a2wl-size--long">
                    <?php $cur_a2w_local_currency = strtoupper(get_setting('local_currency')); ?>
                    <?php foreach ($currencies as $code => $name): ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($cur_a2w_local_currency, $code); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>

                    <?php if (!empty($custom_currencies)): ?>
                        <?php foreach ($custom_currencies as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($cur_a2w_local_currency, $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                </div>
            </div>
        </div>

        <div class="a2wl-card">
            <div class="a2wl-field">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Product description', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-field__control">
                      <span class="a2wl-help" data-title="<?php echo esc_attr_x(
                              'Choose what to show in the product "Description" tab: specifications or AliExpress description',
                              'Wizard',
                              'ali2woo'
                      ); ?>"></span>
                    <select name="a2wl_description_import_mode" id="a2wl_description_import_mode" class="a2wl-size--long">
                        <?php foreach ($description_import_modes as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected('use_spec', $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="a2wl-card">
            <div class="a2wl-field">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Pricing model', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-field__control">
                    <span class="a2wl-help" data-title="<?php echo esc_attr_x(
                            'Choose how to set product pricing',
                            'Wizard',
                            'ali2woo'
                    ); ?>"></span>
                    <select name="a2wl_pricing_rules" id="a2wl_pricing_rules" class="a2wl-size--long">
                        <?php foreach ($pricing_rule_sets as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected('low-ticket-fixed-3000', $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>

        <div class="a2wl-card">
            <div class="a2wl-field a2wl-field--start">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Remove phrases', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-column">
                    <label>
                        <input type="checkbox" name="a2wl_remove_unwanted_phrases" value="yes" checked>
                        <span><?php echo esc_html_x('Enable', 'Wizard', 'ali2woo'); ?></span>
                    </label>
                    <div class="a2wl-field__desc">
                        <?php echo esc_html_x(
                                'Remove AliExpress/China mentions',
                                'Wizard',
                                'ali2woo'
                        ); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="_a2wfo a2wl-info"><div>This feature is available in full version of the plugin.</div><a href="https://ali2woo.com/pricing/?utm_source=lite&utm_medium=lite_banner&utm_campaign=alinext-lite" target="_blank" class="btn">GET FULL VERSION</a></div>

        <?php if (A2WL()->isAnPlugin()): ?>
            <div class="a2wl-card _a2wfo">
                <div class="a2wl-field">
                    <div class="a2wl-field__label">
                        <?php echo esc_html_x('AliExpress region', 'Wizard', 'ali2woo'); ?>
                    </div>
                    <div class="a2wl-field__control">
                       <span class="a2wl-help" data-title="<?php echo esc_attr_x(
                               'Select the AliExpress region for your store. Prices, stock, and shipping will adjust automatically based on your choice.',
                               'Wizard',
                               'ali2woo'
                       ); ?>"></span>
                        <select name="a2wl_aliexpress_region" id="a2wl_aliexpress_region" class="a2wl-size--long">
                            <?php foreach ($aliexpressRegions as $regionCode => $text): ?>
                                <option value="<?php echo esc_attr($regionCode); ?>" <?php selected($aliexpressRegion, $regionCode); ?>>
                                    <?php echo esc_html($text); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="a2wl-card _a2wfo">
            <div class="a2wl-field a2wl-field--start">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Auto-sync', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-column">
                    <label>
                        <input type="checkbox" name="a2wl_auto_update" value="yes">
                        <span><?php echo esc_html_x('Enable', 'Wizard', 'ali2woo'); ?></span>
                    </label>
                    <div class="a2wl-field__desc">
                        <?php echo esc_html_x(
                                'Automatically update product prices and stock from AliExpress',
                                'Wizard',
                                'ali2woo'
                        ); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="a2wl-card _a2wfo">
            <div class="a2wl-field a2wl-field--start">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('AliExpress Shipping UI', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-field__control">
                    <span class="a2wl-help" data-title="<?php echo esc_attr_x(
                            'Choose where to display AliExpress shipping selectors: hide them, show only in the cart, or show both in cart and product pages.',
                            'Wizard',
                            'ali2woo'
                    ); ?>"></span>
                    <select name="a2wl_shipping_mode" class="a2wl-size--long">
                        <?php foreach ($shippingModes as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($code, 'no'); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="a2wl-card _a2wfo">
            <div class="a2wl-field a2wl-field--start">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Shipping auto-sync  ', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-column">
                    <label>
                        <input type="checkbox" name="a2wl_<?php echo Settings::SETTING_SYNC_PRODUCT_SHIPPING; ?>" value="yes">
                        <span><?php echo esc_html_x('Enable', 'Wizard', 'ali2woo'); ?></span>
                    </label>
                    <div class="a2wl-field__desc">
                        <?php echo esc_html_x(
                                'Automatically update shipping costs from AliExpress',
                                'Wizard',
                                'ali2woo'
                        ); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="a2wl-card _a2wfo">
            <div class="a2wl-field a2wl-field--start">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Include shipping', 'Wizard', 'ali2woo'); ?>
                </div>

                <div>
                    <label>
                        <input type="checkbox" name="a2wl_add_shipping_to_product" value="yes">
                        <span><?php echo esc_html_x('Enable', 'Wizard', 'ali2woo'); ?></span>
                    </label>
                    <div class="a2wl-field__desc">
                        <?php echo esc_html_x(
                                'Include shipping in price',
                                'Wizard',
                                'ali2woo'
                        ); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="a2wl-card _a2wfo">
            <div class="a2wl-field a2wl-field--start">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Import reviews', 'Wizard', 'ali2woo'); ?>
                </div>

                <div class="a2wl-column">
                    <label>
                        <input type="checkbox" name="a2wl_import_reviews" value="yes">
                        <span><?php echo esc_html_x('Enable', 'Wizard', 'ali2woo'); ?></span>
                    </label>
                    <div class="a2wl-field__desc">
                        <?php echo esc_html_x(
                                'Bring authentic AliExpress reviews into your store',
                                'Wizard',
                                'ali2woo'
                        ); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="a2wl-card _a2wfo">
            <div class="a2wl-field">
                <div class="a2wl-field__label">
                    <?php echo esc_html_x('Phone for orders', 'Wizard', 'ali2woo'); ?>
                </div>
                <div class="a2wl-field__control">
                      <span class="a2wl-help" data-title="<?php echo esc_attr_x(
                              'Use your phone number for AliExpress orders (code + phone required)',
                              'Wizard',
                              'ali2woo'
                      ); ?>"></span>
                    <input class="a2wl-size--short" type="text" name="a2wl_fulfillment_phone_code" placeholder="<?php esc_attr_e('Code', 'ali2woo'); ?>"
                           value="<?php echo esc_attr(get_setting('fulfillment_phone_code')); ?>" maxlength="5">

                    <input class="a2wl-size--medium" type="text" name="a2wl_fulfillment_phone_number" placeholder="<?php esc_attr_e('Phone', 'ali2woo'); ?>"
                           value="<?php echo esc_attr(get_setting('fulfillment_phone_number')); ?>" maxlength="16">

                    <?php if (!empty($errors['a2wl_fulfillment_phone_block'])): ?>
                        <div class="a2wl-field__error"><?php echo esc_html($errors['a2wl_fulfillment_phone_block']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (A2WL()->isAnPlugin()): ?>
            <div class="a2wl-card _a2wfo">
                <div class="a2wl-field a2wl-field--start">
                    <div class="a2wl-field__label">
                        <?php echo esc_html_x('Shop manager', 'Wizard', 'ali2woo'); ?>
                    </div>

                    <div class="a2wl-column">
                        <label>
                            <input type="checkbox" name="a2wl_<?php echo esc_attr(Settings::SETTING_ALLOW_SHOP_MANAGER); ?>" value="yes">
                            <span><?php echo esc_html_x('Enable', 'Wizard', 'ali2woo'); ?></span>
                        </label>
                        <div class="a2wl-field__desc">
                            <?php echo esc_html_x(
                                    'Allow WooCommerce Shop Managers to access plugin settings',
                                    'Wizard',
                                    'ali2woo'
                            ); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="a2wl-wizard__footer">
            <button type="submit" class="a2wl-btn a2wl-btn--primary">
                <?php esc_html_e('Save settings', 'ali2woo'); ?>
            </button>

            <button type="button" id="close_setup_wizard" class="a2wl-btn a2wl-btn--secondary">
                <?php esc_html_e('Close', 'ali2woo'); ?>
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let closeBtn = document.getElementById('close_setup_wizard');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                window.location.href = "<?php echo esc_url($close_link); ?>";
            });
        }
    });
</script>
