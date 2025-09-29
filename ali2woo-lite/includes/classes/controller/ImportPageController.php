<?php

/**
 * Description of ImportPageController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

use Pages;

class ImportPageController extends AbstractAdminPage
{

    public const COOKIE_IMPORT_SORT = 'a2wl_import_sort';


    public function __construct(
        protected Woocommerce $WoocommerceModel,
        protected ImportListService $ImportListService,
        protected AliexpressRegionRepository $AliexpressRegionRepository,
        protected PermanentAlertService $PermanentAlertService,
        protected ProductImport $ProductImport,
        protected Country $Country,
        protected Override $Override,
        protected TipOfDayService $TipOfDayService,
        
        protected PromoService $PromoService,
        
    ) {

        parent::__construct(
            Pages::getLabel(Pages::IMPORT_LIST),
            Pages::getLabel(Pages::IMPORT_LIST) . ' ' . $this->getImportListItemCountHtml(),
            Capability::pluginAccess(),
            Pages::IMPORT_LIST,
            20
        );

        add_filter('tiny_mce_before_init', array($this, 'tiny_mce_before_init'), 30);
        add_filter('a2wl_configure_lang_data', array($this, 'configure_lang_data'), 30);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
    }

    public function assets(): void
    {
        if ($this->is_current_page()) {
            wp_enqueue_script(
                'a2wl-fancybox',
                A2WL()->plugin_url() . '/assets/js/fancybox/fancybox.umd.js',
                [], A2WL()->version, true
            );

            wp_enqueue_style(
                'a2wl-fancybox-style',
                A2WL()->plugin_url() . '/assets/css/fancybox/fancybox.css',
                [], A2WL()->version
            );
        }
    }

    public function configure_lang_data($data) {
        $data['attr_new_name'] = esc_html__('New name', 'ali2woo');
        $data['attr_name_duplicate_error'] = esc_html__('this name is already used', 'ali2woo');

        return $data;
    }

    public function before_admin_render(): void
    {
        if (!empty($_REQUEST['delete_id']) || !empty($_REQUEST['action']) || !empty($_REQUEST['action2'])) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (!PageGuardHelper::canAccessPage(Pages::IMPORT_LIST)) {
            wp_die($this->getErrorTextNoPermissions());
        }

        if (isset($_REQUEST['delete_id']) && $_REQUEST['delete_id']) {
            if ($product = $this->ProductImport->get_product($_REQUEST['delete_id'])) {
                foreach ($product['tmp_edit_images'] as $edit_image) {
                    if (isset($edit_image['attachment_id'])) {
                        Utils::delete_attachment($edit_image['attachment_id'], true);
                    }
                }
                $this->ProductImport->del_product($_REQUEST['delete_id']);
            }
            wp_redirect(admin_url('admin.php?page=a2wl_import'));
        } else if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "delete_all") || (isset($_REQUEST['action2']) && $_REQUEST['action2'] == "delete_all")) {
            $product_ids = $this->ProductImport->get_product_id_list();

            foreach ($product_ids as $product_id) {
                if ($product = $this->ProductImport->get_product($product_id)) {
                    foreach ($product['tmp_edit_images'] as $edit_image) {
                        if (isset($edit_image['attachment_id'])) {
                            Utils::delete_attachment($edit_image['attachment_id'], true);
                        }
                    }
                }
            }

            $this->ProductImport->del_product($product_ids);

            wp_redirect(admin_url('admin.php?page=a2wl_import'));
        } else if ((isset($_REQUEST['action']) && $_REQUEST['action'] == "push_all") || (isset($_REQUEST['action2']) && $_REQUEST['action2'] == "push_all")) {
            // push all
            wp_redirect(admin_url('admin.php?page=a2wl_import'));
        } else if (((isset($_REQUEST['action']) && $_REQUEST['action'] == "delete") || (isset($_REQUEST['action2']) && $_REQUEST['action2'] == "delete")) && isset($_REQUEST['gi']) && is_array($_REQUEST['gi']) && $_REQUEST['gi']) {
            $this->ProductImport->del_product($_REQUEST['gi']);

            wp_redirect(admin_url('admin.php?page=a2wl_import'));
        }
    }

    public function render($params = []): void
    {
        if (!empty($_REQUEST['s']) || !empty($_REQUEST['o']) || !empty($_REQUEST['cur_page'])) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (!PageGuardHelper::canAccessPage(Pages::IMPORT_LIST)) {
            wp_die($this->getErrorTextNoPermissions());
        }

        $search_query = !empty($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

        $sort_query = $this->resolveSortQuery();

        $products_cnt = $this->ProductImport->get_products_count();
        $paginator = Paginator::build($products_cnt);

        if (a2wl_check_defined('A2WL_SKIP_IMPORT_SORTING')) {
            $product_list = $this->ProductImport->get_product_list(
                true, $search_query,
                $sort_query, $paginator['per_page'],
                ($paginator['cur_page'] - 1) * $paginator['per_page']
            );
        } else {
            $product_list_all = $this->ProductImport->get_product_list(true, $search_query, $sort_query);
            $product_list = array_slice(
                $product_list_all,
                $paginator['per_page'] * ($paginator['cur_page'] - 1),
                $paginator['per_page']
            );
            unset($product_list_all);
        }
        foreach ($product_list as &$product) {
            $this->prepareProduct($product);
        }

        $productShippingFromList = $this->prepareShippingFromList($product_list);

        $page = !empty($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : '';

        $product_links = $this->prepareProductLinks($product_list, $page);

        $links = [
            'remove_all_products_link' =>
                $this->getRemoveAllProductsLink($page)
        ];

        $this->modelPutArray([
            'paginator'              => $paginator,
            'search_query'           => $search_query,
            'sort_query'             => $sort_query,
            'sort_list'              => $this->ProductImport->sort_list(),
            'product_list'           => $product_list,
            'product_links'          => $product_links,
            'links'                  => $links,
            'productShippingFromList'=> $productShippingFromList,
            'localizator'            => AliexpressLocalizator::getInstance(),
            'categories'             => $this->WoocommerceModel->get_categories(),
            'countries'              => $this->Country->get_countries(),
            'override_model'         => $this->Override,
            'TipOfDay'               => $this->TipOfDayService->getNextTip(),
            
            'promo_data'             => $this->PromoService->getPromoData(),
            
            'PermanentAlerts'        => $this->PermanentAlertService->getAll(),
            
        ]);

        if (A2WL()->isFreePlugin()) {
            $this->modelPutArray([
                'aliexpressRegion'     => 'US',
                'aliexpressRegions'    => ['US' => 'United States'],
                'defaultShippingLabel' => $this->getDefaultShippingLabel(),
                'countryToCode'        => get_setting('aliship_shipto', 'US'),
                'applyShippingScopes'  => [],
            ]);
        }

        $this->include_view("import.php");
    }

    public function tiny_mce_before_init($initArray)
    {
        if ($this->is_current_page()) {
            $initArray['setup'] = 'function(ed) {ed.on("change", function(e) { a2wl_update_product(e.target.id, { description:encodeURIComponent(e.target.getContent())}); });}';
        }
        return $initArray;
    }

    private function prepareProduct(array &$product): void
    {
        if (empty($product['sku_products'])) {
            $product['sku_products'] = [
                'variations' => [],
                'attributes' => []
            ];
        }

        $tmp_all_images = Utils::get_all_images_from_product($product);

        if (empty($product['description'])) {
            $product['description'] = '';
        }

        $product['gallery_images'] = [];
        $product['variant_images'] = [];
        $product['description_images'] = [];

        foreach ($tmp_all_images as $img_id => $img) {
            if ($img['type'] === 'gallery') {
                $product['gallery_images'][$img_id] = $img['image'];
            } else if ($img['type'] === 'variant') {
                $product['variant_images'][$img_id] = $img['image'];
            } else if ($img['type'] === 'description') {
                $product['description_images'][$img_id] = $img['image'];
            }
        }

        $this->mergeTmpImages($product, $tmp_all_images, 'tmp_copy_images');
        $this->mergeTmpImages($product, $tmp_all_images, 'tmp_move_images');

        if (!isset($product['thumb_id']) && $product['gallery_images']) {
            $k = array_keys($product['gallery_images']);
            $product['thumb_id'] = $k[0];
        }
    }

    private function prepareShippingFromList(array $productList): array
    {
        return array_map(function ($item) {
            return $this->ImportListService->getCountryFromList($item);
        }, $productList);
    }

    private function prepareProductLinks($productList, $page): array
    {
        $result = [];

        foreach ($productList as $index => $item) {
            $result[$index]['remove_product_link'] =
                $this->getRemoveProductLink($page, $item);

            if (!empty($item['store_id']) && !empty($item['seller_id'])) {
                $result[$index]['find_all_products_in_store_link'] =
                    $this->getFindAllProductInStoreLink($item);
            }
        }

        return $result;
    }

    private function mergeTmpImages(array &$product, array $tmp_all_images, string $field): void
    {
        if (empty($product[$field]) || !is_array($product[$field])) {
            return;
        }

        foreach ($product[$field] as $img_id => $source) {
            if (isset($tmp_all_images[$img_id])) {
                $product['gallery_images'][$img_id] = $tmp_all_images[$img_id]['image'];
            }
        }
    }

    private function resolveSortQuery(): string
    {
        $sort_query = null;

        if (!empty($_REQUEST['o'])) {
            $sort_query = sanitize_text_field($_REQUEST['o']);
            $sort_query = $this->normalizeSortValue($sort_query);
            Utils::setAdminCookie(self::COOKIE_IMPORT_SORT, $sort_query);
        } elseif (!empty($_COOKIE[self::COOKIE_IMPORT_SORT])) {
            $sort_query = sanitize_text_field($_COOKIE[self::COOKIE_IMPORT_SORT]);
            $sort_query = $this->normalizeSortValue($sort_query);
        }

        return $sort_query ?: $this->ProductImport->default_sort();
    }

    private function normalizeSortValue(string $sortValue): string
    {
        $allowed_sorts = array_keys($this->ProductImport->sort_list());
        if (!in_array($sortValue, $allowed_sorts, true)) {
            $sortValue = $this->ProductImport->default_sort();
        }

        return $sortValue;
    }

    private function getDefaultShippingLabel(): string
    {
        $shippingOptions = Utils::get_aliexpress_shipping_options();
        $currentShippingCode = get_setting('fulfillment_prefship', 'CAINIAO_PREMIUM');
        foreach ($shippingOptions as $shipping_option) {
            if ($currentShippingCode === $shipping_option['value']) {
                return $shipping_option['label'];
            }
        }

        return '';
    }

    private function getFindAllProductInStoreLink(array $product): string
    {
        $url = admin_url('admin.php?page=a2wl_store') .
            '&a2wl_store_id=' . $product['store_id'] .
            '&a2wl_seller_id=' . $product['seller_id'] .
            '&a2wl_search=1';

        return wp_nonce_url($url, self::PAGE_NONCE_ACTION, self::NONCE);
    }

    private function getRemoveProductLink(string $page, array $product): string
    {
        $url =  admin_url('admin.php?page=' . $page) .
            '&delete_id=' . $product['import_id'];

        return wp_nonce_url($url, self::PAGE_NONCE_ACTION, self::NONCE);
    }

    private function getRemoveAllProductsLink(string $page): string
    {
        $url = admin_url('admin.php?page=' . $page) .
            '&action=delete_all';

        return wp_nonce_url($url, self::PAGE_NONCE_ACTION, self::NONCE);
    }

    private function getImportListItemCountHtml(): string
    {
        $products_cnt = 0;
        if (is_admin()) {
            $products_cnt = $this->ProductImport->get_products_count();
        }

        $itemCountText = '';
        if ($products_cnt) {
            $itemCountText = ' <span class="update-plugins count-' .
                $products_cnt . '"><span class="plugin-count">' . $products_cnt . '</span></span>';
        }

        return $itemCountText;
    }

}
