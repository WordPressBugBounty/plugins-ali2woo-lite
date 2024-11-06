<?php
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
use AliNext_Lite\AbstractController;
use function AliNext_Lite\get_setting;
use AliNext_Lite\ProductShippingMeta;
use AliNext_Lite\Attachment;
?>
<div class="a2wl_product_tab_menu">
    <ul class="subsubsub" style="float: initial;margin-left:12px">
        <li><a href="#" data-tab="general" class="current">General</a> | </li>
        <li><a href="#" data-tab="variations">Manage Variations</a></li>
    </ul>
    <script>
        jQuery(".a2wl_product_tab_menu li a").click(function () {
            jQuery(".a2wl_product_tab_menu li a").removeClass('current');
            jQuery(this).addClass('current');

            jQuery(".a2wl_product_tab").hide();
            jQuery(".a2wl_product_tab."+jQuery(this).data('tab')).show();
            return false;
        });
    </script>
</div>

<div class="a2wl_product_tab general">
    <?php $external_id = get_post_meta($post_id, '_a2w_external_id', true); ?>

    <div class="options_group">
        <?php
        woocommerce_wp_text_input([
            'id' => '_a2w_external_id',
            'value' => $external_id,
            'label' => esc_html__('External Id', 'ali2woo'),
            'desc_tip' => true,
            'description' => esc_html__('External Aliexpress Product Id', 'ali2woo'),
        ]);

        woocommerce_wp_text_input([
            'id' => '_a2w_orders_count',
            'value' => get_post_meta($post_id, '_a2w_orders_count', true),
            'label' => esc_html__('Orders count', 'ali2woo'),
            'desc_tip' => true,
            'description' => esc_html__('Aliexpress orders count', 'ali2woo'),
            'custom_attributes' => ['readonly'=>'readonly'],
        ]);

        $disable_sync = get_post_meta($post_id, '_a2w_disable_sync', true);

        woocommerce_wp_checkbox([
            'id' => '_a2w_disable_sync',
            'value' => $disable_sync ? 'yes' : 'no',
            'label' => esc_html__('Disable synchronization?', 'ali2woo'),
            'description' => esc_html__('Disable global synchronization for this product', 'ali2woo'),
        ]);
        ?>

        <script>
            let a2wFieldsSelector = "._a2wl_disable_var_price_change_field, " +
                "._a2wl_disable_var_quantity_change_field, " +
                "._a2wl_disable_add_new_variants";

            jQuery(document).ready(function($) {
                $("#_a2wl_disable_sync").on('change', function () {
                    if ($(this).is(":checked")) {
                        $(a2wFieldsSelector).hide();
                    } else {
                        $(a2wFieldsSelector).show();
                    }
                });
            });
        </script>

        <?php
        woocommerce_wp_checkbox(array(
            'id' => '_a2w_disable_var_price_change',
            'value' => get_post_meta($post_id, '_a2w_disable_var_price_change', true) ? 'yes' : 'no',
            'label' => esc_html__('Disable price change?', 'ali2woo'),
            'description' => esc_html__('Disable variations price change', 'ali2woo'),
        ));
        woocommerce_wp_checkbox(array(
            'id' => '_a2w_disable_var_quantity_change',
            'value' => get_post_meta($post_id, '_a2w_disable_var_quantity_change', true) ? 'yes' : 'no',
            'label' => esc_html__('Disable quantity change?', 'ali2woo'),
            'description' => esc_html__('Disable variations quantity change', 'ali2woo'),
        ));
        woocommerce_wp_checkbox(array(
            'id' => '_a2w_disable_add_new_variants',
            'value' => get_post_meta($post_id, '_a2w_disable_add_new_variants', true) ? 'yes' : 'no',
            'label' => esc_html__('Disable add new variants?', 'ali2woo'),
            'description' => esc_html__('Disable add new variants if they appear.', 'ali2woo'),
        ));

        if ($disable_sync) {
            ?>
            <script>
                jQuery(document).ready(function($) {
                    $(a2wFieldsSelector).hide();
                });
            </script>
            <?php
        }
        ?>

        <?php
        $product_url = get_post_meta($post_id, '_a2w_product_url', true);
        if ($product_url) :
            ?>
            <p class="form-field">
                <label><?php esc_html_e('Product url', 'ali2woo'); ?></label>
                <a target="_blank" href="<?php echo $product_url; ?>"><?php echo $product_url; ?></a>
            </p>
        <?php endif; ?>

        <?php
        $original_product_url = get_post_meta($post_id, '_a2w_original_product_url', true);
        if ($original_product_url) :
            ?>
            <p class="form-field">
                <label><?php  esc_html_e('Original product url', 'ali2woo'); ?></label>
                <a target="_blank" href="<?php echo $original_product_url; ?>"><?php echo $original_product_url; ?></a>
            </p>
        <?php endif; ?>
    </div>


    <?php if (get_setting('add_shipping_to_price')) : ?>
        <div class="options_group">
            <?php
            // save shipping meta data
            $shipping_meta = new ProductShippingMeta($post_id);
            $shipping_cost = $shipping_meta->get_cost();
            $shipping_country_from = $shipping_meta->get_country_from();
            $shipping_country_from_list = ProductShippingMeta::get_country_from_list($post_id);
            $shipping_country_to = $shipping_meta->get_country_to();
            $shipping_method = $shipping_meta->get_method();

            $shiping_info = "";
            if($shipping_country_to && $shipping_method){
                $shiping_info = $shipping_country_from.", ".$shipping_country_to.", ".$shipping_method.", ".($shipping_cost ? $shipping_cost : 'free');
                $items = $shipping_meta->get_items(1,$shipping_country_from, $shipping_country_to);
                if($items){
                    foreach($items as $item){
                        if($item['serviceName'] == $shipping_method){
                            $shiping_info = $shipping_country_from.", ".$shipping_country_to.", ".$item['company'].", ".($shipping_cost ? (isset($item['freightAmount']['formatedAmount'])?$item['freightAmount']['formatedAmount']:$item['freightAmount']['value']) : 'free');
                            break;
                        }
                    }
                }
            }
            ?>
            <p class="form-field a2wl-shiping-data"  data-country_from="<?php echo $shipping_country_from; ?>" data-country_to="<?php echo $shipping_country_to; ?>" data-shipping_method="<?php echo $shipping_method; ?>">
                <label><?php  esc_html_e('Shipping', 'ali2woo'); ?></label>
                <span><span class="a2wl-shiping-info"><?php echo $shiping_info; ?></span> <a href="#" class="a2wl-shipping-update">Select shipping</a> / <a href="#" class="a2wl-shipping-remove">Reset</a></span>
                <span class="woocommerce-help-tip" data-tip="This shipping cost will be included to the product price according to pricing rules"></span>
            </p>
            <script>
                (function ($) {
                    let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                    let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';

                    $(".a2wl-shipping-remove").on("click", function () {
                        if (confirm("Are you sure you want to reset shipping info?")) {
                            const data = {
                                'action': 'a2wl_remove_product_shipping_info',
                                'id': '<?php echo $post_id; ?>',
                                "ali2woo_nonce": nonce_action,
                            };
                            $.post(ajaxurl, data).done(function (response) {
                                const json = JSON.parse(response);
                                if (json.state !== 'ok') {
                                    console.log(json);
                                } else {
                                    $('.a2wl-shiping-info').html('');
                                }
                            }).fail(function (xhr, status, error) {
                                console.log(error);
                            });
                        }
                        return false;
                    });
                    $(".a2wl-shipping-update").on("click", function () {
                        const onSelectCallback = function (product_id, items, country_from, country_to, method) {
                            if (method && items) {
                                $('.a2wl-shiping-data').data('country_from', country_from)
                                $('.a2wl-shiping-data').data('country_to', country_to)
                                $('.a2wl-shiping-data').data('shipping_method', method)

                                $.each(items, function (i, item) {
                                    if (item.serviceName == method) {
                                        const cost = item.previewFreightAmount?item.previewFreightAmount.value:item.freightAmount.value
                                        const data = {
                                            'action': 'a2wl_update_product_shipping_info',
                                            'id': '<?php echo $post_id; ?>',
                                            country_from,
                                            country_to,
                                            method,
                                            cost,
                                            items,
                                            "ali2woo_nonce": nonce_action,
                                        };
                                        $.post(ajaxurl, data).done(function (response) {
                                            const json = JSON.parse(response);
                                            if (json.state !== 'ok') {
                                                console.log(json);
                                            } else {
                                                $('.a2wl-shiping-info').html(country_from+", "+country_to+", "+method+", "+(cost?(item.freightAmount.formatedAmount?item.freightAmount.formatedAmount:item.freightAmount.value):'free'));
                                            }
                                        }).fail(function (xhr, status, error) {
                                            console.log(error);
                                        });
                                    }
                                });
                            }
                        }

                        const country_from = $('.a2wl-shiping-data').data('country_from')
                        const country_to = $('.a2wl-shiping-data').data('country_to')
                        const shipping_method = $('.a2wl-shiping-data').data('shipping_method')

                        if (!country_to || !shipping_method) {
                            fill_modal_shipping_info('<?php echo $external_id; ?>', <?php echo wp_json_encode($shipping_country_from_list); ?>, country_from, '', null, 'product', '', onSelectCallback);
                        } else {
                            $('.modal-shipping .shipping-method').html('<div class="a2wl-load-container"><div class="a2wl-load-speeding-wheel"></div></div>');
                            a2wl_load_shipping_info('<?php echo $external_id; ?>', country_from, country_to, 'product', function (state, items, default_method, shipping_cost, variations) {
                                fill_modal_shipping_info('<?php echo $external_id; ?>', <?php echo wp_json_encode($shipping_country_from_list); ?>, country_from, country_to, items, 'product', shipping_method, onSelectCallback);
                            })
                        }

                        $(".modal-shipping").addClass('opened');
                        return false;
                    });
                })(jQuery);
            </script>
        </div>
    <?php endif; ?>

    <div class="options_group">
        <?php $last_update = get_post_meta($post_id, '_a2w_last_update', true); ?>
        <p class="form-field _a2wl_last_update_field ">
            <label>Last update</label>
            <?php if($last_update): ?>
                <span><?php echo gmdate("F j, Y, H:i:s", $last_update); ?> <a href="#clean" id="_a2w_last_update_clean">Clean</a></span>
            <?php else: ?>
                <span>Not set</span>
            <?php endif; ?>
            <span class="woocommerce-help-tip" data-tip="Last update"></span>
            <input type="hidden" class="" name="_a2w_last_update" id="_a2w_last_update" value="<?php echo $last_update;?>" />
        </p>
        <script>
            (function ($) {
                let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';

                $("#_a2wl_last_update_clean").on('click', function () {
                    $("#_a2wl_last_update").val("");
                    $(this).parents("span").html("Not set");
                    $.post(ajaxurl, {
                        "action": "a2wl_data_last_update_clean",
                        "post_id": <?php echo $post_id; ?>,
                        "type": "product",
                        "ali2woo_nonce": nonce_action,
                    });

                    return false;
                });
            })(jQuery);
        </script>

        <?php $reviews_last_update = get_post_meta($post_id, '_a2w_reviews_last_update', true); ?>
        <p class="form-field _a2wl_reviews_last_update_field ">
            <label>Reviews last update</label>
            <?php if($reviews_last_update): ?>
                <span><?php echo gmdate("F j, Y, H:i:s", $reviews_last_update); ?> <a href="#clean" id="_a2w_reviews_last_update_clean">Clean</a></span>
            <?php else: ?>
                <span>Not set</span>
            <?php endif; ?>
            <span class="woocommerce-help-tip" data-tip="Last update"></span>
            <input type="hidden" class="" name="_a2w_reviews_last_update" id="_a2w_reviews_last_update" value="<?php echo $reviews_last_update;?>" />
        </p>
        <script>
            (function ($) {
                let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';

                $("#_a2wl_reviews_last_update_clean").on('click', function () {
                    $("#_a2wl_reviews_last_update").val("");
                    $(this).parents("span").html("Not set");
                    $.post(ajaxurl, {
                        "action": "a2wl_data_last_update_clean",
                        "post_id": <?php echo $post_id; ?>,
                        "type": "review",
                        "ali2woo_nonce": nonce_action,
                    });

                    return false;
                });
            })(jQuery);
        </script>
    </div>

    <?php
    // load external images
    $images_ids = Attachment::find_external_images(1000, $post_id);
    ?>
    <?php if($images_ids):?>
        <div class="options_group">
            <p id="a2wl_product_external_images" class="form-field">
                <label>External images</label>
                <button type="button" class="load-images button button-primary" data-images="<?php echo implode(',',$images_ids); ?>">Load external images</button>
                <span class="description progress"></span>
            </p>
        </div>
    <?php endif;?>
