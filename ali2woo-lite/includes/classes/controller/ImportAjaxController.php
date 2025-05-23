<?php

/**
 * Description of ImportAjaxController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 *
 * @ajax: true
 */
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
namespace AliNext_Lite;;

use Throwable;

class ImportAjaxController extends AbstractController
{
    protected ProductImport $ProductImportModel;
    protected Woocommerce $WoocommerceModel;
    protected ProductReviewsService $ProductReviewsService;
    protected Override $OverrideModel;
    protected Aliexpress $AliexpressModel;
    protected SplitProductService $SplitProductService;

    protected ProductShippingDataService $ProductShippingDataService;
    protected ImportListService $ImportListService;
    protected ProductService $ProductService;

    protected PriceFormulaService $PriceFormulaService;
    protected ImportedProductServiceFactory $ImportedProductServiceFactory;
    protected WoocommerceService $WoocommerceService;
    protected WoocommerceCategoryService $WoocommerceCategoryService;

    public function __construct(
        ProductImport $ProductImportModel, Woocommerce $WoocommerceModel, ProductReviewsService $ProductReviewsService,
        Override $OverrideModel, Aliexpress $AliexpressModel, SplitProductService $SplitProductService,
        ProductShippingDataService $ProductShippingDataService, ImportListService $ImportListService,
        ProductService $ProductService, PriceFormulaService $PriceFormulaService,
        ImportedProductServiceFactory $ImportedProductServiceFactory, WoocommerceService $WoocommerceService,
        WoocommerceCategoryService $WoocommerceCategoryService
    ) {
        parent::__construct();

        $this->ProductImportModel = $ProductImportModel;
        $this->WoocommerceModel = $WoocommerceModel;
        $this->ProductReviewsService = $ProductReviewsService;
        $this->OverrideModel = $OverrideModel;
        $this->AliexpressModel = $AliexpressModel;
        $this->SplitProductService = $SplitProductService;
        $this->ProductShippingDataService = $ProductShippingDataService;
        $this->ImportListService = $ImportListService;
        $this->ProductService = $ProductService;
        $this->PriceFormulaService = $PriceFormulaService;
        $this->ImportedProductServiceFactory = $ImportedProductServiceFactory;
        $this->WoocommerceService = $WoocommerceService;
        $this->WoocommerceCategoryService = $WoocommerceCategoryService;

        add_filter('a2wl_woocommerce_after_add_product', array($this, 'woocommerce_after_add_product'), 30, 4);
        add_action('wp_ajax_a2wl_push_product', [$this, 'ajax_push_product']);
        add_action('wp_ajax_a2wl_delete_import_products', [$this, 'ajax_delete_import_products']);
        add_action('wp_ajax_a2wl_update_product_info', [$this, 'ajax_update_product_info']);
        add_action('wp_ajax_a2wl_link_to_category', [$this, 'ajax_link_to_category']);
        add_action('wp_ajax_a2wl_link_to_aliexpress_category', [$this, 'ajax_link_to_aliexpress_category']);
        add_action('wp_ajax_a2wl_get_all_products_to_import', [$this, 'ajax_get_all_products_to_import']);
        add_action('wp_ajax_a2wl_get_product', [$this, 'ajax_get_product']);
        add_action('wp_ajax_a2wl_split_product', [$this, 'ajax_split_product']);
        add_action('wp_ajax_a2wl_import_images_action', [$this, 'ajax_import_images_action']);
        add_action('wp_ajax_a2wl_import_cancel_images_action', [$this, 'ajax_import_cancel_images_action']);
        add_action('wp_ajax_a2wl_search_tags', [$this, 'ajax_search_tags']);
        add_action('wp_ajax_a2wl_search_products', [$this, 'ajax_search_products']);
        add_action('wp_ajax_a2wl_override_product', [$this, 'ajax_override_product']);
        add_action('wp_ajax_a2wl_override_variations', [$this, 'ajax_override_variations']);
        add_action('wp_ajax_a2wl_cancel_override_product', [$this, 'ajax_cancel_override_product']);
        add_action('wp_ajax_a2wl_add_to_import', [$this, 'ajax_add_to_import']);
        
        add_action('wp_ajax_a2wl_remove_from_import', [$this, 'ajax_remove_from_import']);
        add_action('wp_ajax_a2wl_load_shipping_info', [$this, 'ajax_load_shipping_info']);
        add_action('wp_ajax_a2wl_set_shipping_info', [$this, 'ajax_set_shipping_info']);
        add_action('wp_ajax_a2wl_update_shipping_list', [$this, 'ajax_update_shipping_list']);
    }

    public function woocommerce_after_add_product($result, $product_id, $product, $params)
    {
        $product_import_model = new ProductImport();
        // remove product from process list and from import list
        $product_import_model->del_product($product['import_id'], true);
        $product_import_model->del_product($product['import_id']);
        return $result;
    }

