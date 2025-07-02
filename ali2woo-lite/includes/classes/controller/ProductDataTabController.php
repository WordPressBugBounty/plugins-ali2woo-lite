<?php
/**
 * Description of ProductDataTabController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 *
 * @ajax: true
 */

// phpcs:ignoreFile WordPress.Security.NonceVerification.Recommended
namespace AliNext_Lite;;

use Pages;

class ProductDataTabController extends AbstractController
{

    public $tab_class = '';
    public $tab_id = '';
    public $tab_title = '';
    public $tab_icon = '';

    protected ProductShippingDataRepository $ProductShippingDataRepository;
    protected ProductShippingDataService $ProductShippingDataService;
    protected Country $CountryModel;
    protected Woocommerce $WoocommerceModel;
    protected WoocommerceService $WoocommerceService;

    public function __construct(
            ProductShippingDataRepository $ProductShippingDataRepository,
            ProductShippingDataService $ProductShippingDataService,
            Country $CountryModel,
            Woocommerce $WoocommerceModel,
            WoocommerceService $WoocommerceService
    ) {
        parent::__construct();

        $this->ProductShippingDataRepository = $ProductShippingDataRepository;
        $this->ProductShippingDataService = $ProductShippingDataService;
        $this->CountryModel = $CountryModel;
        $this->WoocommerceModel = $WoocommerceModel;
        $this->WoocommerceService = $WoocommerceService;


        $this->tab_class = 'a2wl_product_data';
        $this->tab_id = 'a2wl_product_data';
        $this->tab_title = 'A2WL Data';

        add_action('admin_head', array(&$this, 'on_admin_head'));
        add_action('woocommerce_product_write_panel_tabs', array(&$this, 'product_write_panel_tabs'), 99);
        add_action('woocommerce_product_data_panels', [$this, 'product_data_panel_wrap'], 99);
        add_action('woocommerce_process_product_meta', [$this, 'process_product_meta'], 1, 2);
        add_action('woocommerce_variation_options_pricing', [$this, 'variation_options_pricing'], 20, 3);

        add_action('wp_ajax_a2wl_data_remove_deleted_attribute', [$this, 'ajax_remove_deleted_attribute']);
        add_action('wp_ajax_a2wl_data_remove_deleted_variation', [$this, 'ajax_remove_deleted_variation']);
        add_action('wp_ajax_a2wl_data_last_update_clean', [$this, 'ajax_last_update_clean']);
        add_action('wp_ajax_a2wl_update_product_shipping_info_cache', [$this, 'ajax_update_product_shipping_info_cache']);
        add_action('wp_ajax_a2wl_remove_product_shipping_info', [$this, 'ajaxRemoveProductDefaultShipping']);
    }

    public function on_admin_head() {
        echo '<style type="text/css">#woocommerce-product-data ul.wc-tabs li.' . $this->tab_class . ' a::before {content: \'\f163\';}</style>';
    }

    public function product_write_panel_tabs() {
        ?>
        <li class="<?php echo $this->tab_class; ?>"><a href="#<?php echo $this->tab_id; ?>"><span><?php echo $this->tab_title; ?></span></a></li>
        <?php
    }

    public function product_data_panel_wrap(): void
    {
        ?>
        <div id="<?php echo $this->tab_id; ?>" class="panel <?php echo $this->tab_class; ?> woocommerce_options_panel wc-metaboxes-wrapper" style="display:none">
            <?php $this->render_product_tab_content(); ?>
        </div>
        <?php
    }

    private function render_product_tab_content(): void
    {
        $productId = $_REQUEST['post'] ?? null;

        if (!$productId) {
            return;
        }

        try {
            $ProductShippingData = $this->ProductShippingDataRepository->get($productId);
        } catch (RepositoryException $RepositoryException) {
            error_log($RepositoryException->getMessage());
            return;
        }

        try {
            $product = $this->WoocommerceService->getProductWithVariations($productId);
        } catch (RepositoryException|ServiceException $Exception) {
            return;
        }

        $variationList = [];
        if (!empty($product['sku_products']['variations'])) {
            foreach ($product['sku_products']['variations'] as $variation) {
                $variationList[] = [
                    'id' => $variation[ImportedProductService::FIELD_EXTERNAL_SKU_ID],
                    'title' => $variation['title'],
                ];
            }
        }

        $variationExternalId = !empty($product[ImportedProductService::FIELD_VARIATION_KEY]) ?
            $product[ImportedProductService::FIELD_VARIATION_KEY] : '';


        $shipping_country_from_list = $this->ProductShippingDataService->getCountryFromList($productId);

        $this->model_put('shipping_country_from_list', $shipping_country_from_list);
        $this->model_put('ProductShippingData', $ProductShippingData);
        $this->model_put('post_id', $productId);
        $this->model_put('countries',  $this->CountryModel->get_countries());
        $this->model_put('variationExternalId', $variationExternalId);
        $this->model_put('variationList', $variationList);

        $this->include_view("product_data_tab.php");
    }

