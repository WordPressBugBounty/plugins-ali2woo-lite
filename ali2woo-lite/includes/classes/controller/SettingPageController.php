<?php

/**
 * Description of SettingPageController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

use Pages;

class SettingPageController extends AbstractAdminPage
{
    public const FIELD_FIELD_NO_AVATAR_PHOTO = 'a2wl_review_noavatar_photo';
    public const SETTING_VIDEO = 'video';

    protected LocalService $LocalService;
    protected CommonSettingService $CommonSettingService;

    public function __construct(
        LocalService $LocalService,
        CommonSettingService $CommonSettingService
    ) {
        parent::__construct(
            Pages::getLabel(Pages::SETTINGS),
            Pages::getLabel(Pages::SETTINGS),
            Capability::pluginAccess(),
            Pages::SETTINGS,
            30
        );

        $this->LocalService = $LocalService;
        $this->CommonSettingService = $CommonSettingService;

        add_filter('a2wl_setting_view', [$this, 'setting_view']);
        add_filter('a2wl_configure_lang_data', array($this, 'configure_lang_data'));
    }

    public function configure_lang_data($lang_data)
    {
        if ($this->is_current_page()) {
            $lang_data = array(
                /* translators: %d is replaced with "digit" */
                'process_loading_d_of_d_erros_d' => _x('Process loading %d of %d. Errors: %d.', 'Status', 'ali2woo'),
                /* translators: %d is replaced with "digit" */
                'load_button_text' => _x('Load %d images', 'Status', 'ali2woo'),
                'all_images_loaded_text' => _x('All images loaded', 'Status', 'ali2woo'),
                'leave_blank_to_allow_all_countries' => esc_html__('leave blank to allow all countries', 'ali2woo'),
            );
        }
        return $lang_data;
    }

    public function render($params = []): void
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            wp_die($this->getErrorTextNoPermissions());
        }

        /** @var PermanentAlertService $PermanentAlertService  */
        $PermanentAlertService = A2WL()->getDI()->get('AliNext_Lite\PermanentAlertService');

        $TipOfDayService = A2WL()->getDI()->get('AliNext_Lite\TipOfDayService');

        $current_module = $_REQUEST['subpage'] ?? 'common';
        $this->model_put("PermanentAlerts", $PermanentAlertService->getAll());
        $this->model_put("TipOfDay", $TipOfDayService->getNextTip());
        $this->model_put("modules", $this->getModules());
        $this->model_put("current_module", $current_module);

        $this->include_view([
            "settings/settings_head.php",
            apply_filters('a2wl_setting_view', $current_module),
            "settings/settings_footer.php"
        ]);
    }

    public function getModules(): array
    {
        return apply_filters('a2wl_setting_modules', [
            ['id' => 'common', 'name' => esc_html__('Common settings', 'ali2woo')],
            ['id' => self::SETTING_VIDEO, 'name' => esc_html__('Video settings', 'ali2woo')],
            ['id' => 'account', 'name' => esc_html__('Account settings', 'ali2woo')],
            ['id' => 'price_formula', 'name' => esc_html__('Pricing Rules', 'ali2woo')],
            ['id' => 'reviews', 'name' => esc_html__('Reviews settings', 'ali2woo')],
            ['id' => 'shipping', 'name' => esc_html__('Shipping settings', 'ali2woo')],
            ['id' => 'phrase_filter', 'name' => esc_html__('Phrase Filtering', 'ali2woo')],
            ['id' => 'chrome_api', 'name' => esc_html__('API Keys', 'ali2woo')],
            ['id' => 'system_info', 'name' => esc_html__('System Info', 'ali2woo')],
        ]);
    }

    public function setting_view($current_module): string
    {
        $view = "";
        switch ($current_module) {
            case 'common':
                $view = $this->common_handle();
                break;
            case self::SETTING_VIDEO:
                $view = $this->videoSettingsHandle();
                break;
            case 'account':
                $view = $this->account_handle();
                break;
            case 'price_formula':
                $view = $this->price_formula();
                break;
            case 'reviews':
                $view = $this->reviews();
                break;
            case 'shipping':
                $view = $this->shipping();
                break;
            case 'phrase_filter':
                $view = $this->phrase_filter();
                break;
            case 'chrome_api':
                $view = $this->chrome_api();
                break;
            case 'system_info':
                $view = $this->system_info();
                break;
        }
        return $view;
    }

    private function common_handle(): string
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (isset($_POST['setting_form'])) {
            $this->CommonSettingService->handle();
        }

        $model = $this->CommonSettingService->collectModel();

        foreach ($model as $key => $value) {
            $this->model_put($key, $value);
        }

        return "settings/common.php";
    }

    private function videoSettingsHandle(): string
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (isset($_POST['setting_form'])) {
            settings()->auto_commit(false);
            set_setting(
                Settings::SETTING_IMPORT_VIDEO,
                isset($_POST['a2wl_' . Settings::SETTING_IMPORT_VIDEO])
            );
            set_setting(
                Settings::SETTING_SHOW_PRODUCT_VIDEO_TAB,
                isset($_POST['a2wl_' . Settings::SETTING_SHOW_PRODUCT_VIDEO_TAB])
            );

            if (isset($_POST['a2wl_' . Settings::SETTING_VIDEO_TAB_PRIORITY])) {
                $videoTabPriority = intval($_POST['a2wl_' . Settings::SETTING_VIDEO_TAB_PRIORITY]);
                set_setting(
                    Settings::SETTING_VIDEO_TAB_PRIORITY,
                    $videoTabPriority
                );
            }

            set_setting(
                Settings::SETTING_MAKE_VIDEO_FULL_TAB_WIDTH,
                isset($_POST['a2wl_' . Settings::SETTING_MAKE_VIDEO_FULL_TAB_WIDTH])
            );

            if (isset($_POST['a2wl_' . Settings::SETTING_ADD_VIDEO_TO_DESCRIPTION])) {
                $addVideoToDescription = $_POST['a2wl_' . Settings::SETTING_ADD_VIDEO_TO_DESCRIPTION];
                if (in_array($addVideoToDescription, ['none', 'after', 'before'], true)) {
                    set_setting(
                        Settings::SETTING_ADD_VIDEO_TO_DESCRIPTION,
                        $addVideoToDescription
                    );
                }
            }

            settings()->commit();
            settings()->auto_commit(true);
        }

        $this->model_put("addVideoToDescriptionTypes", ['none', 'before', 'after']);

        return "settings/video.php";
    }

    private function account_handle(): string
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        $account = Account::getInstance();

        $token = AliexpressToken::getInstance();

        if (isset($_POST['setting_form'])) {
            $accountType = 'aliexpress';

            $accountTypeCheck = isset($_POST['a2wl_account_type']) &&
                in_array($_POST['a2wl_account_type'], ['aliexpress', 'admitad', 'epn']);

            if ($accountTypeCheck) {
                $accountType = $_POST['a2wl_account_type'];
            }

            $account->set_account_type($accountType);
            $account->use_custom_account(isset($_POST['a2wl_use_custom_account']));
            if ($account->custom_account && isset($_POST['a2wl_account_type'])) {
                if ($_POST['a2wl_account_type'] == 'aliexpress') {
                    $appkey = isset($_POST['a2wl_appkey']) ?
                        trim(sanitize_text_field($_POST['a2wl_appkey'])) : null;
                    $secretkey = isset($_POST['a2wl_secretkey']) ?
                        trim(sanitize_text_field($_POST['a2wl_secretkey'])) : null;
                    $trackingid = isset($_POST['a2wl_trackingid']) ?
                        trim(sanitize_text_field($_POST['a2wl_trackingid'])) : null;

                    $appkey = $this->isNoWhiteSpace($appkey) ? $appkey : null;
                    $secretkey = $this->isNoWhiteSpace($secretkey) ? $secretkey : null;
                    $trackingid = $this->isNoWhiteSpace($trackingid) ? $trackingid : null;
                    $account->save_aliexpress_account($appkey, $secretkey, $trackingid);
                } else if ($_POST['a2wl_account_type'] == 'admitad') {
                    $cashback_url = isset($_POST['a2wl_admitad_cashback_url']) ?
                        trim(sanitize_text_field($_POST['a2wl_admitad_cashback_url'])) : '';
                    $account_name = isset($_POST['a2wl_admitad_account_name']) ?
                        trim(sanitize_text_field($_POST['a2wl_admitad_account_name'])) : '';

                    $cashback_url = $this->isNoWhiteSpace($cashback_url) ? $cashback_url : '';
                    $account_name = $this->isNoWhiteSpace($account_name) ? $account_name : '';
                    $account->save_admitad_account($cashback_url, $account_name);
                } else if ($_POST['a2wl_account_type'] == 'epn') {
                    $cashback_url = isset($_POST['a2wl_epn_cashback_url']) ?
                        trim(sanitize_text_field($_POST['a2wl_epn_cashback_url'])) : '';

                    $cashback_url = $this->isNoWhiteSpace($cashback_url) ? $cashback_url : '';
                    $account->save_epn_account($cashback_url);
                }
            }
        }

        $this->model_put("account", $account);

        $this->model_put("tokens", $token->tokens());

        return "settings/account.php";
    }

    function isNoWhiteSpace(string $value): bool
    {
        return preg_match('/^\S+$/', $value);
    }

    private function price_formula(): string
    {
        $PriceFormulaSettingsRepository= A2WL()->getDI()->get('AliNext_Lite\PriceFormulaSettingsRepository');
        $PriceFormulaRepository = A2WL()->getDI()->get('AliNext_Lite\PriceFormulaRepository');
        $PriceFormulaSetRepository = A2WL()->getDI()->get('AliNext_Lite\PriceFormulaSetRepository');
        $PriceFormulaFactory = A2WL()->getDI()->get('AliNext_Lite\PriceFormulaFactory');
        $WoocommerceModel = A2WL()->getDI()->get('AliNext_Lite\Woocommerce');

        $formulas = $PriceFormulaRepository->getExtendedFormulas();

        if ($formulas) {
            $add_formula = $PriceFormulaFactory->createFormula();
            $add_formula->min_price = floatval($formulas[count($formulas) - 1]->max_price) + 0.01;
            $formulas[] = $add_formula;
            $this->model_put("formulas", $formulas);
        } else {
            $this->model_put("formulas", $PriceFormulaRepository->getDefaultFormulas());
        }

        $this->model_put("PriceFormulaSets", $PriceFormulaSetRepository->getAll());
        //$a2wl_pricing_rules_type
        $this->model_put("pricingRuleTypes", PriceFormula::pricing_rules_types());
        $this->model_put("PriceFormulaSettingsRepository", $PriceFormulaSettingsRepository);

        $this->model_put("default_formula", $PriceFormulaRepository->getDefaultFormula());

        $this->model_put('cents', get_setting('price_cents'));
        $this->model_put('compared_cents', get_setting('price_compared_cents'));
        $this->model_put("categories", $WoocommerceModel->get_categories());

        return "settings/price_formula.php";
    }

    private function reviews(): string
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (isset($_POST['setting_form'])) {
            settings()->auto_commit(false);
            set_setting('load_review', isset($_POST['a2wl_load_review']));
            set_setting('review_status', isset($_POST['a2wl_review_status']));
            set_setting('review_translated', isset($_POST['a2wl_review_translated']));
            set_setting('review_avatar_import', isset($_POST['a2wl_review_avatar_import']));

            set_setting('review_schedule_load_period', 'a2wl_15_mins');

            //review number fields
            $review_number_from = 10;
            $review_number_to = 20;

            if (isset($_POST['a2wl_review_min_per_product'])) {
                $review_number_from = intval($_POST['a2wl_review_min_per_product']);
            }

            if (isset($_POST['a2wl_review_max_per_product'])) {
                $review_number_to = intval($_POST['a2wl_review_max_per_product']);
            }

            if ($review_number_to < 1) {
                $review_number_to = 20;
            }

            if ($review_number_from < 1 || $review_number_from > $review_number_to) {
                $review_number_from = $review_number_to;
            }

            set_setting('review_min_per_product', $review_number_from);
            set_setting('review_max_per_product', $review_number_to);

            //clear this meta in all products, it will be recalculated during reviews loading
            Review::clear_all_product_max_number_review_meta();

            //raiting fields
            $raiting_from = 1;
            $raiting_to = 5;
            if (isset($_POST['a2wl_review_raiting_from'])) {
                $raiting_from = intval($_POST['a2wl_review_raiting_from']);
            }

            if (isset($_POST['a2wl_review_raiting_to'])) {
                $raiting_to = intval($_POST['a2wl_review_raiting_to']);
            }

            if ($raiting_from >= 5) {
                $raiting_from = 5;
            }

            if ($raiting_from < 1 || $raiting_from > $raiting_to) {
                $raiting_from = 1;
            }

            if ($raiting_to >= 5) {
                $raiting_to = 5;
            }

            if ($raiting_to < 1) {
                $raiting_to = 1;
            }

            set_setting('review_raiting_from', $raiting_from);
            set_setting('review_raiting_to', $raiting_to);

            //update more field
            set_setting('review_load_attributes', isset($_POST['a2wl_review_load_attributes']));

            set_setting('review_show_image_list', isset($_POST['a2wl_review_show_image_list']));

            if (isset($_POST['a2wl_review_show_image_list'])) {
                $a2wl_review_thumb_width = intval($_POST['a2wl_review_thumb_width']);

                if ($a2wl_review_thumb_width > 0) {
                    set_setting('review_thumb_width', $a2wl_review_thumb_width);
                } else {
                    set_setting('review_thumb_width', 30);
                }
            }

            set_setting('review_skip_empty', isset($_POST['a2wl_review_skip_empty']));
            set_setting('review_skip_keywords',
                isset($_POST['a2wl_review_skip_keywords']) ? trim(wp_unslash($_POST['a2wl_review_skip_keywords'])) : ''
            );

            if (!isset($_POST['a2wl_review_country']) || !is_array($_POST['a2wl_review_country'])) {
                set_setting('review_country', []);
            } else {
                set_setting('review_country', $_POST['a2wl_review_country']);
            }

            set_setting('moderation_reviews', isset($_POST['a2wl_moderation_reviews']));

            $checkNoAvatarPhoto = $_FILES && isset($_FILES[self::FIELD_FIELD_NO_AVATAR_PHOTO]) &&
                UPLOAD_ERR_OK === $_FILES[self::FIELD_FIELD_NO_AVATAR_PHOTO]['error'];

            if ($checkNoAvatarPhoto) {
                if (!function_exists('wp_handle_upload')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }

                $uploadedfile = $_FILES[self::FIELD_FIELD_NO_AVATAR_PHOTO];
                $movefile = wp_handle_upload($uploadedfile, ['test_form' => false]);
                if ($movefile && !isset($movefile['error'])) {
                    set_setting('review_noavatar_photo', $movefile['url']);
                } else {
                    error_log('review avatar upload error: ' . $movefile['error']);
                }
            } else {
                del_setting('review_noavatar_photo');
            }

            settings()->commit();
            settings()->auto_commit(true);
        }

        $countryModel = new Country();
        $countries = $countryModel->get_countries();

        unset($countries[0]);

        $this->model_put("reviews_countries", $countries);

        return "settings/reviews.php";
    }

    private function shipping(): string
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (isset($_POST['setting_form'])) {

            set_setting('aliship_shipto', isset($_POST['a2w_aliship_shipto']) ? wp_unslash($_POST['a2w_aliship_shipto']) : 'US');
            set_setting('aliship_frontend', isset($_POST['a2wl_aliship_frontend']));
            set_setting('default_shipping_class', !empty($_POST['a2wl_default_shipping_class']) ? $_POST['a2wl_default_shipping_class'] : false);

            if (isset($_POST['a2wl_aliship_frontend'])) {

                if (isset($_POST['default_rule'])) {
                    ShippingPriceFormula::set_default_formula(new ShippingPriceFormula($_POST['default_rule']));
                }

                set_setting('aliship_selection_type', isset($_POST['a2wl_aliship_selection_type']) ? wp_unslash($_POST['a2wl_aliship_selection_type']) : 'popup');

                set_setting('aliship_shipping_type', isset($_POST['a2wl_aliship_shipping_type']) ? wp_unslash($_POST['a2wl_aliship_shipping_type']) : 'new');

                set_setting('aliship_shipping_option_text',
                    (isset($_POST['a2wl_aliship_shipping_option_text']) && !empty($_POST['a2wl_aliship_shipping_option_text'])) ?
                    wp_unslash($_POST['a2wl_aliship_shipping_option_text']) : '[{shipping_cost}] {shipping_company} ({delivery_time}) - {country}');

                set_setting('aliship_shipping_label', isset($_POST['a2wl_aliship_shipping_label']) ? wp_unslash($_POST['a2wl_aliship_shipping_label']) : 'Shipping');
                set_setting('aliship_free_shipping_label', isset($_POST['a2wl_aliship_free_shipping_label']) ? wp_unslash($_POST['a2wl_aliship_free_shipping_label']) : 'Free Shipping');

                set_setting('aliship_product_enable', isset($_POST['a2wl_aliship_product_enable']));

                if (isset($_POST['a2wl_aliship_product_enable'])) {
                    set_setting('aliship_product_position', isset($_POST['a2wl_aliship_product_position']) ? wp_unslash($_POST['a2wl_aliship_product_position']) : 'after_cart');

                    set_setting('aliship_product_not_available_message',
                        (isset($_POST['a2wl_aliship_product_not_available_message']) && !empty($_POST['a2wl_aliship_product_not_available_message'])) ?
                        wp_unslash($_POST['a2wl_aliship_product_not_available_message']) : 'This product can not be delivered to {country}.');
                }

                set_setting('aliship_not_available_remove', isset($_POST['a2wl_aliship_not_available_remove']));

                set_setting('aliship_not_available_message',
                    (isset($_POST['a2wl_aliship_not_available_message']) && !empty($_POST['a2wl_aliship_not_available_message'])) ?
                    wp_unslash($_POST['a2wl_aliship_not_available_message']) : '[{shipping_cost}] {delivery_time} - {country}');

                $not_available_shipping_cost = (isset($_POST['a2wl_aliship_not_available_cost']) && floatval($_POST['a2wl_aliship_not_available_cost']) >= 0) ? floatval($_POST['a2wl_aliship_not_available_cost']) : 10;

                set_setting('aliship_not_available_cost', $not_available_shipping_cost);

                $min_time = (isset($_POST['a2wl_aliship_not_available_time_min']) && intval($_POST['a2wl_aliship_not_available_time_min']) > 0) ? intval($_POST['a2wl_aliship_not_available_time_min']) : 20;
                $max_time = (isset($_POST['a2wl_aliship_not_available_time_max']) && intval($_POST['a2wl_aliship_not_available_time_max']) > 0) ? intval($_POST['a2wl_aliship_not_available_time_max']) : 30;

                set_setting('aliship_not_available_time_min', $min_time);
                set_setting('aliship_not_available_time_max', $max_time);

            }

        }

        $countryModel = new Country();

        $this->model_put("shipping_countries", $countryModel->get_countries());

        $this->model_put("shipping_selection_types", Shipping::get_selection_types());

        $this->model_put("shipping_types", Shipping::get_shipping_types());

        $this->model_put("selection_position_types", Shipping::get_selection_position_types());

        $this->model_put("default_formula", ShippingPriceFormula::get_default_formula());

        $shipping_class = get_terms(array('taxonomy' => 'product_shipping_class', 'hide_empty' => false));
        $this->model_put("shipping_class", $shipping_class ? $shipping_class : array());

        return "settings/shipping.php";
    }

    private function phrase_filter(): string
    {
        $phrases = PhraseFilter::load_phrases();

        if ($phrases) {
            $this->model_put("phrases", $phrases);
        } else {
            $this->model_put("phrases", array());
        }

        return "settings/phrase_filter.php";
    }

    private function chrome_api(): string
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        $api_keys = get_setting('api_keys', []);

        if (!empty($_REQUEST['delete-key'])) {
            foreach ($api_keys as $k => $key) {
                if ($key['id'] === $_REQUEST['delete-key']) {
                    unset($api_keys[$k]);
                    set_setting('api_keys', $api_keys);
                    break;
                }
            }
            wp_redirect(admin_url('admin.php?page=a2wl_setting&subpage=chrome_api'));
        } else if (!empty($_POST['a2wl_api_key'])) {
            $key_id = sanitize_text_field($_POST['a2wl_api_key']);
            $key_name = !empty($_POST['a2wl_api_key_name']) ?
                sanitize_text_field($_POST['a2wl_api_key_name']) :
                "New key";

            $is_new = true;
            foreach ($api_keys as &$key) {
                if ($key['id'] === $key_id) {
                    $key['name'] = $key_name;
                    $is_new = false;
                    break;
                }
            }

            if ($is_new) {
                $api_keys[] = [
                    'id' => $key_id,
                    'name' => $key_name
                ];
            }

            set_setting('api_keys', $api_keys);

            wp_redirect(admin_url('admin.php?page=a2wl_setting&subpage=chrome_api&edit-key=' . $key_id));
        } else if (isset($_REQUEST['edit-key'])) {
            $api_key = [
                'id' => md5("a2wkey" . wp_rand() . microtime()),
                'name' => "New key"
            ];
            $is_new = true;
            if (empty($_REQUEST['edit-key'])) {
                $api_keys[] = $api_key;
                set_setting('api_keys', $api_keys);

                wp_redirect(admin_url('admin.php?page=a2wl_setting&subpage=chrome_api&edit-key=' . $api_key['id']));
            } else if (!empty($_REQUEST['edit-key']) && $api_keys && is_array($api_keys)) {
                foreach ($api_keys as $key) {
                    if ($key['id'] === $_REQUEST['edit-key']) {
                        $api_key = $key;
                        $is_new = false;
                    }
                }
            }
            $this->model_put("api_key", $api_key);
            $this->model_put("is_new_api_key", $is_new);
        }

        $this->model_put("api_keys", $api_keys);

        return "settings/chrome.php";
    }

    private function system_info(): string
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (isset($_POST['setting_form'])) {
            set_setting('write_info_log', isset($_POST['a2wl_write_info_log']));
        }

        $server_ip = '-';
        if (array_key_exists('SERVER_ADDR', $_SERVER)) {
            $server_ip = $_SERVER['SERVER_ADDR'];
        } elseif (array_key_exists('LOCAL_ADDR', $_SERVER)) {
            $server_ip = $_SERVER['LOCAL_ADDR'];
        } elseif (array_key_exists('SERVER_NAME', $_SERVER)) {
            $server_ip = gethostbyname($_SERVER['SERVER_NAME']);
        } else {
            // Running CLI
            if (stristr(PHP_OS, 'WIN')) {
                $server_ip = gethostbyname(php_uname("n"));
            } else {
                $ifconfig = shell_exec('/sbin/ifconfig eth0');
                preg_match('/addr:([\d\.]+)/', $ifconfig, $match);
                $server_ip = $match[1];
            }
        }

        $processorCores = $this->LocalService->getNumberOfProcessorCores();
        $systemAverageLoadStatus = true;
        $systemAverageLoad = $this->LocalService->getSystemLoadAverage();
        foreach ($systemAverageLoad as $systemLoad) {
            if ($systemLoad > $processorCores)   {
                $systemAverageLoadStatus = false;
                break;
            }
        }

        $memoryUsage = $this->LocalService->getMemoryUsageInBytes();

        $this->model_put("processorCores", $processorCores);
        $this->model_put(
            'systemLoadAverage', implode(', ', $systemAverageLoad)
        );
        $this->model_put("systemAverageLoadStatus", $systemAverageLoadStatus);
        $this->model_put("memoryUsage", size_format($memoryUsage));

        $this->model_put("server_ip", $server_ip);



        return "settings/system_info.php";
    }
}
