<?php

/**
 * Description of SearchStoreProductsPageController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */
namespace AliNext_Lite;;

class SearchStoreProductsPageController extends AbstractAdminPage
{

    protected Aliexpress $AliexpressModel;
    protected Country $CountryModel;


    public function __construct(
        Aliexpress $AliexpressModel,
        Country $CountryModel
    ) {
        parent::__construct(esc_html__('Search In Store', 'ali2woo'), esc_html__('Search In Store', 'ali2woo'), 'import', 'a2wl_store', 10);

        $this->AliexpressModel = $AliexpressModel;
        $this->CountryModel = $CountryModel;

        add_filter('a2wl_configure_lang_data', [$this, 'configure_lang_data']);
    }

    public function configure_lang_data($lang_data)
    {
        $lang_data['advance'] = _x('Advance', 'Button', 'ali2woo');
        $lang_data['simple'] = _x('Simple', 'Button', 'ali2woo');
        $lang_data['imported_successfully'] = _x('Imported successfully.', 'Status', 'ali2woo');
        $lang_data['removed_successfully'] = _x('Removed successfully.', 'Status', 'ali2woo');
        $lang_data['import_failed'] = _x('Import failed.', 'Status', 'ali2woo');

        return $lang_data;
    }

    public function render($params = array()): void
    {
        if (!empty($_POST)) {
            check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);
        }

        if (!current_user_can('manage_options')) {
            wp_die($this->getErrorTextNoPermissions());
        }

        $filter = array();
        if (!empty($_GET['a2wl_search'])) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);

            $filter = array_merge($filter, $_GET);
            if (isset($filter['cur_page'])) {
                unset($filter['cur_page']);
            }
            if (isset($filter['page'])) {
                unset($filter['page']);
            }
        }

        $adv_search_field = array('min_price', 'max_price', 'min_feedback', 'max_feedback', 'volume_from', 'volume_to');
        $adv_search = false;
        foreach ($filter as $key => $val) {
            $new_key = preg_replace('/a2wl_/', '', $key, 1);
            unset($filter[$key]);
            $filter[$new_key] = wp_unslash($val);
            if (in_array($new_key, $adv_search_field)) {
                $adv_search = true;
            }
        }

        if (!isset($filter['sort'])) {
            $filter['sort'] = "volumeDown";
        }

        $page = isset($_GET['cur_page']) && intval($_GET['cur_page']) ? intval($_GET['cur_page']) : 1;
        $per_page = 20;
        if (a2wl_check_defined('A2WL_STORE_PRODUCT_SEARCH_LIMIT')) {
            $per_page = intval(A2WL_STORE_PRODUCT_SEARCH_LIMIT);
        }

        if (!empty($_REQUEST['a2wl_search'])) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);

            $load_products_result =  $this->AliexpressModel->load_store_products($filter, $page, $per_page);
        } else {
            $load_products_result = ResultBuilder::buildError(esc_html__('Please enter some store id and seller id!', 'ali2woo'));
        }

        if ($load_products_result['state'] == 'error' || $load_products_result['state'] == 'warn') {
            add_settings_error('a2wl_products_list', esc_attr('settings_updated'), $load_products_result['message'], 'error');
        }

        if ($load_products_result['state'] != 'error') {
            $pages_list = [];
            $links = 4;
            $last = ceil($load_products_result['total'] / $per_page);
            $load_products_result['total_pages'] = $last;
            $start = (($load_products_result['page'] - $links) > 0) ? $load_products_result['page'] - $links : 1;
            $end = (($load_products_result['page'] + $links) < $last) ? $load_products_result['page'] + $links : $last;
            if ($start > 1) {
                $pages_list[] = 1;
                $pages_list[] = '';
            }
            for ($i = $start; $i <= $end; $i++) {
                $pages_list[] = $i;
            }
            if ($end < $last) {
                $pages_list[] = '';
                $pages_list[] = $last;
            }
            $load_products_result['pages_list'] = $pages_list;

            a2wl_set_transient('a2wl_search_store_result', $load_products_result['products']);
        }

        $localizator = AliexpressLocalizator::getInstance();
        $TipOfDayService = A2WL()->getDI()->get('AliNext_Lite\TipOfDayService');

        $page = esc_attr(((isset($_GET['page'])) ? $_GET['page'] : ''));
        $curPage = esc_attr(((isset($_GET['cur_page'])) ? $_GET['cur_page'] : ''));;

        $this->model_put('filter', $filter);
        $this->model_put('adv_search', $adv_search);
        $this->model_put('categories', $this->get_categories());
        $this->model_put('countries', $this->CountryModel->get_countries());
        $this->model_put('locale', $localizator->getLangCode());
        $this->model_put('currency', $localizator->currency);
        $this->model_put('chrome_ext_import', a2wl_check_defined('A2WL_CHROME_EXT_IMPORT'));
        $this->model_put('load_products_result', $load_products_result);
        $this->model_put('page', $page);
        $this->model_put('curPage', $curPage);
        $this->model_put("TipOfDay", $TipOfDayService->getNextTip());

        $search_version = 'v1';
        $this->include_view('search_store_products_' . $search_version . '.php');
    }

    protected function get_categories()
    {
        if (file_exists(A2WL()->plugin_path() . '/assets/data/user_aliexpress_categories.json')) {
            $result = json_decode(
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                file_get_contents(A2WL()->plugin_path() . '/assets/data/user_aliexpress_categories.json'),
                true
            );
        } else {
            $result = array('categories' => get_option('a2wl_all_categories', array()));
        }
        $result = isset($result["categories"]) && is_array($result["categories"]) ? $result["categories"] : array();
        array_unshift($result, array("id" => "0", "name" => "All categories", "level" => 1));
        return $result;
    }

}
