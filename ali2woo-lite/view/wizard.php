<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
use AliNext_Lite\AbstractController;
use function AliNext_Lite\get_setting;

/**
 * @var string $aliexpressRegion
 * @var array $aliexpressRegions
 */
?>
<div class="a2wl-content">
    <div class="panel panel-primary">
        <div class="panel-heading panel-heading_column">
            <div class="panel-title">
                <h2><?php echo esc_html_x( 'Welcome to AliNext (Lite version)!', 'Wizard', 'ali2woo' ); ?></h2>
            </div>
            <h3 class="display-inline">
                <?php echo esc_html_x( 'Based on your selection, our setup wizard will set optimal settings.', 'Wizard', 'ali2woo' ); ?>
            </h3>
            <p>
                <?php echo esc_html_x( 'Click "Save" at the bottom of the page to apply recommendations. Please note: this setup wizard may overwrite your existing plugin settings.', 'Wizard', 'ali2woo' ); ?>
            </p>
        </div>

        <div class="panel-body">
            <form method="post">
                <?php wp_nonce_field( AbstractController::PAGE_NONCE_ACTION, AbstractController::NONCE ); ?>
                <input type="hidden" name="wizard_form" value="1"/>

                

                <div class="field field_inline">
                    <div class="field__label">
                        <label for="a2wl_aliexpress_token">
                            <strong><?php echo esc_html_x( 'Connect your AliExpress account', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'The connection is established using your AliExpress access token.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap">
                        <a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=a2wl_setting&subpage=account' ) ); ?>">
                            <?php echo esc_html_x( 'Go to account settings to complete the connection', 'Wizard', 'ali2woo' ); ?>
                        </a>
                    </div>
                </div>


                <?php if (A2WL()->isAnPlugin()): ?>
                <div class="_a2wfo a2wl-info"><div>This feature is available in full version of the plugin.</div><a href="https://ali2woo.com/pricing/?utm_source=lite&utm_medium=lite_banner&utm_campaign=alinext-lite" target="_blank" class="btn">GET FULL VERSION</a></div>
                <div class="field field_inline _a2wfv">
                    <div class="field__label">
                        <label for="a2wl_aliexpress_region">
                            <strong><?php echo esc_html_x( 'AliExpress region', 'Setting title', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'This feature enables you to select the AliExpress region for your website. It automatically adjusts the imported prices, stock levels, and shipping information based on the chosen region.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap select2-fixed">
                        <select name="a2wl_aliexpress_region" id="a2wl_aliexpress_region" class="field__input form-control small-input">
                        <?php foreach ( $aliexpressRegions as $regionCode => $text ) : ?>
                            <option value="<?php echo esc_attr( $regionCode ); ?>" <?php selected( $aliexpressRegion, $regionCode ); ?>>
                                <?php echo esc_html( $text ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Language Selection Field -->
                <div class="field field_inline">
                    <div class="field__label">
                        <label for="a2w_import_language">
                            <strong><?php echo esc_html_x( 'Set Language', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'AliExpress product specifications, titles, descriptions, and reviews will be imported in your preferred language.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap select2-fixed">
                        <?php $cur_language = get_setting( 'import_language' ); ?>
                        <select
                                name="a2w_import_language"
                                id="a2w_import_language"
                                class="field__input form-control small-input"
                        >
                            <?php foreach ( $languages as $code => $text ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $cur_language, $code ); ?>>
                                    <?php echo esc_html( $text ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>


                <div class="field field_inline">
                    <div class="field__label">
                        <label for="a2w_local_currency">
                            <strong><?php echo esc_html_x( 'Set currency', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'Choose the currency for prices you import from AliExpress. Note: WooCommerce store currency will also be updated.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap select2-fixed">
                        <?php $cur_a2w_local_currency = strtoupper( get_setting( 'local_currency' ) ); ?>
                        <select name="a2w_local_currency" id="a2w_local_currency" class="form-control small-input">
                            <?php foreach ( $currencies as $code => $name ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $cur_a2w_local_currency, $code ); ?>>
                                    <?php echo esc_html( $name ); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ( ! empty( $custom_currencies ) ) : ?>
                                <?php foreach ( $custom_currencies as $code => $name ) : ?>
                                    <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $cur_a2w_local_currency, $code ); ?>>
                                        <?php echo esc_html( $name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="field field_inline">
                    <div class="field__label">
                        <label for="a2wl_description_import_mode">
                            <strong><?php echo esc_html_x( 'What to do with product description?', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'AliExpress sellers often provide little or no text, relying on images with promotional information. A good practice is to use product specifications instead of descriptions and avoid importing description images.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap">
                        <select name="a2wl_description_import_mode" id="a2wl_description_import_mode" class="field__input form-control large-input">
                            <?php foreach ( $description_import_modes as $code => $name ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( 'use_spec', $code ); ?>>
                                    <?php echo esc_html( $name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field field_inline">
                    <div class="field__label">
                        <label for="a2wl_pricing_rules">
                            <strong><?php echo esc_html_x( 'What pricing model do you want to use?', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'Pricing rules define your profit margin. The wizard can add basic rules as a starting point for your unique strategy.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap">
                        <select name="a2wl_pricing_rules" id="a2wl_pricing_rules" class="field__input form-control large-input">
                            <?php foreach ( $pricing_rule_sets as $code => $name ) : ?>
                                <option value="<?php echo esc_attr( $code ); ?>" <?php selected( 'low-ticket-fixed-3000', $code ); ?>>
                                    <?php echo esc_html( $name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field field_inline">
                    <div class="field__label">
                        <label for="a2wl_add_shipping_to_product">
                            <strong><?php echo esc_html_x( 'Include shipping cost in product prices', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'Including shipping costs in product prices helps protect your profit margins.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap">
                        <input type="checkbox" class="field__input form-control" id="a2wl_add_shipping_to_product" name="a2wl_add_shipping_to_product" value="yes" checked />
                    </div>
                </div>

                <div class="field field_inline">
                    <div class="field__label">
                        <label for="a2wl_remove_unwanted_phrases">
                            <strong><?php echo esc_html_x( 'Remove unwanted phrases from AliExpress products', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'The plugin will automatically remove words like "AliExpress", "China", etc. from imported products.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap">
                        <input type="checkbox" class="field__input form-control" id="a2wl_remove_unwanted_phrases" name="a2wl_remove_unwanted_phrases" value="yes" checked />
                    </div>
                </div>

                <div class="field field_inline">
                    <div class="field__label">
                        <label class="<?php echo ! empty( $errors['a2wl_fulfillment_phone_block'] ) ? 'has-error' : ''; ?>">
                            <strong><?php echo esc_html_x( 'Replace buyer phone with your number', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'Suppliers may contact you about orders. Best practice is to leave your phone number in the AliExpress order note.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap">
                        <div class="field__input form-group input-block no-margin <?php echo ! empty( $errors['a2wl_fulfillment_phone_block'] ) ? 'has-error' : ''; ?>">
                            <input type="text" placeholder="<?php esc_attr_e( 'Code', 'ali2woo' ); ?>" style="max-width: 60px;" class="field__input form-control" id="a2wl_fulfillment_phone_code" maxlength="5" name="a2wl_fulfillment_phone_code" value="<?php echo esc_attr( get_setting( 'fulfillment_phone_code' ) ); ?>" />
                            <input type="text" placeholder="<?php esc_attr_e( 'Phone', 'ali2woo' ); ?>" class="field__input form-control large-input" id="a2wl_fulfillment_phone_number" maxlength="16" name="a2wl_fulfillment_phone_number" value="<?php echo esc_attr( get_setting( 'fulfillment_phone_number' ) ); ?>" />
                            <?php if ( ! empty( $errors['a2wl_fulfillment_phone_block'] ) ) : ?>
                                <span class="field__input help-block"><?php echo esc_html( $errors['a2wl_fulfillment_phone_block'] ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="field field_inline">
                    <div class="field__label">
                        <label for="a2wl_import_reviews">
                            <strong><?php echo esc_html_x( 'Do you want to import reviews?', 'Wizard', 'ali2woo' ); ?></strong>
                        </label>
                        <div class="info-box"
                             data-toggle="tooltip"
                             data-title="<?php echo esc_attr_x(
                                     'Reviews can help increase conversion rates in your store.',
                                     'Wizard',
                                     'ali2woo'
                             ); ?>">
                        </div>
                    </div>
                    <div class="field__input-wrap">
                        <div class="form-group input-block no-margin">
                            <input type="checkbox" class="form-control" id="a2wl_import_reviews" name="a2wl_import_reviews" value="yes" checked />
                        </div>
                    </div>
                </div>

                <div class="container-fluid">
                    <div class="row pt20 border-top">
                        <div class="col-sm-12">
                            <input class="btn btn-success js-main-submit" type="submit" value="<?php esc_attr_e( 'Save settings', 'ali2woo' ); ?>"/>
                            <input class="btn btn-default" id="close_setup_wizard" type="button" value="<?php esc_attr_e( 'Close', 'ali2woo' ); ?>"/>
                            <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_attr_x( 'Close the Setup Wizard to prevent changes in the settings.', 'Wizard', 'ali2woo' ); ?>"></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function ($) {
        if ($.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip({ placement: "top" });
        }

        $('#close_setup_wizard').on('click', function () {
            window.location.href = "<?php echo esc_url( $close_link ); ?>";
        });

        $('#a2wl_pricing_rules').on('change', function () {
            if ($(this).val() === 'no') {
                $('#a2wl_add_shipping_to_product').prop('checked', false);
            } else {
                $('#a2wl_add_shipping_to_product').prop('checked', true);
            }
        });
    })(jQuery);
</script>