    public function process_product_meta($post_id, $post): void
    {
        if (isset($_POST['_a2w_external_id'])) {
            update_post_meta($post_id, '_a2w_external_id', $_POST['_a2w_external_id']);
        } else {
            delete_post_meta($post_id, '_a2w_external_id');
        }

        if (isset($_POST['_a2w_orders_count'])) {
            update_post_meta($post_id, '_a2w_orders_count', $_POST['_a2w_orders_count']);
        } else {
            delete_post_meta($post_id, '_a2w_orders_count');
        }

        update_post_meta($post_id, '_a2w_disable_sync', !empty($_POST['_a2w_disable_sync']) ? 1 : 0);

        update_post_meta($post_id, '_a2w_disable_var_price_change', !empty($_POST['_a2w_disable_var_price_change']) ? 1 : 0);

        update_post_meta($post_id, '_a2w_disable_var_quantity_change', !empty($_POST['_a2w_disable_var_quantity_change']) ? 1 : 0);

        update_post_meta($post_id, '_a2w_disable_add_new_variants', !empty($_POST['_a2w_disable_add_new_variants']) ? 1 : 0);

        if (!empty($_POST['_a2w_last_update'])) {
            update_post_meta($post_id, '_a2w_last_update', $_POST['_a2w_last_update']);
        } else {
            delete_post_meta($post_id, '_a2w_last_update');
        }
        if (!empty($_POST['_a2w_reviews_last_update'])) {
            update_post_meta($post_id, '_a2w_reviews_last_update', $_POST['_a2w_reviews_last_update']);
        } else {
            delete_post_meta($post_id, '_a2w_reviews_last_update');
        }
        if (!empty($_POST['_a2w_review_page'])) {
            update_post_meta($post_id, '_a2w_review_page', $_POST['_a2w_review_page']);
        } else {
            delete_post_meta($post_id, '_a2w_review_page');
        }
    }

    public function variation_options_pricing($loop, $variation_data, $variation): void
    {
        if (!empty($variation_data['_aliexpress_regular_price']) || !empty($variation_data['_aliexpress_price'])) {

            $helpTip = esc_html__('Source Regular price loaded from Aliexpress', 'ali2woo');
            $label = sprintf(esc_html__('Aliexpress Regular price (%s)', 'ali2woo'), get_woocommerce_currency_symbol());
            $value = wc_format_localized_price(is_array($variation_data['_aliexpress_regular_price']) ? $variation_data['_aliexpress_regular_price'][0] : $variation_data['_aliexpress_regular_price']);
            $this->outputSettingRow($label, $value, "form-row-first", $helpTip);

            $helpTip = esc_html__('Source Sale price loaded from Aliexpress', 'ali2woo');
            $label = sprintf(esc_html__('Aliexpress Sale price (%s)', 'ali2woo'), get_woocommerce_currency_symbol());
            $value = wc_format_localized_price(is_array($variation_data['_aliexpress_price']) ? $variation_data['_aliexpress_price'][0] : $variation_data['_aliexpress_price']);
            $this->outputSettingRow($label, $value, "form-row-last", $helpTip);

            $notLoadedText = esc_html__('not loaded', 'ali2woo');

            $helpTip = esc_html__('Source Sku ID loaded from Aliexpress', 'ali2woo');
            $label = esc_html__('Aliexpress Sku ID', 'ali2woo');
            $value = !empty($variation_data['_a2w_ali_sku_id'][0]) ? $variation_data['_a2w_ali_sku_id'][0] : $notLoadedText;
            $this->outputSettingRow($label, $value, "form-row-first", $helpTip);

            $helpTip = esc_html__('Source Sku properties loaded from Aliexpress', 'ali2woo');
            $label = esc_html__('Aliexpress Sku Props', 'ali2woo');
            $value = !empty($variation_data['_aliexpress_sku_props'][0]) ? $variation_data['_aliexpress_sku_props'][0] : $notLoadedText;
            $this->outputSettingRow($label, $value, "form-row-last", $helpTip);

            $helpTip = esc_html__('All attributes included to this variation', 'ali2woo');
            $label = esc_html__('Attributes', 'ali2woo');
            $values = [];
            foreach ($variation_data as $itemKey => $dataItem) {
                if (str_starts_with($itemKey, 'attribute_pa_')) {
                    $attrLabel = str_replace('attribute_pa_', '', $itemKey);
                    $values[] = $attrLabel . ': ' .  (!empty($dataItem[0]) ? $dataItem[0] : '');
                }
            }
            $value = implode('; ', $values);
            $this->outputSettingRow($label, $value, "", $helpTip);
        }
    }