    public function ajax_push_product(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        a2wl_init_error_handler();

        $result = ResultBuilder::buildOk();

        $product_import_model = $this->ProductImportModel;
        $woocommerce_model = $this->WoocommerceModel;

        $background_import = get_setting('background_import', true);

        if ($background_import) {
            // NEW import method (in background)
            if (isset($_POST['id']) && $_POST['id']) {
                $product = $product_import_model->get_product($_POST['id']);
                if ($product) {
                    try {
                        $ts = microtime(true);

                        $steps = $woocommerce_model->build_steps($product);

                        // process first step
                        $result = $woocommerce_model->add_product($product, array('step' => 'init'));
                        unset($steps[array_search('init', $steps)]);

                        if ($result['state'] !== 'error') {
                            // write first step log
                            a2wl_info_log("IMPORT[time: " . (microtime(true) - $ts) . ", id:" . $result['product_id'] . ", extId: " . $_POST['id'] . ", step: init]");

                            // move product to processing list
                            $product_import_model->move_to_processing($_POST['id']);

                            // process all other steps
                            $product_queue = ImportProcess::create_new_queue($result['product_id'], $_POST['id'], $steps, false);
                            if (get_setting('load_review')) {
                                ImportProcess::create_new_queue($result['product_id'], $_POST['id'], array('reviews'), false);
                            }

                            $product_queue->dispatch();
                        }
                    } catch (Throwable $e) {
                        a2wl_print_throwable($e);
                        $result = ResultBuilder::buildError($e->getMessage());
                    }
                } else {
                    $result = ResultBuilder::buildError("Product " . $_POST['id'] . " not find.");
                }
            } else {
                $result = ResultBuilder::buildError("import_product: waiting for ID...");
            }
        } else {
            // Old import method (non-background import method)
            $this->prepareSystemForImport();

            try {
                if (isset($_POST['id']) && $_POST['id']) {
                    $product = $product_import_model->get_product($_POST['id']);

                    if ($product) {
                        $import_wc_product_id = $woocommerce_model->get_product_id_by_import_id($product['import_id']);

                        if (!get_setting('allow_product_duplication') && $import_wc_product_id) {
                            $result = $woocommerce_model->upd_product($import_wc_product_id, $product);
                        } else {
                            $result = $woocommerce_model->add_product($product);
                        }

                        $product_id = false;
                        if ($result['state'] !== 'error') {
                            $product_id = $result['product_id'];
                            $product_import_model->del_product($_POST['id']);
                            $result = ResultBuilder::buildOk(['product_id' => $product_id]);
                        } else {
                            $result = ResultBuilder::buildError($result['message']);
                        }

                        if ($result['state'] !== 'error' && get_setting('load_review')) {
                            $this->ProductReviewsService->loadReviewsForProductIds([$product_id]);
                            //make sure that post comment status is 'open'
                            wp_update_post(array('ID' => $product_id, 'comment_status' => 'open'));
                        }
                        if ($result['state'] === 'error') {
                            $result = ResultBuilder::buildError($result['message']);
                        }

                    } else {
                        $result = ResultBuilder::buildError("Product " . $_POST['id'] . " not find.");
                    }
                } else {
                    $result = ResultBuilder::buildError("import_product: waiting for ID...");
                }

                restore_error_handler();
            } catch (Throwable $e) {
                a2wl_print_throwable($e);
                $result = ResultBuilder::buildError($e->getMessage());
            }
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_delete_import_products(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        a2wl_init_error_handler();
        try {
            if (isset($_POST['ids']) && $_POST['ids']) {
                $product_import_model = $this->ProductImportModel;
                $product_import_model->del_product($_POST['ids']);
            }
            $result = ResultBuilder::buildOk();
            restore_error_handler();
        } catch (Throwable $e) {
            a2wl_print_throwable($e);
            $result = ResultBuilder::buildError($e->getMessage());
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_update_product_info(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        a2wl_init_error_handler();
        try {
            $product_import_model = $this->ProductImportModel;
            $out_data = array();
            if (isset($_POST['id']) && $_POST['id'] && ($product = $product_import_model->get_product($_POST['id']))) {
                if (isset($_POST['title']) && $_POST['title']) {
                    if (!isset($product['original_title'])) {
                        $product['original_title'] = $product['title'];
                    }
                    // $product['title'] = stripslashes($_POST['title']);
                    $product['title'] = sanitize_text_field($_POST['title']);
                }

                if (!empty($_POST['sku'])) {
                    $product['sku'] = stripslashes($_POST['sku']);
                }

                if (isset($_POST['type']) && $_POST['type'] && in_array($_POST['type'], array('simple', 'external'))) {
                    $product['product_type'] = $_POST['type'];
                }

                if (isset($_POST['status']) && $_POST['status'] && in_array($_POST['status'], array('publish', 'draft'))) {
                    $product['product_status'] = $_POST['status'];
                }

                if (isset($_POST['tags']) && $_POST['tags']) {
                    $product['tags'] = $_POST['tags'] ? array_map('sanitize_text_field', $_POST['tags']) : array();
                }

                if (!empty($_POST['attr_names'])) {
                    foreach ($_POST['attr_names'] as $attr) {
                        foreach ($product['sku_products']['attributes'] as &$product_attr) {
                            if ($product_attr['id'] == $attr['id']) {
                                if (!isset($product_attr['original_name'])) {
                                    $product_attr['original_name'] = $product_attr['name'];
                                }
                                $product_attr['name'] = $attr['value'];
                                break;
                            }
                        }
                    }
                }

                if (isset($_POST['categories'])) {
                    $product['categories'] = array();
                    if ($_POST['categories']) {
                        foreach ($_POST['categories'] as $cat_id) {
                            if (intval($cat_id)) {
                                $product['categories'][] = intval($cat_id);
                            }
                        }
                    }

                }

                if (isset($_POST['description'])) {
                    $product['description'] = stripslashes(trim(urldecode($_POST['description'])));
                }

                if (isset($_POST['skip_vars']) && $_POST['skip_vars']) {
                    $product['skip_vars'] = $_POST['skip_vars'];
                }

                if (isset($_POST['reset_skip_vars']) && $_POST['reset_skip_vars']) {
                    $product['skip_vars'] = array();
                }

                if (isset($_POST['skip_images']) && $_POST['skip_images']) {
                    $product['skip_images'] = $_POST['skip_images'];
                }

                if (!empty($_POST['no_skip'])) {
                    $product['skip_images'] = array();
                }

                if (isset($_POST['thumb'])) {
                    $product['thumb_id'] = $_POST['thumb'];
                }

                if (isset($_POST['specs'])) {
                    $product['attribute'] = array();
                    $split_attribute_values = get_setting('split_attribute_values');
                    $attribute_values_separator = get_setting('attribute_values_separator');
                    foreach ($_POST['specs'] as $attr) {
                        $name = trim($attr['name']);
                        if (!empty($name)) {
                            $el = array('name' => $name);
                            if ($split_attribute_values) {
                                $el['value'] = array_map('trim', explode($attribute_values_separator, $attr['value']));
                            } else {
                                $el['value'] = array($attr['value']);
                            }
                            $product['attribute'][] = $el;
                        }
                    }
                } else if (!empty($_POST['cleanSpecs'])) {
                    $product['attribute'] = array();
                }

                if (isset($_POST['disable_var_price_change'])) {
                    if (intval($_POST['disable_var_price_change'])) {
                        $product['disable_var_price_change'] = true;
                    } else {
                        $product['disable_var_price_change'] = false;
                    }
                }

                if (isset($_POST['disable_var_quantity_change'])) {
                    if (intval($_POST['disable_var_quantity_change'])) {
                        $product['disable_var_quantity_change'] = true;
                    } else {
                        $product['disable_var_quantity_change'] = false;
                    }
                }

                if (!empty($_POST['variations'])) {
                    $out_data['new_attr_mapping'] = array();
                    foreach ($_POST['variations'] as $variation) {
                        foreach ($product['sku_products']['variations'] as &$v) {
                            if ($v['id'] == $variation['variation_id']) {
                                if (isset($variation['regular_price'])) {
                                    $v['calc_regular_price'] = floatval($variation['regular_price']);
                                }
                                if (isset($variation['price'])) {
                                    $v['calc_price'] = floatval($variation['price']);
                                }
                                if (isset($variation['quantity'])) {
                                    $v['quantity'] = intval($variation['quantity']);
                                }

                                if (isset($variation['sku']) && $variation['sku']) {
                                    $v['sku'] = sanitize_text_field($variation['sku']);
                                }

                                if (isset($variation['attributes']) && is_array($variation['attributes'])) {
                                    foreach ($variation['attributes'] as $a) {
                                        foreach ($v['attributes'] as $i => $av) {
                                            $_attr_val = false;
                                            foreach ($product['sku_products']['attributes'] as $tmp_attr) {
                                                if (isset($tmp_attr["value"][$av])) {
                                                    $_attr_val = $tmp_attr["value"][$av];
                                                    break;
                                                }
                                            }
                                            $old_name = sanitize_text_field($_attr_val['name']);
                                            $new_name = sanitize_text_field($a['value']);
                                            if ($old_name !== $new_name && $_attr_val['id'] == $a['id']) {
                                                $_attr_id = explode(':', $av);
                                                $attr_id = $_attr_id[0];
                                                $new_attr_id = $attr_id . ':' . md5($variation['variation_id'] . $new_name);
                                                if ($av !== $new_attr_id) {
                                                    $out_data['new_attr_mapping'][] = array('variation_id' => $variation['variation_id'], 'old_attr_id' => $av, 'new_attr_id' => $new_attr_id);
                                                }
                                                foreach ($product['sku_products']['attributes'] as $ind => $orig_attr) {
                                                    if ($orig_attr['id'] == $attr_id) {
                                                        if (!isset($orig_attr['value'][$new_attr_id])) {
                                                            $product['sku_products']['attributes'][$ind]['value'][$new_attr_id] = $product['sku_products']['attributes'][$ind]['value'][$av];
                                                            if (!isset($product['sku_products']['attributes'][$ind]['value'][$new_attr_id]['original_id'])) {
                                                                $product['sku_products']['attributes'][$ind]['value'][$new_attr_id]['original_id'] = $product['sku_products']['attributes'][$ind]['value'][$new_attr_id]['id'];
                                                            }
                                                            $product['sku_products']['attributes'][$ind]['value'][$new_attr_id]['id'] = $new_attr_id;
                                                            $product['sku_products']['attributes'][$ind]['value'][$new_attr_id]['name'] = $new_name;
                                                            if (!isset($product['sku_products']['attributes'][$ind]['value'][$new_attr_id]['src_id'])) {
                                                                $product['sku_products']['attributes'][$ind]['value'][$new_attr_id]['src_id'] = $av;
                                                            }
                                                        }
                                                        break;
                                                    }
                                                }

                                                $v['attributes'][$i] = $new_attr_id;
                                                $v['attributes_names'][$i] = sanitize_text_field($a['value']);
                                            }
                                        }
                                    }
                                }

                                break;
                            }
                        }
                    }
                }

                $product_import_model->upd_product($product);
                $result = ResultBuilder::buildOk($out_data);
            } else {
                $result = ResultBuilder::buildError("update_product_info: waiting for ID...");
            }

            restore_error_handler();
        } catch (Throwable $e) {
            a2wl_print_throwable($e);
            $result = ResultBuilder::buildError($e->getMessage());
        }
        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_link_to_category(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $product_import_model = $this->ProductImportModel;

        if (!empty($_POST['categories']) && !empty($_POST['ids'])) {
            $new_categories = is_array($_POST['categories']) ?
                array_map('intval', $_POST['categories']) :
                [intval($_POST['categories'])];

            $ids = ($_POST['ids'] === 'all') ?
                $product_import_model->get_product_id_list() :
                (is_array($_POST['ids']) ? $_POST['ids'] : [$_POST['ids']]);

            foreach ($ids as $id) {
                if ($product = $product_import_model->get_product($id)) {
                    $product['categories'] = $new_categories;
                    $product_import_model->upd_product($product);
                }
            }
            set_setting('remember_categories', $new_categories);
        } else if (empty($_POST['categories'])) {
            del_setting('remember_categories');
        }

        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }

    public function ajax_link_to_aliexpress_category(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $ProductImportModel = $this->ProductImportModel;

        $defaultCategoryIds = false;
        if (!empty($_POST['categories'])) {
            $defaultCategoryIds = is_array($_POST['categories']) ?
                array_map('intval', $_POST['categories']) :
                [intval($_POST['categories'])];
        }

        if (!empty($_POST['id'])) {
            $id = intval($_POST['id']);

            if ($Product = $ProductImportModel->get_product($id)) {
                $aliexpressCategoryId = $Product['category_id'];
                if ($aliexpressCategoryId) {
                    a2wl_init_error_handler();
                    try {
                        $insertedCategoryIds = $this->WoocommerceCategoryService->loadAliexpressCategory(
                            $aliexpressCategoryId
                        );
                        restore_error_handler();
                    } catch (ApiException $Exception) {

                        echo wp_json_encode(
                            ResultBuilder::buildError(
                                $Exception->getMessage(),
                                ['error_code' => $Exception->getCode()]
                            )
                        );
                        wp_die();
                    }

                    if (empty($insertedCategoryIds)) {
                       $selectedCategoryIds = $defaultCategoryIds;
                   } else {
                       //todo: now we sent last category as selected category for the product
                       $selectedCategoryIds = [end($insertedCategoryIds)];
                       //but we can put product to parent categories as well for example:
                       //$selectedCategoryIds = $insertedCategoryIds;
                   }

                   if ($selectedCategoryIds) {
                       $Product['categories'] = $selectedCategoryIds;
                       $ProductImportModel->upd_product($Product);
                   }
                }
            }
        }

        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }

    public function ajax_get_all_products_to_import(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $product_import_model = $this->ProductImportModel;

        echo wp_json_encode(ResultBuilder::buildOk(
            ['ids' => $product_import_model->get_product_id_list()]
        ));
        wp_die();
    }

    public function ajax_get_product(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $product_import_model = $this->ProductImportModel;

        if (!empty($_POST['id'])) {
            if ($product = $product_import_model->get_product($_POST['id'])) {
                $result = ResultBuilder::buildOk(array('product' => $product));
            } else {
                $result = ResultBuilder::buildError("product not found");
            }
        } else {
            $result = ResultBuilder::buildError("get_product: waiting for ID...");
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_split_product(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $result = ResultBuilder::buildOk();

        if (!empty($_POST['id']) && !empty($_POST['attr'])) {
            $productId = $_POST['id'];
            $attributeId = $_POST['attr'];
            a2wl_init_error_handler();
            try {
                $this->SplitProductService->splitProductByAttribute($productId, $attributeId);
                restore_error_handler();
            }
            catch (ServiceException $Exception) {
                a2wl_print_throwable($Exception);
                $result = ResultBuilder::buildError($Exception);
            }
        } else if (!empty($_POST['id']) && !empty($_POST['vars'])) {
            $productId = $_POST['id'];
            $variationIds = $_POST['vars'];
            a2wl_init_error_handler();
            try {
                $this->SplitProductService->splitProductBySelectedVariants($productId, $variationIds);
            }
            catch (ServiceException $Exception) {
                a2wl_print_throwable($Exception);
                $result = ResultBuilder::buildError($Exception);
            }
        } else {
            $message =  _x("Split product operation: wrong parameters...",
                'error text', 'ali2woo');
            $result = ResultBuilder::buildError($message);
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_import_images_action(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $product_import_model = $this->ProductImportModel;

        a2wl_init_error_handler();
        try {
            if (isset($_POST['id']) && $_POST['id'] && ($product = $product_import_model->get_product($_POST['id'])) && !empty($_POST['source']) && !empty($_POST['type']) && in_array($_POST['source'], array("description", "variant")) && in_array($_POST['type'], array("copy", "move"))) {
                if (!empty($_POST['images'])) {
                    foreach ($_POST['images'] as $image) {
                        if ($_POST['type'] == 'copy') {
                            $product['tmp_copy_images'][$image] = $_POST['source'];
                        } else if ($_POST['type'] == 'move') {
                            $product['tmp_move_images'][$image] = $_POST['source'];
                        }
                    }

                    $product_import_model->upd_product($product);
                }

                $result = ResultBuilder::buildOk();
            } else {
                $result = ResultBuilder::buildError("Error in params");
            }

            restore_error_handler();
        } catch (Throwable $e) {
            a2wl_print_throwable($e);
            $result = ResultBuilder::buildError($e->getMessage());
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_import_cancel_images_action(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $product_import_model = $this->ProductImportModel;

        a2wl_init_error_handler();
        try {
            if (isset($_POST['id']) && $_POST['id'] && ($product = $product_import_model->get_product($_POST['id'])) && !empty($_POST['image']) && !empty($_POST['source']) && !empty($_POST['type']) && in_array($_POST['source'], array("description", "variant")) && in_array($_POST['type'], array("copy", "move"))) {
                if ($_POST['type'] == 'copy') {
                    unset($product['tmp_copy_images'][$_POST['image']]);
                } else if ($_POST['type'] == 'move') {
                    unset($product['tmp_move_images'][$_POST['image']]);
                }

                $product_import_model->upd_product($product);

                $result = ResultBuilder::buildOk();
            } else {
                $result = ResultBuilder::buildError("Error in params");
            }

            restore_error_handler();
        } catch (Throwable $e) {
            a2wl_print_throwable($e);
            $result = ResultBuilder::buildError($e->getMessage());
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_search_tags(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $woocommerce_model = $this->WoocommerceModel;

        a2wl_init_error_handler();
        try {
            $num_in_page = 50;
            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $search = !empty($_REQUEST['search']) ? $_REQUEST['search'] : '';
            $result = $woocommerce_model->get_product_tags($search);
            $total_count = count($result);
            $result = array_slice($result, $num_in_page * ($page - 1), $num_in_page);

            $result = array(
                'results' => array_map(function ($o) {return array('id' => $o, 'text' => $o);}, $result),
                'pagination' => array('more' => $num_in_page * ($page - 1) + $num_in_page < $total_count),
            );
            restore_error_handler();
        } catch (Throwable $e) {
            a2wl_print_throwable($e);
            $result = ResultBuilder::buildError($e->getMessage());
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_search_products(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        a2wl_init_error_handler();
        try {
            $num_in_page = 20;
            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $search = !empty($_REQUEST['search']) ? $_REQUEST['search'] : '';

            global $wpdb;

            $sql = "SELECT p.ID, p.post_title, pimg.guid as thumb FROM $wpdb->posts p " .
                   "LEFT JOIN $wpdb->postmeta pm ON (p.ID=pm.post_id AND pm.meta_key='_thumbnail_id') " .
                   "LEFT JOIN $wpdb->posts pimg ON (pimg.ID=pm.meta_value) " .
                   "WHERE p.post_type='product' AND p.post_title like %s LIMIT %d, %d";

            $products = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql,
                    '%' . $wpdb->esc_like($search) . '%',
                    ($page - 1) * $num_in_page,
                    $num_in_page
                ),
                ARRAY_A
            );
            $products = $products && is_array($products) ? $products : array();
            $total_count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT count(ID) FROM $wpdb->posts WHERE post_type='product' AND post_title like %s",
                    '%' . $wpdb->esc_like($search) . '%',
                )
            );
            $result = array(
                'results' => array_map(function ($o) {return array('id' => $o['ID'], 'text' => $o['post_title'], 'thumb' => $o['thumb']);}, $products),
                'pagination' => array('more' => $num_in_page * ($page - 1) + $num_in_page < $total_count),
            );

            restore_error_handler();
        } catch (Throwable $e) {
            a2wl_print_throwable($e);
            $result = ResultBuilder::buildError($e->getMessage());
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_override_variations(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $product_id = $_REQUEST['product_id'];

        $result = array("state" => "ok");

        if (!$product_id) {
            $result = array("state" => "error", "message" => "Wrong params.");
        }

        if ($result['state'] != 'error') {
            $override_model = $this->OverrideModel;
            $result['order_variations'] = $override_model->find_orders($product_id);
            $result['variations'] = $override_model->find_variations($product_id);
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_override_product(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $result = array('state' => 'ok');

        $product_id = $_REQUEST['product_id'];
        $external_id = $_REQUEST['external_id'];

        $change_supplier = !empty($_REQUEST['change_supplier']) ? filter_var($_REQUEST['change_supplier'], FILTER_VALIDATE_BOOLEAN) : false;
        $override_images = !empty($_REQUEST['override_images']) ? filter_var($_REQUEST['override_images'], FILTER_VALIDATE_BOOLEAN) : false;
        $override_title_description = !empty($_REQUEST['override_title_description']) ? filter_var($_REQUEST['override_title_description'], FILTER_VALIDATE_BOOLEAN) : false;
        $variations = !empty($_REQUEST['variations']) && is_array($_REQUEST['variations']) ? $_REQUEST['variations'] : array();

        if (!$product_id || !$external_id) {
            $result = array("state" => "error", "message" => "Wrong params.");
        }

        if ($result['state'] != 'error') {
            $override_model = $this->OverrideModel;
            $result = $override_model->override($product_id, $external_id, $change_supplier, $override_images, $override_title_description, $variations);
        }

        if ($result['state'] != 'error') {
            $result['button'] = esc_html__('Override', 'ali2woo');
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_cancel_override_product(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $external_id = $_REQUEST['external_id'];

        if ($external_id) {
            $override_model = $this->OverrideModel;
            $result = $override_model->cancel_override($external_id);
        } else {
            $result = array("state" => "error", "message" => "Wrong params.");
        }

        if ($result['state'] != 'error') {
            $result['button'] = esc_html__('Push to Shop', 'ali2woo');
            $result['override_action'] = '<li><a href="#" class="product-card-override-product">' . esc_html__('Select Product to Override', 'ali2woo') . '</a></li>';
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_add_to_import(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (isset($_POST['id'])) {

            $product = array();

            if ($_POST['page'] === 'a2wl_dashboard'){
                $products = a2wl_get_transient('a2wl_search_result');
            } elseif ($_POST['page'] === 'a2wl_store'){
                $products = a2wl_get_transient('a2wl_search_store_result');
            }

            $product_import_model = $this->ProductImportModel;
            $PriceFormulaService = $this->PriceFormulaService;

            if ($products && is_array($products)) {
                foreach ($products as $p) {
                    if ($p['id'] == $_POST['id']) {
                        $product = $p;
                        break;
                    }
                }
            }

            global $wpdb;
            $post_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_a2w_external_id' AND meta_value=%s LIMIT 1",
                    $_POST['id']
                )
            );
            if (get_setting('allow_product_duplication') || !$post_id) {
                $params = empty($_POST['apd']) ? array() : array('data' => array('apd' => json_decode(stripslashes($_POST['apd']))));
                $res = $this->AliexpressModel->load_product($_POST['id'], $params);
                if ($res['state'] !== 'error') {
                    $product = array_replace_recursive($product, $res['product']);

                    if ($product) {
                        $product = $PriceFormulaService->applyFormula($product);

                        $product_import_model->add_product($product);

                        echo wp_json_encode(ResultBuilder::buildOk());
                    } else {
                        echo wp_json_encode(ResultBuilder::buildError("Product not found in serach result"));
                    }
                } else {
                    echo wp_json_encode($res);
                }
            } else {
                echo wp_json_encode(ResultBuilder::buildError("Product already imported."));
            }
        } else {
            echo wp_json_encode(ResultBuilder::buildError("add_to_import: waiting for ID..."));
        }
        wp_die();
    }
    

    public function ajax_remove_from_import(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (isset($_POST['id'])) {
            $product = false;

            if ($_POST['page'] === 'a2wl_dashboard'){
                $products = a2wl_get_transient('a2wl_search_result');
            } elseif ($_POST['page'] === 'a2wl_store'){
                $products = a2wl_get_transient('a2wl_search_store_result');
            }

            $product_import_model = $this->ProductImportModel;

            foreach ($products as $p) {
                if ($p['id'] == $_POST['id']) {
                    $product = $p;
                    break;
                }
            }
            if ($product) {
                $product_import_model->del_product($product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID]);
                echo wp_json_encode(ResultBuilder::buildOk());
            } else {
                echo wp_json_encode(ResultBuilder::buildError("Product not found in search result"));
            }
        } else {
            echo wp_json_encode(ResultBuilder::buildError("remove_from_import: waiting for ID..."));
        }

        wp_die();
    }

    /**
     * todo: need to refactor this method and split to use service instead of private methods
     * @return void
     */
    public function ajax_load_shipping_info(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (A2WL()->isFreePlugin()) {
            $errorText = '<div class="_a2wfo a2wl-info"><div>This feature is available in full version of AliNext (Lite version).</div><a href="https://ali2woo.com/pricing/?utm_source=lite&utm_medium=lite_banner&utm_campaign=alinext-lite" target="_blank" class="btn">START FREE TRIAL</a></div>';
            $result = ResultBuilder::buildError($errorText);

            echo wp_json_encode($result);
            wp_die();
        }

        if (!Account::getInstance()->get_purchase_code()) {
            $errorText = _x('Input your purchase code in the plugin settings', 'error', 'ali2woo');
            $result = ResultBuilder::buildError($errorText);

            echo wp_json_encode($result);
            wp_die();
        }

        if (isset($_POST['id'])) {
            $page = $_POST['page'] ?? 'a2wl_dashboard';

            if ($page === 'product') {
                $this->ajax_load_shipping_info_for_product();
            }

            if ($page === 'fulfillment') {
                $this->ajax_load_shipping_info_for_fulfillment();
            }

            if ($page === 'import') {
                $this->ajax_load_shipping_info_for_import();
            }
        }

        //don't return shipping items for a2wl_dashboard and a2wl_store pages
        echo wp_json_encode(ResultBuilder::buildOk(['products' => []]));
        wp_die();
    }

    /**
     * Load shipping info at Woo orders page
     * @return void
     */
    private function ajax_load_shipping_info_for_fulfillment(): void
    {
        $externalSkuId = $_POST['variation_key'] ?? null;
        $wcProductId = intval($_POST['id']);

        $WC_ProductOrVariation = wc_get_product($wcProductId);
        if (!$WC_ProductOrVariation) {
            $errorText = _x('Bad product ID', 'error', 'ali2woo');
            $result = ResultBuilder::buildError($errorText);

            echo wp_json_encode($result);
            wp_die();
        }

        $wcVariationId = null;

        if ($externalSkuId) {
            $wcVariationId = $this->WoocommerceModel
                ->getProductIdByExternalSkuId($wcProductId, $externalSkuId);
        }

        if ($externalSkuId && !$wcVariationId) {
            $errorText = _x('No such a product with this variation key', 'error', 'ali2woo');
            $result = ResultBuilder::buildError($errorText);

            echo wp_json_encode($result);
            wp_die();
        }

        if ($wcVariationId) {
            $WC_ProductOrVariation = wc_get_product($wcVariationId);
            if (!$WC_ProductOrVariation) {
                $errorText = _x('Bad product variation ID', 'error', 'ali2woo');

                echo wp_json_encode(ResultBuilder::buildError($errorText));
                wp_die();
            }
        }

        try {
            $importedProduct = $this->WoocommerceService->getProductWithVariations($wcProductId);
        } catch (RepositoryException|ServiceException $Exception) {
            $errorText = _x('Product does`t have imported data from AliExpress', 'error', 'ali2woo');
            $result = ResultBuilder::buildError($errorText);

            echo wp_json_encode($result);
            wp_die();
        }

        $product_country_to = !empty($importedProduct[ImportedProductService::FIELD_COUNTRY_TO])
            ? $importedProduct[ImportedProductService::FIELD_COUNTRY_TO]
            : '';

        $countryToCode = $_POST['country_to'] ?? $product_country_to;
       // $countryFromCode = !empty($_POST['country_from']) ? $_POST['country_from'] : $product_country_from;
        $countryFromCode = $this->WoocommerceService->getShippingFromByProduct($WC_ProductOrVariation);

        //todo: $externalSkuId should be update if the product variations are changed
        $externalSkuId = $_POST['variation_key'] ?? '';

        $countryCode = ProductShippingData::meta_key($countryFromCode, $countryToCode);
        //need fresh shipping items data on the fulfillment page, clean cache
        $importedProduct[ImportedProductService::FIELD_SHIPPING_INFO][$countryCode] = [];

        try {
            $importedProduct = $this->ProductService->updateProductShippingInfo(
                $importedProduct, $countryFromCode,
                $countryToCode, $externalSkuId, null
            );

            $shippingItems = $this->ProductService->getShippingItems(
                $importedProduct, $countryToCode, $countryFromCode
            );
        } catch (ServiceException $ServiceException) {
            a2wl_error_log($ServiceException->getMessage());
            $shippingItems = [];
        }

        try {
            $this->ProductShippingDataService->saveItems(
                $wcProductId, $countryFromCode, $countryToCode,
                $importedProduct[ImportedProductService::FIELD_SHIPPING_INFO][$countryCode]
            );
        } catch (RepositoryException $RepositoryException) {
            a2wl_error_log('Can`t update product shipping items cache' . $RepositoryException->getMessage());
        }

        $importedProduct = $this->PriceFormulaService->applyFormula($importedProduct);

        $variationList = [];
        if (isset($importedProduct['sku_products']['variations'])) {
            foreach ($importedProduct['sku_products']['variations'] as $variation) {
                $variationList[] = [
                    'id' => $variation[ImportedProductService::FIELD_EXTERNAL_SKU_ID],
                    'calc_price' => $variation['calc_price'],
                    'calc_regular_price' => $variation['calc_regular_price'],
                    'title' => $variation['title'],
                    'ship_from' => $variation[ImportedProductService::FIELD_COUNTRY_CODE],
                ];
            }
        }

        $result[] = [
            'product_id' => $wcProductId,
            'default_method' => $importedProduct[ImportedProductService::FIELD_METHOD] ?? '',
            'items' => $shippingItems,
            'shipping_cost' => $product[ImportedProductService::FIELD_COST] ?? '',
            'variations' => $variationList,
            'variation_key' => $externalSkuId,
        ];

        echo wp_json_encode(ResultBuilder::buildOk(['products' => $result]));
        wp_die();
    }

    /**
     * Load shipping info at ProductDataTab (Woo product editing page)
     * @return void
     */
    private function ajax_load_shipping_info_for_product(): void
    {
        $externalSkuId = $_POST['variation_key'] ?? null;
        $wcProductId = intval($_POST['id']);

        $WC_ProductOrVariation = wc_get_product($wcProductId);
        if (!$WC_ProductOrVariation) {
            echo wp_json_encode(ResultBuilder::buildError("Bad product ID"));
            wp_die();
        }

        $wcVariationId = null;

        if ($externalSkuId) {
            $wcVariationId = $this->WoocommerceModel
                ->getProductIdByExternalSkuId($wcProductId, $externalSkuId);
        }

        if ($externalSkuId && !$wcVariationId) {
            $errorText = esc_html__('No such a product with this variation key', 'ali2woo');
            $result = ResultBuilder::buildError($errorText);

            echo wp_json_encode($result);
            wp_die();
        }

        if ($wcVariationId) {
            $WC_ProductOrVariation = wc_get_product($wcVariationId);
            if (!$WC_ProductOrVariation) {
                echo wp_json_encode(ResultBuilder::buildError("Bad product variation ID"));
                wp_die();
            }
        }

        try {
            $importedProduct = $this->WoocommerceService->getProduct($wcProductId);
        } catch (RepositoryException|ServiceException $Exception) {
            $errorText = esc_html__(
                'This WooCommerce product does not have imported data from AliExpress', 'ali2woo'
            );
            $result = ResultBuilder::buildError($errorText);

            echo wp_json_encode($result);
            wp_die();
        }

        //todo: Here we take product countries to pass as parameters
        //need to move this logic to WoocommerceService::updateProductShippingInfo()
        $product_country_from = !empty($importedProduct[ImportedProductService::FIELD_COUNTRY_FROM]) ?
            $importedProduct[ImportedProductService::FIELD_COUNTRY_FROM] :
            'CN';

        $product_country_to = !empty($importedProduct[ImportedProductService::FIELD_COUNTRY_TO])
            ? $importedProduct[ImportedProductService::FIELD_COUNTRY_TO]
            : '';

        $countryToCode = $_POST['country_to'] ?? $product_country_to;
       // $countryFromCode = !empty($_POST['country_from']) ? $_POST['country_from'] : $product_country_from;

        $countryFromCode = $this->WoocommerceService->getShippingFromByProduct($WC_ProductOrVariation);

        try {
            $importedProduct = $this->WoocommerceService->updateProductShippingInfo(
                $WC_ProductOrVariation, $countryToCode,
            );

            $shippingItems = $this->ProductService->getShippingItems(
                $importedProduct, $countryToCode, $countryFromCode
            );
        } catch (RepositoryException $RepositoryException) {
            a2wl_error_log($RepositoryException->getMessage());

            echo wp_json_encode(ResultBuilder::buildError(
                'Can`t get product shipping')
            );

            wp_die();
        } catch (ServiceException $ServiceException) {
            a2wl_error_log($ServiceException->getMessage());
            $shippingItems = [];
        }

        $product = $this->PriceFormulaService->applyFormula($importedProduct);

        $variationList = [];
        if (isset($importedProduct['sku_products']['variations'])) {
            foreach ($importedProduct['sku_products']['variations'] as $variation) {
                $variationList[] = [
                    'id' => $variation[ImportedProductService::FIELD_EXTERNAL_SKU_ID],
                    'calc_price' => $variation['calc_price'],
                    'calc_regular_price' => $variation['calc_regular_price'],
                    'title' => $variation['title'],
                    'ship_from' => $variation[ImportedProductService::FIELD_COUNTRY_CODE],
                ];
            }
        }

        $result[] = [
            'product_id' => $wcProductId,
            'default_method' => $importedProduct[ImportedProductService::FIELD_METHOD] ?? '',
            'items' => $shippingItems,
            'shipping_cost' => $product[ImportedProductService::FIELD_COST] ?? '',
            'variations' => $variationList
        ];

        echo wp_json_encode(ResultBuilder::buildOk(['products' => $result]));
        wp_die();
    }

    /**
     * Load shipping info in the ImportList
     * @return void
     */
    private function ajax_load_shipping_info_for_import(): void
    {
        $externalSkuId = $_POST['variation_key'] ? sanitize_text_field($_POST['variation_key']) : null;
        $externalProductId = $_POST['id'] ? sanitize_text_field($_POST['id']) : null;

        try {
            $importedProduct = $this->ProductImportModel->getProduct($externalProductId);
        } catch (ServiceException $Exception) {
            echo wp_json_encode($Exception->getMessage());
            wp_die();
        }

        //todo: Here we take product countries to pass as parameters
        //need to move this logic to WoocommerceService::updateProductShippingInfo()
        $product_country_from = !empty($importedProduct[ImportedProductService::FIELD_COUNTRY_FROM]) ?
            $importedProduct[ImportedProductService::FIELD_COUNTRY_FROM] :
            'CN';

        $product_country_to = !empty($importedProduct[ImportedProductService::FIELD_COUNTRY_TO])
            ? $importedProduct[ImportedProductService::FIELD_COUNTRY_TO]
            : '';

        $countryToCode = $_POST['country_to'] ?? $product_country_to;
        $countryFromCode = $this->ProductService->getShippingFromByExternalSkuId(
            $importedProduct, $externalSkuId
        );

        $countryCode = ProductShippingData::meta_key($countryFromCode, $countryToCode);
        //need fresh shipping items data on the fulfillment page, clean cache
        $importedProduct[ImportedProductService::FIELD_SHIPPING_INFO][$countryCode] = [];

        try {
            $importedProduct = $this->ProductService->updateProductShippingInfo(
                $importedProduct, $countryFromCode,
                $countryToCode, $externalSkuId, null
            );

            $shippingItems = $this->ProductService->getShippingItems(
                $importedProduct, $countryToCode, $countryFromCode
            );
        } catch (ServiceException $ServiceException) {
            a2wl_error_log($ServiceException->getMessage());
            $shippingItems = [];
        }

        $importedProduct = $this->PriceFormulaService->applyFormula($importedProduct);

        $variationList = [];
        if (isset($importedProduct['sku_products']['variations'])) {
            foreach ($importedProduct['sku_products']['variations'] as $variation) {
                $shipFrom = $variation[ImportedProductService::FIELD_COUNTRY_CODE] ?? 'CN';
                $title = str_replace($shipFrom, '', implode(" ", $variation['attributes_names']));
                $variationList[] = [
                    'id' => $variation[ImportedProductService::FIELD_EXTERNAL_SKU_ID],
                    'calc_price' => $variation['calc_price'],
                    'calc_regular_price' => $variation['calc_regular_price'],
                    'title' => $title,
                    'ship_from' => $shipFrom,
                ];
            }
        }
        //todo: loaded shipping info is not saved here, need to fix that
        $this->ProductImportModel->upd_product($importedProduct);

        $result[] = [
            'product_id' => $externalProductId,
            'default_method' => $importedProduct[ImportedProductService::FIELD_METHOD] ?? '',
            'items' => $shippingItems,
            'shipping_cost' => $product[ImportedProductService::FIELD_COST] ?? '',
            'variations' => $variationList,
            'variation_key' => $externalSkuId,
        ];

        echo wp_json_encode(ResultBuilder::buildOk(['products' => $result]));
        wp_die();
    }

    public function ajax_set_shipping_info(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (isset($_POST['id'])) {
            $product = $this->ProductImportModel->get_product($_POST['id']);

            if ($product) {
                $product = $this->ProductService->setShippingInfo(
                    $product,
                    $_POST['method'] ?? '',
                    $_POST['variation_key'] ?? null,
                    $_POST['country_to'] ?? null,
                    $_POST['country_from'] ?? null
                );

                $this->ProductImportModel->upd_product($product);

                $variationList = [];
                foreach ($product['sku_products']['variations'] as $variation) {
                    $variationList[] = [
                        'id' => $variation[ImportedProductService::FIELD_EXTERNAL_SKU_ID],
                        'calc_price' => $variation['calc_price'],
                        'calc_regular_price' => $variation['calc_regular_price']
                    ];
                }

                $result = [
                    'default_method' => $product[ImportedProductService::FIELD_METHOD],
                    'shipping_cost' => $product[ImportedProductService::FIELD_COST],
                    'variations' => $variationList
                ];
                echo wp_json_encode(ResultBuilder::buildOk($result));
            } else {
                echo wp_json_encode(ResultBuilder::buildError("Product not found."));
            }
        } else {
            echo wp_json_encode(ResultBuilder::buildError("set_shipping_info: waiting for ID..."));
        }

        wp_die();
    }

    public function ajax_update_shipping_list(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!current_user_can('manage_options')) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        
        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }

    /**
     * Prepare system for heavy non-background import task
     * @todo move this method to Woocommerce model
     * @return void
     */
    private function prepareSystemForImport(): void
    {
        ini_set("memory_limit", -1);
        set_time_limit(0);
        ignore_user_abort(true);

        if (!a2wl_check_defined('A2WL_DO_NOT_USE_TRANSACTION')) {
            global $wpdb;

            wp_defer_term_counting(true);
            wp_defer_comment_counting(true);
            $wpdb->query('SET autocommit = 0;');

            register_shutdown_function(function () {
                global $wpdb;
                $wpdb->query('COMMIT;');
                //we use @ to prevent errors because during testing, terms are removed already on shutdown
                @wp_defer_term_counting(false);
                wp_defer_comment_counting(false);
            });
        }
    }
}