</div>

<div class="a2wl_product_tab variations" style="display:none">
    <div class="options_group">
        <p class="form-field _a2wl_deleted_variations_attributes">
            <label for="_a2w_deleted_variations_attributes">Removed attributes</label>
            <span id="_a2w_deleted_variations_attributes">
                <?php
                $deleted_variations_attributes = get_post_meta($post_id, '_a2w_deleted_variations_attributes', true);
                if (empty($deleted_variations_attributes)) {
                    echo '<i>' . esc_html__('No deleted attributes of variations', 'ali2woo') . '</i>';
                } else {
                    foreach ($deleted_variations_attributes as $ka => $av) {
                        echo '<span class="va" style="display: inline-block;margin-right:10px;margin-bottom: 5px;background-color: #eee;padding: 0px 10px;" data-attr-id="' . urldecode($ka) . '"><i>' . $av['current_name'] . '</i> <a href="#" style="text-decoration: none;"><span class="dashicons dashicons-trash"></span></a></span> ';
                    }
                }
                ?>
            </span>
        </p>
        <script>
            (function ($) {
                let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';

                $("#_a2wl_deleted_variations_attributes > span > a").on('click', function () {
                    let this_v_a = $(this).parents("span.va");
                    $.post(ajaxurl, {
                        "action": "a2wl_data_remove_deleted_attribute",
                        "post_id":<?php echo $post_id; ?>,
                        "id": $(this_v_a).attr("data-attr-id"),
                        "ali2woo_nonce": nonce_action,
                    }).done(function (response) {
                        $(this_v_a).remove();
                        if ($("#_a2wl_deleted_variations_attributes > span").length == 0) {
                            $("#_a2wl_deleted_variations_attributes").html("<i><?php  esc_html_e('No deleted attributes of variations', 'ali2woo'); ?></i>");
                        }
                    }).fail(function (xhr, status, error) {
                        console.log(error);
                    });

                    return false;
                });
            })(jQuery);
        </script>
    </div>


    <div class="options_group">
        <p class="form-field _a2wl_deleted_variations">
            <label for="_a2w_deleted_variations">Removed variations</label>
            <span id="_a2w_deleted_variations">
            <?php
            $skip_meta = get_post_meta($post_id, "_a2w_skip_meta", true);
            if (!empty($skip_meta['skip_vars']) && is_array($skip_meta['skip_vars'])) {
                echo '<span class="var" style="display: inline-block;margin-right:10px;margin-bottom: 5px;background-color: #eee;padding: 0px 10px;" data-attr-id="all"><a href="#" style="text-decoration: none;">RESET ALL <span class="dashicons dashicons-trash"></span></a></span> ';
                foreach ($skip_meta['skip_vars'] as $v) {
                    echo '<span class="var" style="display: inline-block;margin-right:10px;margin-bottom: 5px;background-color: #eee;padding: 0px 10px;" data-attr-id="' . $v . '"><i>' . $v . '</i> <a href="#" style="text-decoration: none;"><span class="dashicons dashicons-trash"></span></a></span> ';
                }
            } else {
                echo '<i>' . esc_html__('No deleted variations', 'ali2woo') . '</i>';
            }
            ?>
            </span>
        </p>
        <script>
            (function ($) {
                let ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                let nonce_action = '<?php echo wp_create_nonce(AbstractController::AJAX_NONCE_ACTION); ?>';

                $("#_a2wl_deleted_variations > span > a").on('click', function () {
                    let this_v_a = $(this).parents("span.var");
                    let var_id = $(this_v_a).attr("data-attr-id");
                    if (var_id!='all' || confirm("Are you sure you want to reset all variations?")) {
                        $.post(ajaxurl, {
                            "action": "a2wl_data_remove_deleted_variation",
                            "post_id": <?php echo $post_id; ?>,
                            "id": var_id,
                            "ali2woo_nonce": nonce_action,
                        }).done(function (response) {
                            $(this_v_a).remove();
                            if (var_id=='all' || $("#_a2wl_deleted_variations > span").length==0) {
                                $("#_a2wl_deleted_variations").html("<i>No deleted variations</i>");
                            }
                        }).fail(function (xhr, status, error) {
                            console.log(error);
                        });
                    }

                    return false;
                });
            })(jQuery);
        </script>
    </div>

</div>

<div class="a2wl-content">
    <?php include_once 'includes/shipping_modal.php'; ?>
</div>