    public function ajax_remove_deleted_attribute(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::IMPORT_LIST)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (!empty($_POST['post_id']) && !empty($_POST['id'])) {
            $deleted_variations_attributes = get_post_meta($_POST['post_id'], '_a2w_deleted_variations_attributes', true);
            if ($deleted_variations_attributes) {
                foreach ($deleted_variations_attributes as $k => $a) {
                    if ($_POST['id'] == 'all' || $k == sanitize_title($_POST['id'])) {
                        unset($deleted_variations_attributes[$k]);
                    }
                }
            }
            update_post_meta($_POST['post_id'], '_a2w_deleted_variations_attributes', $deleted_variations_attributes);
        }

        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }

    public function ajax_remove_deleted_variation(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::IMPORT_LIST)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (!empty($_POST['post_id'])) {
            $a2wl_skip_meta = get_post_meta($_POST['post_id'], "_a2w_skip_meta", true);
            $a2wl_skip_meta = $a2wl_skip_meta?$a2wl_skip_meta:array('skip_vars' => array(), 'skip_images' => array());
            if ($_POST['id']=='all') {
                $a2wl_skip_meta['skip_vars'] = array();
            } else {
                $a2wl_skip_meta['skip_vars'] = array_filter(array_diff($a2wl_skip_meta['skip_vars'], array($_POST['id'])));
            }
            update_post_meta($_POST['post_id'], "_a2w_skip_meta", $a2wl_skip_meta);
        }

        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }

    public function ajax_last_update_clean(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::IMPORT_LIST)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (!empty($_POST['post_id']) && !empty($_POST['type'])) {
            if ($_POST['type'] === 'product') {
                delete_post_meta($_POST['post_id'], '_a2w_last_update');
            } else if($_POST['type'] === 'review') {
                delete_post_meta($_POST['post_id'], '_a2w_reviews_last_update');
            }
        }

        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }

    public function ajax_update_product_shipping_info_cache(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::IMPORT_LIST)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $isValidOperation = !empty($_POST['id']) && isset($_POST['cost']) && !empty($_POST['country_to']) &&
            !empty($_POST['method']) && !empty($_POST['items']);

        if ($isValidOperation) {
            $wc_product_id = intval($_POST['id']);

            $cost = floatval($_POST['cost']);
            $method = sanitize_text_field($_POST['method']);
            $countryTo = sanitize_text_field($_POST['country_to']);
            $countryFrom = isset($_POST['country_from']) ? sanitize_text_field($_POST['country_from']) : null;
            $items = rest_sanitize_array($_POST['items']);
            $variationKey = isset($_POST['variation_key']) ? sanitize_text_field($_POST['variation_key']) : null;

            try {
                $ProductShippingData = $this->ProductShippingDataRepository->get($wc_product_id);
                $ProductShippingData
                    ->setCost($cost)
                    ->setMethod($method)
                    ->setCountryTo($countryTo)
                    ->setCountryFrom($countryFrom)
                    ->setItems(1, $countryFrom, $countryTo, $items)
                    ->setVariationKey($variationKey);

                $this->ProductShippingDataRepository->save($wc_product_id, $ProductShippingData);
            } catch (RepositoryException $RepositoryException) {
                error_log($RepositoryException->getMessage());
                $errorText = esc_html__(
                        'Repository error: can`t update product shipping  cache', 'ali2woo'
                );
                echo wp_json_encode(ResultBuilder::buildError($errorText));
                wp_die();
            }

            echo wp_json_encode(ResultBuilder::buildOk());
        } else {
            $errorText = esc_html__(
                'ProductDataTabController error: can`t update product shipping cache', 'ali2woo'
            );
            echo wp_json_encode(ResultBuilder::buildError($errorText));
        }

        wp_die();
    }

    public function ajaxRemoveProductDefaultShipping(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::IMPORT_LIST)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (empty($_POST['id'])) {
            $errorText = esc_html__(
                'Can`t reset product shipping data: wrong params',
                'ali2woo'
            );
            $result = ResultBuilder::buildError($errorText);
            echo wp_json_encode($result);
            wp_die();
        }


        $productId = intval($_POST['id']);

        try {
            $this->ProductShippingDataService->resetProductDefaultShipping($productId);
        }
        catch (RepositoryException $RepositoryException) {
            error_log($RepositoryException->getMessage());

            $errorText = esc_html__(
                'Can`t reset product shipping data: repository error',
                'ali2woo'
            );

            $result = ResultBuilder::buildError($errorText);
            echo wp_json_encode($result);
            wp_die();
        }

        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }

    private function outputSettingRow(string $label, string $value, string $class = "", string $helpTip = ""): void
    {
        echo '<p class="form-field form-row ' . $class . '">';
        if (!empty($value)) {
            echo '<label style="cursor: inherit;">' . $label . ':</label>&nbsp;<label style="cursor: inherit;">' . $value . '</label>';
        }
        if (!empty($helpTip)) {
            echo '<span class="woocommerce-help-tip" data-tip="' . $helpTip . '"></span>';
        }
        echo '</p>';
    }
}
