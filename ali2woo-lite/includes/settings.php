<?php
/**
 * Description of Settings
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

use Pages;

class Settings
{
    public const DEFAULT_AUTO_UPDATE_MAX_QUOTA = '75';


    public const SETTING_ALIEXPRESS_REGION = 'aliexpress_region';
    public const SETTING_SYSTEM_MESSAGE = 'system_message';
    public const SETTING_CRITICAL_MESSAGES = 'critical_messages';
    public const SETTING_TIP_OF_DAY = 'tip_of_day';
    public const SETTING_TIP_OF_DAY_LAST_DATE = 'tip_of_day_last_date';
    public const SETTING_IMPORT_VIDEO = 'import_video';
    public const SETTING_SHOW_PRODUCT_VIDEO_TAB = 'show_product_video_tab';
    public const SETTING_VIDEO_TAB_PRIORITY = 'video_tab_priority';
    public const SETTING_MAKE_VIDEO_FULL_TAB_WIDTH = 'make_video_full_tab_width';
    public const SETTING_ADD_VIDEO_TO_DESCRIPTION = 'add_video_to_description';
    public const SETTING_ALLOW_SHOP_MANAGER = 'allow_shop_manager';
    public const SETTING_HIDDEN_PAGES = 'hidden_pages';


    //shipping settings
    public const SETTING_ADD_SHIPPING_TO_PRICE = 'add_shipping_to_price';
    public const SETTING_ALLOW_SHIPPING_FRONTEND = 'aliship_frontend';
    public const SETTING_SYNC_PRODUCT_SHIPPING = 'sync_product_shipping';
    public const SETTING_ASSIGN_SHIPPING_ON_IMPORT = 'assign_shipping_on_import';
    public const SETTING_DELIVERY_INFO_DISPLAY_MODE = 'delivery_info_display_mode';

    public const SETTING_DELIVERY_TIME_TEXT_FORMAT = 'delivery_time_text_format';
    public const SETTING_DELIVERY_TIME_FALLBACK_MIN  = 'delivery_time_fallback_min';
    public const SETTING_DELIVERY_TIME_FALLBACK_MAX = 'delivery_time_fallback_max';

    public const SETTING_SHIPPING_ON_PRODUCT_PAGE = 'aliship_product_enable';


    public const DEFAULTS = [
        self::SETTING_ADD_SHIPPING_TO_PRICE => false,
        self::SETTING_ALLOW_SHIPPING_FRONTEND => false,
        self::SETTING_SYNC_PRODUCT_SHIPPING => false,
        self::SETTING_DELIVERY_INFO_DISPLAY_MODE => 'default',
        self::SETTING_DELIVERY_TIME_TEXT_FORMAT => 'Estimated delivery: {delivery_time}',
        self::SETTING_DELIVERY_TIME_FALLBACK_MIN => 20,
        self::SETTING_DELIVERY_TIME_FALLBACK_MAX => 30,
        self::SETTING_SHIPPING_ON_PRODUCT_PAGE => false,
    ];


    /**
     * Value in percents from 25 to 100
     */
    public const SETTING_AUTO_UPDATE_MAX_QUOTA = 'auto_update_max_quota';

    private $settings;
    private $auto_commit = true;

    private $static_settings = [
        'api_endpoint' => 'https://api.ali2woo.com/v4/',
        'client_id' => 33446317,
        'image_editor_srickers' => [
            '/assets/img/stickers/stick-001.png',
            '/assets/img/stickers/stick-002.png',
            '/assets/img/stickers/stick-003.png',
            '/assets/img/stickers/stick-004.png',
            '/assets/img/stickers/stick-005.png',
            '/assets/img/stickers/stick-006.png',
            '/assets/img/stickers/stick-007.png',
            '/assets/img/stickers/stick-008.png',
            '/assets/img/stickers/stick-009.png',
            '/assets/img/stickers/stick-010.png',
            '/assets/img/stickers/stick-011.png',
            '/assets/img/stickers/stick-012.png',
            '/assets/img/stickers/stick-013.png',
            '/assets/img/stickers/stick-014.png',
            '/assets/img/stickers/stick-015.png',
            '/assets/img/stickers/stick-016.png',
            '/assets/img/stickers/stick-017.png',
            '/assets/img/stickers/stick-018.png',
            '/assets/img/stickers/stick-019.png',
            '/assets/img/stickers/stick-020.png',
            '/assets/img/stickers/stick-021.png',
            '/assets/img/stickers/stick-022.png'
        ],
    ];

    private $default_settings = [
        'item_purchase_code' => '',
        'aliexpress_access_tokens' => [],
        'account_type' => 'aliexpress',
        'use_custom_account' => false,
        'account_data' => ['aliexpress' => ['appkey' => '', 'trackingid' => ''], 'admitad' => ['cashback_url' => '']],

        'import_language' => 'en',
        'local_currency' => 'USD',
        'default_product_type' => 'simple',
        'default_product_status' => 'publish',
        'not_import_attributes' => false,
        'not_import_description' => false,
        'not_import_description_images' => false,
        'import_extended_attribute' => false,
        'import_product_images_limit' => 0,
        'use_external_image_urls' => true,
        'use_random_stock' => false,
        'use_random_stock_min' => 5,
        'use_random_stock_max' => 15,
        'split_attribute_values' => true,
        'attribute_values_separator' => ',',
        'currency_conversion_factor' => 1,
        'background_import' => true,
        'convert_attr_case' => 'original',
        'allow_product_duplication' => true,
        'remove_ship_from' => false,
        'default_ship_from' => 'CN',

        self::SETTING_ALIEXPRESS_REGION => 'US',
        self::SETTING_IMPORT_VIDEO => true,
        self::SETTING_SHOW_PRODUCT_VIDEO_TAB => false,
        self::SETTING_VIDEO_TAB_PRIORITY => 50,
        self::SETTING_MAKE_VIDEO_FULL_TAB_WIDTH => false,
        self::SETTING_ADD_VIDEO_TO_DESCRIPTION => 'none', //none, before, after

        'auto_update' => false,
        self::SETTING_AUTO_UPDATE_MAX_QUOTA => self::DEFAULT_AUTO_UPDATE_MAX_QUOTA,
        'on_not_available_product' => 'trash', // nothing, trash, zero
        'on_not_available_variation' => 'trash', // nothing, trash, zero
        'on_new_variation_appearance' => 'add', // nothing, add
        'on_price_changes' => 'update', // nothing, update
        'on_stock_changes' => 'update', // nothing, update
        'untrash_product' => false,
        'email_alerts' => false,
        'email_alerts_email' => '',

        'fulfillment_prefship' => 'CAINIAO_FULFILLMENT_STD',
        'fulfillment_phone_code' => '',
        'fulfillment_phone_number' => '',
        'fulfillment_custom_note' => '',
        'fulfillment_cpf_meta_key' => '',
        'fulfillment_rut_meta_key' => '',
        'order_translitirate' => false,
        'order_third_name' => false,
        'pricing_rules_type' => PriceFormulaService::SALE_PRICE_AS_BASE,
        'use_extended_price_markup' => false,
        'use_compared_price_markup' => false,
        'price_cents' => -1,
        'price_compared_cents' => -1,
        'default_formula' => false,
        'formula_list' => [],
        self::SETTING_ADD_SHIPPING_TO_PRICE => self::DEFAULTS[self::SETTING_ADD_SHIPPING_TO_PRICE],
        'apply_price_rules_after_shipping_cost' => false,

        'phrase_list' => [],

        'load_review' => false,
        'review_status' => false,
        'review_translated' => false,
        'review_avatar_import' => false,
        'review_min_per_product' => 10,
        'review_max_per_product' => 20,
        'review_raiting_from' => 1,
        'review_raiting_to' => 5,
        'review_noavatar_photo' => null,
        'review_load_attributes' => false,
        'review_show_image_list' => false,
        'review_thumb_width' => 30,
        'moderation_reviews' => false,
        'review_skip_keywords' => '',
        'review_skip_empty' => false,
        'review_country' => [],

        'aliship_shipto' => 'US',
        //'aliship_shipfrom' - rudiment setting, use 'aliexpress_region' now
        'aliship_shipfrom' => 'CN',
        'default_shipping_class' => false,
        self::SETTING_ALLOW_SHIPPING_FRONTEND => self::DEFAULTS[self::SETTING_ALLOW_SHIPPING_FRONTEND],
        self::SETTING_SYNC_PRODUCT_SHIPPING => self::DEFAULTS[self::SETTING_SYNC_PRODUCT_SHIPPING],
        self::SETTING_ASSIGN_SHIPPING_ON_IMPORT => false,
        self::SETTING_DELIVERY_INFO_DISPLAY_MODE => self::DEFAULTS[self::SETTING_DELIVERY_INFO_DISPLAY_MODE],
        self::SETTING_DELIVERY_TIME_TEXT_FORMAT => self::DEFAULTS[self::SETTING_DELIVERY_TIME_TEXT_FORMAT],
        self::SETTING_DELIVERY_TIME_FALLBACK_MIN => self::DEFAULTS[self::SETTING_DELIVERY_TIME_FALLBACK_MIN],
        self::SETTING_DELIVERY_TIME_FALLBACK_MAX => self::DEFAULTS[self::SETTING_DELIVERY_TIME_FALLBACK_MAX],
        'aliship_shipping_method' => 'default',
        'aliship_selection_type' => 'popup',
        'aliship_shipping_type' => 'new',
        'aliship_shipping_option_text' => '[{shipping_cost}] {shipping_company} ({delivery_time}) - {country}',
        'aliship_shipping_label' => 'Shipping',
        'aliship_free_shipping_label' => 'Free Shipping',
        self::SETTING_SHIPPING_ON_PRODUCT_PAGE => self::DEFAULTS[self::SETTING_SHIPPING_ON_PRODUCT_PAGE],
        'aliship_product_not_available_message' => 'This product can not be delivered to {country}.',
        'aliship_product_position' => 'after_cart',
        'aliship_not_available_remove' => false,
        'aliship_not_available_message' => '[{shipping_cost}] {delivery_time} - {country}',
        'aliship_not_available_cost' => 10,
        'aliship_not_available_time_min' => 20,
        'aliship_not_available_time_max' => 30,

        'json_api_base' => 'a2wl_api',
        'json_api_controllers' => 'core,auth',

        'system_message_last_update' => 0,
        self::SETTING_SYSTEM_MESSAGE => [],
        self::SETTING_CRITICAL_MESSAGES => [],
        self::SETTING_TIP_OF_DAY => [],
        self::SETTING_TIP_OF_DAY_LAST_DATE => null,

        self::SETTING_ALLOW_SHOP_MANAGER => false,
        self::SETTING_HIDDEN_PAGES => [Pages::SETTINGS, Pages::ADDONS, Pages::WIZARD, Pages::JSON_API, Pages::DEBUG],

        'api_keys' => [],

        'chrome_ext_import' => false,

        'write_info_log' => false,
    ];

    private static $_instance = null;

    protected function __construct()
    {
        //todo: refactor this later
        $this->default_settings[self::SETTING_TIP_OF_DAY] = $this->getDefaultTipOfDayData();

        $this->load();
    }

    protected function __clone()
    {

    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function auto_commit($auto_commit = true)
    {
        $this->auto_commit = $auto_commit;
    }

    public function load()
    {
        $static_settings = $this->static_settings;

        if (a2wl_check_defined("A2WL_API_ENDPOINT")) {
            $static_settings['api_endpoint'] = A2WL_API_ENDPOINT;
        }

        if (a2wl_check_defined("A2WL_DO_NOT_USE_HTTPS")) {
            $static_settings['api_endpoint'] = str_replace("https", "http", $static_settings['api_endpoint']);
        }
        $this->settings = array_merge(
            $this->default_settings, get_option('a2wl_settings', array()), $static_settings);
    }

    public function commit()
    {
        update_option('a2wl_settings', $this->settings, 'no');
    }

    public function to_string()
    {}

    public function from_string($str)
    {}

    public function get($setting, $default = '')
    {
        return $this->settings[$setting] ?? $default;
    }

    public function set($setting, $value)
    {
        $old_value = isset($this->settings[$setting]) ? $this->settings[$setting] : '';

        do_action('a2wl_pre_set_setting_' . $setting, $old_value, $value, $setting);

        $this->settings[$setting] = $value;

        if ($this->auto_commit) {
            $this->commit();
        }

        do_action('a2wl_set_setting_' . $setting, $old_value, $value, $setting);
    }

    public function del($setting)
    {
        if (isset($this->settings[$setting])) {
            unset($this->settings[$setting]);

            if ($this->auto_commit) {
                $this->commit();
            }
        }
    }

    //todo: refactor this method later
    private function getDefaultTipOfDayData(): array
    {
        $htmlContent =
            <<<HTML
            <p>
            Transform your AliExpress account into a Business Account using our exclusive invitation code. 
            This linkage ensures AliExpress recognizes you as a dropshipping partner, opening the door to new earning potentials.
            </p>
            <p>
            <strong>Instant Reward:</strong> Secure your Business Account status with our code and receive a special bonus $100 off over $500. 
            This bonus is valid for 90 days, so start maximizing your benefits now.
            </p>
            <p><strong>Additional Earnings:</strong>  Enjoy increased dropshipper commissions based on your Partnership Account's purchase volumes. 
            Seize this chance to boost your business and income!</p>
            <p>
            <strong><a target="_blank" href="https://inbusiness.aliexpress.com/web/newCertification?bizScene=STANDARD_SCENE&channel=STANDARD_CHANNEL&invitationCode=2qkht5">CLICK OUR INVITATION LINK</a></strong> and begin your journey toward enhanced dropshipping success today!
            </p>
HTML;

        return [
            [
                TipOfDay::FIELD_ID => 1,
                TipOfDay::FIELD_NAME => 'Tip of the Day: Boost Your AliExpress Earnings!',
                TipOfDay::FIELD_HTML_CONTENT => $htmlContent,
                TipOfDay::FIELD_IS_HIDDEN => false,
            ],
        ];
    }
}

if (!function_exists('settings')) {
    function settings()
    {
        return Settings::instance();
    }
}

if (!function_exists('get_setting')) {
    function get_setting($setting, $default = '')
    {
        if (!$default && isset(Settings::DEFAULTS[$setting])) {
            $default = Settings::DEFAULTS[$setting];
        }

        return settings()->get($setting, $default);
    }
}

if (!function_exists('set_setting')) {
    function set_setting($setting, $value)
    {
        if (a2wl_check_defined('A2WL_DEMO_MODE') && in_array($setting, array('use_external_image_urls'))) {
            return;
        }

        return settings()->set($setting, $value);
    }
}

if (!function_exists('del_setting')) {
    function del_setting($setting)
    {
        return settings()->del($setting);
    }
}
