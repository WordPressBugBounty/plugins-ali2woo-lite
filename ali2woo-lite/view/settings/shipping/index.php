<?php
use AliNext_Lite\AbstractController;
use AliNext_Lite\PriceFormula;
use function AliNext_Lite\get_setting;
use AliNext_Lite\Settings;

/**
 * @var array $shipping_class
 * @var array $shipping_countries
 * @var array $shipping_selection_types
 * @var array $shipping_types
 * @var array $selection_position_types
 * @var PriceFormula $default_formula
 * @var array $deliveryInfoDisplayModes
 * @var string $deliveryTimeTextFormat
 * @var int $deliveryTimeFallbackMin
 * @var int $deliveryTimeFallbackMax
 * @var bool $deliveryTimeShortcodeEnabled
 */

$a2w_local_currency = strtoupper(get_setting('local_currency'));
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<form method="post" enctype='multipart/form-data'>
    <?php wp_nonce_field(AbstractController::PAGE_NONCE_ACTION, AbstractController::NONCE); ?>
    <input type="hidden" name="setting_form" value="1"/>
    <div class="panel panel-primary mt20">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo esc_html_x('Shipping settings', 'Setting title', 'ali2woo'); ?></h3>
            <span class="pull-right">
                <a href="#" class="reset-shipping-meta btn _a2wfv"><?php echo esc_html_x('Reset product shipping meta', 'Setting title', 'ali2woo'); ?><div class="info-box" data-placement="left" data-toggle="tooltip" data-title="<?php echo esc_html_x('It clears the shipping methods cache, use this feature if you believe the shipping cost is changed on AliExpress.', 'Setting tip', 'ali2woo'); ?>"></div></a>
            </span>
        </div>

        <div class="panel-body">
            <div class="field field_inline _a2wfv">
                <div class="field__label">
                    <label>
                        <strong><?php echo esc_html_x('Default shipping class', 'Setting title', 'ali2woo'); ?></strong>
                    </label>
                    <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_html_x('Specific shipping class for WooCommerce, that get all products imported via Ali2Woo.', 'setting description', 'ali2woo'); ?>"></div>
                </div>
                <div class="field__input-wrap">
                    <?php $default_shipping_class = get_setting('default_shipping_class');?>
                    <select name="a2wl_default_shipping_class" id="a2wl_default_shipping_class" class="field__input form-control small-input">
                        <option value=""><?php echo esc_html_x('Do nothing', 'Setting option', 'ali2woo'); ?></option>
                        <?php foreach ($shipping_class as $sc): ?>
                            <option value="<?php echo $sc->term_id; ?>" <?php if ($default_shipping_class == $sc->term_id): ?>selected="selected"<?php endif;?>><?php echo $sc->name; ?></option>
                        <?php endforeach;?>
                    </select>
                </div>
            </div>

            <div class="field field_inline">
                <div class="field__label">
                    <label>
                        <strong><?php echo esc_html_x('Default Shipping Country', 'Setting title', 'ali2woo'); ?></strong>
                    </label>
                    <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_html_x('This is for the frontend (Cart, Checkout, Product page) and for the backend Ali2Woo`s pages (Search, Import List, etc.).', 'setting description', 'ali2woo'); ?>"></div>
                </div>
                <div class="field__input-wrap">
                    <?php $cur_a2w_aliship_shipto = get_setting('aliship_shipto');?>
                    <select name="a2w_aliship_shipto" id="a2w_aliship_shipto" class="field__input form-control small-input country_list">
                        <option value=""><?php  esc_html_e('N/A', 'ali2woo');?></option>
                        <?php foreach ($shipping_countries as $code => $country): ?>
                            <option value="<?php echo $code; ?>"<?php if ($cur_a2w_aliship_shipto == $code): ?> selected<?php endif;?>>
                                <?php echo $country; ?>
                            </option>
                        <?php endforeach;?>
                    </select>
                </div>
            </div>

        </div>
    </div>

    <div class="panel panel-default mt20">
        <div class="_a2wfo a2wl-info"><div>This feature is available in full version of the plugin.</div><a href="https://ali2woo.com/pricing/?utm_source=lite&utm_medium=lite_banner&utm_campaign=alinext-lite" target="_blank" class="btn">GET FULL VERSION</a></div>
        <div class="panel-body _a2wfv"">
        <div class="field field_inline">
            <div class="field__label">
                <label>
                    <strong><?php echo esc_html_x('Use Aliexpress Shipping', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_html_x('It enables all options below and show the shipping selection interface on the cart and checkout page.', 'setting description', 'ali2woo'); ?>"></div>
            </div>
            <div class="field__input-wrap">
                <input type="checkbox" class="field__input form-control small-input" id="a2wl_<?php echo Settings::SETTING_ALLOW_SHIPPING_FRONTEND; ?>" name="a2wl_<?php echo Settings::SETTING_ALLOW_SHIPPING_FRONTEND; ?>" <?php if (get_setting(Settings::SETTING_ALLOW_SHIPPING_FRONTEND)): ?>value="yes" checked<?php endif;?> />
                <p><?php esc_html_e('All options below will only work if this option is enabled', 'ali2woo')?></p>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label>
                    <strong><?php echo esc_html_x('Auto-Assign Shipping on Import', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip" data-title="<?php echo esc_html_x('Enable shipping auto-assignment on product import.', 'setting description', 'ali2woo'); ?>"></div>
            </div>
            <div class="field__input-wrap">
                <input type="checkbox" class="field__input form-control small-input" id="a2wl_<?php echo Settings::SETTING_ASSIGN_SHIPPING_ON_IMPORT ?>"
                       name="a2wl_<?php echo Settings::SETTING_ASSIGN_SHIPPING_ON_IMPORT ?>"
                       <?php if (get_setting(Settings::SETTING_ASSIGN_SHIPPING_ON_IMPORT, false)): ?>value="yes" checked<?php endif;?> />
                <p><?php esc_html_e('Assign default or lowest-cost shipping method when available', 'ali2woo')?></p>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label for="a2wl_<?php echo esc_attr(Settings::SETTING_SYNC_PRODUCT_SHIPPING); ?>">
                    <strong>
                        <?php esc_html_e("Synchronize product shipping", 'ali2woo'); ?>
                    </strong>
                </label>
                <div class="info-box"
                     data-toggle="tooltip"
                     data-title="<?php esc_attr_e("Enable synchronization of product shipping information from AliExpress.", 'ali2woo'); ?>">
                </div>
            </div>
            <div class="field__input-wrap">
                <label>
                    <input type="checkbox"
                           class="field__input form-control"
                           id="a2wl_<?php echo esc_attr(Settings::SETTING_SYNC_PRODUCT_SHIPPING); ?>"
                           name="a2wl_<?php echo esc_attr(Settings::SETTING_SYNC_PRODUCT_SHIPPING); ?>"
                           value="yes"
                            <?php checked(get_setting(Settings::SETTING_SYNC_PRODUCT_SHIPPING)); ?> />
                </label>
                <p class="description">
                    <?php esc_html_e(
                            "Note: it can affect the price included into your product price if you allow in pricing rules",
                            'ali2woo'
                    );
                    ?>
                </p>
            </div>
        </div>

        <div class="field field_inline">
            <div class="field__label">
                <label>
                    <strong><?php echo esc_html_x('Display Mode for Delivery Info', 'Setting title', 'ali2woo'); ?></strong>
                </label>
                <div class="info-box" data-toggle="tooltip"
                     data-title="<?php echo esc_html_x('Select how delivery details are shown: full shipping options or delivery time only.', 'setting description', 'ali2woo'); ?>">

                </div>
            </div>
            <div class="field__input-wrap">
                <?php $curDeliveryInfoDisplayMode = get_setting(Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE);?>
                <select name="a2wl_<?php echo Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE; ?>"
                        id="a2wl_<?php echo Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE; ?>" class="field__input form-control small-input">
                    <?php foreach ($deliveryInfoDisplayModes as $displayMode => $displayModeTitle): ?>
                        <option value="<?php echo esc_attr($displayMode); ?>"<?php selected($curDeliveryInfoDisplayMode, $displayMode); ?>>
                            <?php echo $displayModeTitle; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php include_once 'panel_delivery_time_only.php'; ?>
        <?php include_once 'panel_full_shipping_options.php'; ?>
        <div class="global-pricing mt20">
            <div class="panel panel-primary mt20 _a2wfv">
                <div class="panel-heading">
                    <h3 class="display-inline"><?php echo esc_html_x('Global shipping rules', 'Setting title', 'ali2woo'); ?><div class="info-box" data-placement="left" data-toggle="tooltip" data-title="<?php echo esc_html_x('Please note that you can disable Global rules for specific shipping methods if needed. Just go to "Shipping List" page, then choose "specific method" and set  "Enable price rule" to "no".', 'Setting tip', 'ali2woo'); ?>"></div></h3>
                </div>

                <div class="panel-body js-default-prices">
                    <div class="grid grid_default grid_center">

                        <div class="grid__col vertical-align">
                            <svg class="icon-pricechanged">
                                <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#icon-pricechanged"></use>
                            </svg>

                        </div>


                        <div class="grid__col vertical-align">
                            <h3>Shipping cost</h3>
                        </div>
                        <?php /*
                            <div class="grid__col vertical-align">
                                <svg class="sign <?php if ($default_formula->sign == '+' || $default_formula->sign == '*'): ?>icon-plus <?php endif;?><?php if ($default_formula->sign == '*'): ?>icon-rotate45<?php endif;?> <?php if ($default_formula->sign == '='): ?>icon-equal<?php endif;?>">
                                <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#<?php if ($default_formula->sign == '+' || $default_formula->sign == '*'): ?>icon-plus<?php else: ?>icon-equal<?php endif;?>"></use>

                                </svg>
                            </div>
                            */ ?>
                        <div class="grid__col grid__col_jcenter vertical-align">
                            <input name="default_rule[sign]" type="hidden" value="<?php echo $default_formula->sign; ?>">
                            <div class="input-group price-dropdown-group">
                                <input name="default_rule[value]" sign="<?php echo $default_formula->sign ; ?>" type="text" class="input-group__input field__input form-control value" value="<?php echo $default_formula->value; ?>" <?php if (!get_setting(Settings::SETTING_ALLOW_SHIPPING_FRONTEND)): ?> disabled <?php endif;?>>

                                <div class="input-group__input">
                                    <button type="button" class="input-group__input-inner btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" <?php if (!get_setting(Settings::SETTING_ALLOW_SHIPPING_FRONTEND)): ?> disabled <?php endif;?>>
                                        <?php if ($default_formula->sign == '+'): ?>Fixed Markup<?php endif;?>
                                        <?php if ($default_formula->sign == '='): ?>Custom Price<?php endif;?>
                                        <?php if ($default_formula->sign == '*'): ?>Multiplier<?php endif;?>  <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right sign">
                                        <li data-sign = "+" <?php if ($default_formula->sign == '+'): ?>style="display: none;"<?php endif;?>><a>Fixed Markup</a></li>
                                        <li data-sign = "=" <?php if ($default_formula->sign == '='): ?>style="display: none;"<?php endif;?>><a>Custom Price</a></li>
                                        <li data-sign = "*" <?php if ($default_formula->sign == '*'): ?>style="display: none;"<?php endif;?>><a>Multiplier</a></li>
                                    </ul>
                                </div><!-- /btn-group -->
                            </div>
                        </div>
                        <div class="grid__col vertical-align">
                            <svg class="icon-full-arrow-right">
                                <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#icon-full-arrow-right"></use>
                            </svg>
                        </div>
                        <div class="grid__col vertical-align">
                            <h3 style="width: 135px;">Shipping price</h3>
                        </div>
                        <div class="grid__col vertical-align">
                            <div class="info-box" data-placement="left" data-toggle="tooltip" data-title=""></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="container-fluid">
        <div class="row pt20">
            <div class="col-sm-12">
                <input class="btn btn-success" type="submit" value="<?php esc_html_e('Save settings', 'ali2woo');?>"/>
            </div>
        </div>
    </div>

</form>

<script>
    function a2wl_isInt(value) {
        return !isNaN(value) &&
            parseInt(Number(value)) == value &&
            !isNaN(parseInt(value, 10));
    }

    (function ($) {
        let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';

        const $modeSelect = $('#a2wl_<?php echo Settings::SETTING_DELIVERY_INFO_DISPLAY_MODE; ?>');
        const $conditionalPanel = $('.delivery-time-only-conditional');
        const $conditionalPanel2 = $('.full-shipping-options-conditional');

        function toggleDeliveryTimePanel() {
            const mode = $modeSelect.val();

            $conditionalPanel.stop(true, true).hide();
            $conditionalPanel2.stop(true, true).hide();

            if (mode === 'delivery_time_only') {
                $conditionalPanel.fadeIn(200, function () {
                    $(this).addClass('a2wl-highlighted');
                    setTimeout(() => {
                        $(this).removeClass('a2wl-highlighted');
                    }, 1500);
                });
            } else if (mode === 'default') {
                $conditionalPanel2.fadeIn(200, function () {
                    $(this).addClass('a2wl-highlighted');
                    setTimeout(() => {
                        $(this).removeClass('a2wl-highlighted');
                    }, 1500);
                });
            }
        }

        $modeSelect.on('change', toggleDeliveryTimePanel);

        $('.a2wl-placeholder-value').on('click', function () {
            $(this).select();
        });

        $(document).on('click', '.a2wl-placeholder-value-copy', function () {
            const $container = $(this).closest('.a2wl-placeholder-value-container');
            const $input = $container.find('.a2wl-placeholder-value');
            const textToCopy = $input.val() || $input.text();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy).then(() => {
                    console.log('Copied via Clipboard API');
                }).catch(err => {
                    console.warn('Clipboard API failed, fallback to execCommand', err);
                    fallbackCopy($input[0]);
                });
            } else {
                fallbackCopy($input[0]);
            }
        });

        function fallbackCopy(inputEl) {
            inputEl.select();
            try {
                document.execCommand('copy');
                console.log('Copied via execCommand');
            } catch (err) {
                console.error('Fallback copy failed', err);
            }
        }

        $(".reset-shipping-meta").on("click", function () {
            if(!$(".reset-shipping-meta").hasClass('processing')){
                $(".reset-shipping-meta").addClass('processing');

                let data = {
                    'action': 'a2wl_reset_shipping_meta',
                    'ali2woo_nonce': nonce_action,
                };

                $.post(ajaxurl, data).done(function (response) {
                    $(".reset-shipping-meta").removeClass('processing');
                    let json = JSON.parse(response);
                    if(json.state==='ok'){
                        show_notification('Reset product shipping meta Done');
                    } else{
                        show_notification(json.message, true);
                    }
                }).fail(function (xhr, status, error) {
                    $(".reset-shipping-meta").removeClass('processing');
                    show_notification('Applying pricing rules failed.', true);
                });
            }

            return false;
        });

        function get_el_sign_value(el) {
            return el.children('li')
                .filter(function () {
                    return $(this).css('display') === 'none'
                })
                .attr('data-sign');
        }

        function get_value(compared) {
            var s_class = 'compared_value';
            if (typeof compared == "undefined")
                s_class = 'value';

            return $('.js-default-prices .' + s_class).val();
        }

        function rule_info_box_calculation(str_tmpl, sign, value)
        {
            let def_value = 1, result = value;
            if (sign == "+")
                result = def_value + Number(value);
            if (sign == "*")
                result = def_value * Number(value);

            return sprintf(str_tmpl, def_value, result, def_value, sign, value, result)
        }

        if(jQuery.fn.tooltip) { $('[data-toggle="tooltip"]').tooltip({"placement": "top"}); }

        //info content
        $(".js-default-prices div.info-box").on("mouseover", function () {
            let helpTextTpl = "E.g., A product shipping that costs %d <?php echo $a2w_local_currency; ?> would have its price set to %d <?php echo $a2w_local_currency; ?> (%d %s %d = %d).";
            let helpText = rule_info_box_calculation(
                helpTextTpl, get_el_sign_value($('.js-default-prices ul.sign')), get_value()
            );
            $(this).attr('data-title', helpText);
            if(jQuery.fn.tooltip) { $(this).tooltip('fixTitle').tooltip('show'); }
        });

        //default rule dropdown
        $(".global-pricing .dropdown").on("click", function () {
            $(this).next().slideToggle();
        });

        $('.a2wl-content').on("click", ".global-pricing .dropdown-menu li", function (e) {
            e.preventDefault();

            $(this).trigger('change');
            let sign = $(this).attr('data-sign');

            const input = $(this).parents('.price-dropdown-group').find('input[type="text"]');

            $(input).attr('sign', sign)

            /*    let svg = $(this).closest('.input-group').prev('svg');
                svg = svg.length > 0 ? svg : $(this).closest('td').prev('td').find("svg");
                svg = svg.length > 0 ? svg : $(this).closest('.row').find('svg.sign');*/

            $('input[name="default_rule[sign]"]').val(sign);

            /* if (sign == '=') {
                 svg.removeClass('icon-equal icon-plus icon-rotate45').addClass('icon-equal');
                 svg.children('use').attr('xlink:href', '#icon-equal');
             }
             else if (sign == '*') {
                 svg.removeClass('icon-equal icon-plus icon-rotate45').addClass('icon-plus icon-rotate45');
                 svg.children('use').attr('xlink:href', '#icon-plus');
             }
             else if (sign == '+') {
                 svg.removeClass('icon-equal icon-plus icon-rotate45').addClass('icon-plus');
                 svg.children('use').attr('xlink:href', '#icon-plus');
             }*/

            $(this).hide().siblings().each(function () {
                $(this).show()
            });
            $(this).parent().fadeOut().prev().html($(this).text());
        });

        $('.a2wl-content form').on('submit', function () {

            if ($(this).find('.has-error').length > 0)
                return false;
        });

        $("#a2wl_aliship_product_enable").on('change', function () {

            var checked_status = $(this).is(':checked');

            $("#a2wl_aliship_product_not_available_message").closest('.row').toggle(checked_status);
            $("#a2wl_aliship_product_position").closest('.row').toggle(checked_status);

            return true;
        });

        $("#a2wl_aliship_not_available_remove").on('change', function () {
            var checked_status = !$(this).is(':checked');

            $("#a2wl_aliship_not_available_cost").closest('.row').toggle(checked_status);
            $("#a2wl_aliship_not_available_time_min").closest('.row').toggle(checked_status);
            $("#a2wl_aliship_not_available_time_max").closest('.row').toggle(checked_status);


            return true;
        });



        const $frontendCheckbox = $('#a2wl_aliship_frontend');
        const $panel = $frontendCheckbox.closest('.panel-body');

        function togglePanelElements() {
            const enabled = $frontendCheckbox.is(':checked');
            $panel.find('input, select, textarea, button')
                .not($frontendCheckbox)
                .prop('disabled', !enabled);
        }

        $frontendCheckbox.on('change', togglePanelElements);


        //set init states:

        if ( !$("#a2wl_aliship_product_enable").is(':checked') ) {
            $("#a2wl_aliship_product_enable").trigger('change');
        }

        if ( $("#a2wl_aliship_not_available_remove").is(':checked') ) {
            $("#a2wl_aliship_not_available_remove").trigger('change');
        }

        togglePanelElements();
        toggleDeliveryTimePanel();

    })(jQuery);
</script>
