<?php

/**
 * Description of OrderFulfillmentController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 *
 * @ajax: true
 */
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
namespace AliNext_Lite;;

use Pages;
use RuntimeException;
use Throwable;
use WC_Order;
use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderFulfillmentController extends AbstractController
{
    protected ProductShippingDataRepository $ProductShippingDataRepository;
    protected ProductShippingDataService $ProductShippingDataService;
    protected WoocommerceService $WoocommerceService;
    protected Woocommerce $WoocommerceModel;
    protected OrderFulfillmentService $OrderFulfillmentService;
    protected ProductService $ProductService;
    protected ImportedProductServiceFactory $ImportedProductServiceFactory;
    protected OrderShippingDataService $OrderShippingDataService;

    protected static array $shipping_fields = [];
    protected static array $additional_shipping_fields = [];

    public function __construct(
            ProductShippingDataRepository $ProductShippingDataRepository,
            ProductShippingDataService $ProductShippingDataService,
            WoocommerceService $WoocommerceService,
            Woocommerce $WoocommerceModel,
            OrderFulfillmentService $OrderFulfillmentService,
            ProductService $ProductService,
            ImportedProductServiceFactory $ImportedProductServiceFactory,
            OrderShippingDataService $OrderShippingDataService,
    ) {
        parent::__construct(A2WL()->plugin_path() . '/view/');

        $this->ProductShippingDataRepository = $ProductShippingDataRepository;
        $this->ProductShippingDataService = $ProductShippingDataService;
        $this->WoocommerceService = $WoocommerceService;
        $this->WoocommerceModel = $WoocommerceModel;
        $this->OrderFulfillmentService = $OrderFulfillmentService;
        $this->ProductService = $ProductService;
        $this->ImportedProductServiceFactory = $ImportedProductServiceFactory;
        $this->OrderShippingDataService = $OrderShippingDataService;

        add_action('admin_init', [$this, 'admin_init']);

        add_filter('a2wl_wcol_bulk_actions_init', array($this, 'bulk_actions'));
        //todo: rudiment method for chrome extension fulfillment
        add_action('wp_ajax_a2wl_get_aliexpress_order_data', [$this, 'ajax_get_aliexpress_order_data']);

        add_action('wp_ajax_a2wl_load_fulfillment_model', [$this, 'ajaxLoadFulfillmentPopup']);
        add_action('wp_ajax_a2wl_load_fulfillment_orders', [$this, 'ajax_load_fulfillment_orders_html']);
        add_action('wp_ajax_a2wl_save_order_shipping_info', [$this, 'ajax_save_order_shipping_info']);

        add_action('wp_ajax_a2wl_fulfillment_place_order', [$this, 'ajax_load_fulfillment_place_order']);

        add_action('wp_ajax_a2wl_update_fulfillment_shipping', [$this, 'ajax_update_fulfillment_shipping']);

        add_action('wp_ajax_a2wl_sync_order_info', [$this, 'ajax_sync_order_info']);

        add_filter('a2wl_fill_additional_shipping_fields', array($this, 'fill_additional_shipping_fields'), 10, 3);
        add_action('a2wl_update_custom_shipping_field', [$this, 'update_custom_shipping_field'], 10, 3);

        $this->init();
    }

    public function admin_init(): void
    {
        if (function_exists('WC') && OrderUtil::custom_orders_table_usage_is_enabled()) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $currentPage = $_REQUEST['page'] ?? '';
            if ($currentPage === 'wc-orders') {
                add_action('admin_enqueue_scripts', [$this, 'assets']);
                add_action('admin_footer', [$this, 'place_orders_bulk_popup']);
                add_action('admin_footer', [$this, 'place_shipping_modal']);
            }
        }
        else {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $post_type = $_GET['post_type'] ?? ($_REQUEST['post_type'] ?? "");
            if (is_admin() && $post_type == "shop_order") {
                add_action('admin_enqueue_scripts', [$this, 'assets']);
                add_action('admin_footer', [$this, 'place_orders_bulk_popup']);
                add_action('admin_footer', [$this, 'place_shipping_modal']);
            }
        }
    }

    public function init(): void
    {
        $woocommerceCountries = [];
        if (function_exists('WC')) {
            $woocommerceCountries = WC()->countries->get_shipping_countries();
        }

        self::$shipping_fields = apply_filters(
            'woocommerce_admin_shipping_fields',
            [
                'first_name' => array(
                    'label' => esc_html__( 'First name', 'woocommerce' ),
                    'show'  => false,
                ),
                'last_name'  => array(
                    'label' => esc_html__( 'Last name', 'woocommerce' ),
                    'show'  => false,
                ),
                'company'    => array(
                    'label' => esc_html__( 'Company', 'woocommerce' ),
                    'show'  => false,
                ),
                'address_1'  => array(
                    'label' => esc_html__( 'Address line 1', 'woocommerce' ),
                    'show'  => false,
                ),
                'address_2'  => array(
                    'label' => esc_html__( 'Address line 2', 'woocommerce' ),
                    'show'  => false,
                ),
                'city'       => array(
                    'label' => esc_html__( 'City', 'woocommerce' ),
                    'show'  => false,
                ),
                'postcode'   => array(
                    'label' => esc_html__( 'Postcode / ZIP', 'woocommerce' ),
                    'show'  => false,
                ),
                'country'    => array(
                    'label'   => esc_html__( 'Country / Region', 'woocommerce' ),
                    'show'    => false,
                    'type'    => 'select',
                    'class'   => 'js_field-country select short',
                    'options' => array( '' => esc_html__( 'Select a country / region&hellip;', 'woocommerce' ) ) + $woocommerceCountries,
                ),
                'state'      => array(
                    'label' => esc_html__( 'State / County', 'woocommerce' ),
                    'class' => 'js_field-state select short',
                    'show'  => false,
                ),
                'phone'      => array(
                    'label' => esc_html__( 'Phone', 'woocommerce' ),
                ),
            ],
            false,
            false
        );

        self::$additional_shipping_fields = apply_filters(
            'woocommerce_admin_additional_shipping_fields',
            array(
                'passport_no' => array(
                    'label' => esc_html__( 'Passport Number', 'ali2woo' ),
                    'show'  => false,
                ),
                'passport_no_date'  => array(
                    'label' => esc_html__( 'Passport Expiry Date', 'ali2woo' ),
                    'show'  => false,
                ),
                'passport_organization'    => array(
                    'label' => esc_html__( 'Passport Issuing Authority', 'ali2woo' ),
                    'show'  => false,
                ),
                'tax_number'  => array(
                    'label' => esc_html__( 'Tax Identification Number', 'ali2woo' ),
                    'show'  => false,
                ),
                'foreigner_passport_no'  => array(
                    'label' => esc_html__( 'Foreign Tax ID', 'ali2woo' ),
                    'show'  => false,
                ),
                'is_foreigner'       => array(
                    'type'  => 'checkbox',
                    'label' => esc_html__( 'Is Foreigner', 'ali2woo' ),
                    'show'  => false,
                    'cbvalue' => '1',
                ),
                'vat_no'   => array(
                    'label' => esc_html__( 'VAT Registration Number', 'ali2woo' ),
                    'show'  => false,
                ),
                'tax_company'   => array(
                    'label' => esc_html__( 'Company Name', 'ali2woo' ),
                    'show'  => false,
                ),
            )
        );
    }

    public function assets()
    {
        wp_enqueue_style('a2wl-admin-style', A2WL()->plugin_url() . '/assets/css/admin_style.css', array(), A2WL()->version);
        wp_enqueue_style(
                'a2wl-admin-fulfillment',
                A2WL()->plugin_url() . '/assets/css/pages/admin-fulfillment.css',
                array('a2wl-admin-style'),
                A2WL()->version
        );

        wp_enqueue_script('a2wl-admin-script',
            A2WL()->plugin_url() . '/assets/js/admin_script.js',
            array('jquery'),
            A2WL()->version
        );
        AbstractAdminPage::localizeAdminScript();
        wp_enqueue_script('a2wl-ali-orderfulfill-js', A2WL()->plugin_url() . '/assets/js/orderfulfill.js', array('a2wl-admin-script'), A2WL()->version, true);

        wp_enqueue_script('a2wl-sprintf-script', A2WL()->plugin_url() . '/assets/js/sprintf.js', array(), A2WL()->version);

        $lang_data = array(
            'placing_orders_d_of_d' => _x('Placing orders %d/%d...', 'Status', 'ali2woo'),
            'please_wait_data_loads' => _x('Please wait, data loads..', 'Status', 'ali2woo'),
            'process_update_d_of_d_erros_d' => _x('Process update %d of %d. Errors: %d.', 'Status', 'ali2woo'),
            'process_sync_d_of_d_erros_d' => _x('Process sync %d of %d. Errors: %d.', 'Status', 'ali2woo'),
            'complete_result_updated_d_erros_d' => _x('Complete! Result updated: %d; errors: %d.', 'Status', 'ali2woo'),
            'complete_result_sync_d_erros_d' => _x('Complete! Successfully synced: %d; errors: %d.', 'Status', 'ali2woo'),
            'install_chrome_ext' => _x('Please install and connect to your website the Ali2Woo chrome extension to use this feature.', 'Status', 'ali2woo'),
            'please_connect_chrome_extension_check_d' => _x('Please connect the Chrome extension to your store and then continue. Need help? Check out <a href="%s">the instruction</a>', 'Status', 'ali2woo'),
            'we_found_old_order' => _x('We found an old order fulfillment process and removed it. Press the "Continue" button.', 'Status', 'ali2woo'),
            'login_into_aliexpress_account' => _x('Please switch to AliExpress tab and login into your AliExpress account.', 'Status', 'ali2woo'),
            'detected_old_aliexpress_interface' => _x('Detected old AliExpress interface. Please contact Ali2Woo support.', 'Status', 'ali2woo'),
            'your_customer_address_entered' => _x('Your customer address is entered. Wait...', 'Status', 'ali2woo'),
            'product_is_added_to_cart' => _x('Product (%d) is added to the cart. Wait...', 'Status', 'ali2woo'),
            'all_products_are_added' => _x('All products are added to the cart. Wait...', 'Status', 'ali2woo'),
            'cart_is_cleared' => _x('The previous cart data is cleared. Wait...', 'Status', 'ali2woo'),
            'get_no_responces_from_chrome_ext_d' => _x('Get no responces from the chrome extension for 30s. Check out <a href="%s">the instruction</a>', 'Status', 'ali2woo'),
            'fill_order_note' => _x('Filling order notes...', 'Status', 'ali2woo'),
            'cant_add_product_to_cart_d' => _x('Can`t add this product to the cart. Switch to AliExpress and choose another one or add a similar product from another supplier manually. Then continue. Check out <a href="%s">the instruction</a>', 'Status', 'ali2woo'),
            'please_type_customer_address' => _x('Please switch to AliExpress tab, type the address or skip this order.', 'Status', 'ali2woo'),
            'please_input_captcha' => _x('Please switch to AliExpress and input the Captcha code manually or wait for your Captcha solver to do the job...', 'Status', 'ali2woo'),
            'order_is_placed' => _x('The order is placed. Wait...', 'Status', 'ali2woo'),
            'internal_aliexpress_error' => _x('Internal AliExpress error. Please continue to try again or skip this order.', 'Status', 'ali2woo'),
            'all_orders_are_placed' => _x('All orders are placed! Click "Orders List" to be directed to the orders list on the AliExpress website.', 'Status', 'ali2woo'),
            'cant_process_your_orders' => _x('We can`t process your orders. Check out the "Status Page" for more details.', 'Status', 'ali2woo'),
            'cant_get_order_id' => _x('Can`t get the external order ID, please copy it manually to your WC order. Then continue.', 'Status', 'ali2woo'),
            'payment_is_failed' => _x('The payment is failed, please finish this order manually. Then continue.', 'Status', 'ali2woo'),
            'done_pay_manually' => _x('Please switch to AliExpress and pay for the order.', 'Status', 'ali2woo'),
            'choose_payment_method' => _x('Please switch to AliExpress and choose payment method.', 'Status', 'ali2woo'),

            'please_activate_right_store_apikey_in_chrome' => _x('This website is not connected to the Ali2Woo chrome extension. Please check that you choose right API key.', 'Status', 'ali2woo'),

            'bad_product_id' => _x('Can`t find the WC order with a given ID.', 'Status', 'ali2woo'),
            'no_variable_data' => _x('This order has a variable product but doesn`t contain the variable data for some reason. Check out <a href="%s">the instruction</a>', 'Status', 'ali2woo'),
            'no_product_url' => _x('This order doesn`t contain the `product_url` field for some reason. Check out <a href="%s">the instruction</a>', 'Status', 'ali2woo'),
            'no_ali_products' => _x('No AliExpress products in the current order. Check out <a href="%s">the instruction</a>', 'Status', 'ali2woo'),

            'unknown_error' => _x('Unknown error occured. Please contact support.', 'Status', 'ali2woo'),
            'server_error' => _x('Server error. Continue to try again.', 'Status', 'ali2woo'),
        );

        $data = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce_action' => wp_create_nonce(self::AJAX_NONCE_ACTION),
            'lang' => $lang_data,
        ];

        wp_localize_script('a2wl-ali-orderfulfill-js', 'a2wl_ali_orderfulfill_js', $data);

        $lang_data = [
            'sync_failed_in_fulfillment_popup' => esc_html_x(
                'Product can`t be synchronized, so its shipping information cannot be loaded.',
                'Status',
                'ali2woo'
            ),
        ];
        wp_localize_script('a2wl-admin-script', 'a2wl_sync_data', ['lang' => $lang_data]);
    }

    public function bulk_actions(array $bulk_actions): array
    {
        $bulk_actions['a2wl_order_place_bulk'] = esc_html__("Place on AliExpress", 'ali2woo');
        $bulk_actions['a2wl_order_sync_bulk'] = esc_html__("Sync with AliExpress", 'ali2woo');

        return $bulk_actions;
    }

    public function fill_additional_shipping_fields($additional_shipping_fields, $order, $country = false)
    {
        if (!$country) {
            $country = $this->get_order_shipping_to_country($order);
        }

        $rutMetaKey = get_setting('fulfillment_rut_meta_key', '');

        $custom_attributes = [];

        $value = '';

        if (empty($rutMetaKey)) {
            $custom_attributes['disabled'] = 'disabled';
        } else {
            $value = get_post_meta($order->get_id(), $rutMetaKey, true);
        }

        $additional_shipping_fields['rut'] = [
            'id' => 'rut',
            'description' => esc_html__('You have to configure RUT meta field in the plugin settings',  'ali2woo'),
            'desc_tip' => true,
            'label' => esc_html__( 'RUT', 'ali2woo' ),
            'show'  => false,
            'wrapper_class' => '_shipping_rut '.($country !== 'CL' ? 'hidden' : ''),
            'custom_attributes' => $custom_attributes,
            'value' => $value,
            'custom' => [
                'meta_key' => $rutMetaKey
            ]
        ];

        $cpfMetaKey = get_setting('fulfillment_cpf_meta_key', '');

        $custom_attributes = [];

        $value = '';

        if (empty($cpfMetaKey)) {
            $custom_attributes['disabled'] = 'disabled';
        } else {
            $value = get_post_meta($order->get_id(), $cpfMetaKey, true);
        }

        $additional_shipping_fields['cpf'] = [
            'id' => 'cpf',
            'description' => esc_html__('You have to configure CPF meta field in the plugin settings',  'ali2woo'),
            'desc_tip' => true,
            'label' => esc_html__( 'CPF', 'ali2woo' ),
            'show'  => false,
            'wrapper_class' => '_shipping_cpf '.($country !== 'BR' ? 'hidden' : ''),
            'custom_attributes' => $custom_attributes,
            'value' => $value,
            'custom' => [
                'meta_key' => $cpfMetaKey
            ]
        ];

        $passportSetting = get_setting(Settings::SETTING_FULFILLMENT_PASSPORT_NUMBER, '');
        if ($passportSetting !== '') {
            $additional_shipping_fields['passport_no']['value'] = $passportSetting;
        } else {
            $additional_shipping_fields['passport_no']['value'] = get_post_meta(
                $order->get_id(),
                '_shipping_passport_no',
                true
            );
        }

        $foreignTaxIdSetting = get_setting(Settings::SETTING_FULFILLMENT_FOREIGN_TAX_ID, '');
        if ($foreignTaxIdSetting !== '') {
            $additional_shipping_fields['foreigner_passport_no']['value'] = $foreignTaxIdSetting;
        } else {
            $additional_shipping_fields['foreigner_passport_no']['value'] = get_post_meta(
                $order->get_id(),
                '_shipping_foreigner_passport_no',
                true
            );
        }

        $isForeignerSetting = get_setting(Settings::SETTING_FULFILLMENT_IS_FOREIGNER, false);
        if ($isForeignerSetting) {
            $additional_shipping_fields['is_foreigner']['value'] = '1';
        } else {
            $additional_shipping_fields['is_foreigner']['value'] = get_post_meta($order->get_id(), '_shipping_is_foreigner', true);
        }

        return $additional_shipping_fields;
    }

    public function update_custom_shipping_field($key, $field, $order): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if (in_array($key, ['rut', 'cpf']) && $field['custom']['meta_key'] && !empty($_POST[$key])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            update_post_meta($order->get_id(), $field['custom']['meta_key'], $_POST[$key]);
        }
    }

    public function place_orders_bulk_popup()
    {
        $this->include_view('includes/place_orders_bulk_popup.php');
    }

    public function place_shipping_modal()
    {
        $this->model_put('countries', WC()->countries->get_countries());
        $this->model_put('disableCountryTo', true);
        $this->include_view('includes/shipping-modal/modal.php');
    }

    public function ajax_get_aliexpress_order_data(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::ORDER_MANAGEMENT)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $post_id = $_POST['id'] ?? false;

        if (!$post_id) {
            $result = ResultBuilder::buildError('', array('error_code' => -1));
            echo wp_json_encode($result);
            wp_die();
        }

        $post_id = intval($post_id);

        $order = new WC_Order($post_id);

        try {
            $result = $this->OrderShippingDataService->buildOrderContent($order, $post_id);
        } catch (RuntimeException $e) {
            $result = ResultBuilder::buildError('', array('error_code' => $e->getCode()));
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajaxLoadFulfillmentPopup(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::ORDER_MANAGEMENT)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (EditionHelper::isLite()) {
            $purchase_code = 1;
        } else {
            $purchase_code = Account::getInstance()->get_purchase_code();
        }

        $this->model_put('purchase_code', $purchase_code);
        $this->include_view('order-fulfillment/fulfillment_modal.php');

        wp_die();
    }

    public function get_order_shipping_to_country($order): string
    {
        $shipping_address = $order->get_address('shipping');
        if (empty($shipping_address['country'])) {
            $shipping_address = $order->get_address('billing');
        }

        $AliexpressHelper = A2WL()->getDI()->get('AliNext_Lite\AliexpressHelper');

        return $AliexpressHelper->convertToAliexpressCountryCode($shipping_address['country']);
    }

    /**
     * On order fulfillment popup loading
     * @return void
     */
    public function ajax_load_fulfillment_orders_html(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::ORDER_MANAGEMENT)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $ids = array_map(
                'intval',
                isset($_POST['ids']) ? (is_array($_POST['ids']) ? $_POST['ids'] : [$_POST['ids']]) : []
        );

        $orders = [];
        if (!empty($ids)) {
            foreach ($ids as $order_id) {
                $orders[] = new WC_Order($order_id);
            }
        }

        $is_wpml = $this->isWpml();

        try {
            $orders_data = $this->OrderFulfillmentService->getFulfillmentOrdersData($orders, $is_wpml);
        } catch (RepositoryException|ServiceException $Exception) {
            $this->model_put("text", $Exception->getMessage());
            $this->include_view("order-fulfillment/error_container.php");
            wp_die();
        }

         if (empty($orders_data)) {
            $text = esc_html__("Orders not found", 'ali2woo');
            $this->model_put("text", $text);
            $this->include_view("order-fulfillment/error_container.php");
        } else {
            $this->model_put("shipping_fields", self::$shipping_fields);
            $this->model_put("additional_shipping_fields", self::$additional_shipping_fields);
            $this->model_put("countries", WC()->countries->get_countries());
            $this->model_put('ProductShippingDataRepository', $this->ProductShippingDataRepository);
            $this->model_put("ProductShippingDataService", $this->ProductShippingDataService);
            $this->model_put("OrderShippingDataService", $this->OrderShippingDataService);
            $this->model_put("ImportedProductServiceFactory", $this->ImportedProductServiceFactory);

            foreach ($orders_data as $order_data) {
                $urls_to_data = '';
                if (!empty($order_data['sign_urls'])) {
                    $urls_to_data = implode(';', $order_data['sign_urls']);
                }

                $this->model_put("order_data", $order_data);
                $this->model_put("urls_to_data", $urls_to_data);
                $this->include_view("order-fulfillment/single_order_container.php");
            }
        }

        wp_die();
    }

    public function ajax_save_order_shipping_info(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::ORDER_MANAGEMENT)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $shipping_to_country = $_POST['_shipping_country'] ?? false;

        if (empty($_POST['order_id'])) {
            $result = ResultBuilder::buildError('waiting for order id');
            echo wp_json_encode($result);
            wp_die();
        }

        // Get order object.
        $order = wc_get_order($_POST['order_id']);
        $props = [];

        $additional_shipping_fields = apply_filters(
                'a2wl_fill_additional_shipping_fields',
                self::$additional_shipping_fields,
                $order,
                $shipping_to_country
        );

        $shipping_fields = array_merge(self::$shipping_fields, $additional_shipping_fields);

        // Update shipping fields.
        if ( !empty($shipping_fields)) {
            foreach ($shipping_fields as $key => $field) {
                if (!isset($field['id'])) {
                    $field['id'] = '_shipping_' . $key;
                }

                if (!isset($_POST[$field['id']])) {
                    continue;
                }

                // The phone number and code are stored in the plugin's own
                // order meta fields (_shipping_phone_number, _shipping_phone_code)
                // and never overwrite the phone number the customer entered
                // in the original order.
                if ($key === 'phone') {
                    continue;
                }

                if (!empty($field['custom'])) {
                    do_action('a2wl_update_custom_shipping_field', $key, $field, $order);
                } else {
                    if (is_callable(array($order, 'set_shipping_' . $key))) {
                        $props['shipping_' . $key] = wc_clean(wp_unslash($_POST[$field['id']]));
                    } else {
                        $order->update_meta_data($field['id'], wc_clean(wp_unslash($_POST[$field['id']])));
                    }
                }
            }
        }

        // Save order data.
        if (!$this->OrderShippingDataService->isShippingPhoneOverridden()) {
            if (isset($_POST['_shipping_phone'])) {
                $order->update_meta_data('_shipping_phone_number', wc_clean(wp_unslash($_POST['_shipping_phone'])));
            }
            if (isset($_POST['_shipping_phone_code'])) {
                $order->update_meta_data('_shipping_phone_code', wc_clean(wp_unslash($_POST['_shipping_phone_code'])));
            }
        }

        $order->set_props($props);
        $order->save();

        if ($shipping_to_country) {
            foreach ($order->get_items() as $item) {
                $WC_Product = $item->get_product();

                $quantity = $item->get_quantity();
                $countryFromCode = 'CN';
                try {
                    $importedProduct = $this->WoocommerceService
                        ->updateProductShippingItems($WC_Product, $shipping_to_country, $countryFromCode, $quantity);

                    $shippingItems = $this->ProductService->getShippingItems(
                        $importedProduct, $shipping_to_country, $countryFromCode
                    );
                } catch (RepositoryException|ServiceException $Exception) {
                    a2wl_error_log($Exception->getMessage());
                    continue;
                }

                $ShippingItemDto = $this->ProductService->findDefaultFromShippingItems(
                        $shippingItems, $importedProduct
                );

                $shipping_meta_data = $item->get_meta(Shipping::get_order_item_shipping_meta_key());
                $shipping_meta_data = $shipping_meta_data ? json_decode($shipping_meta_data, true) :
                    [
                        'company' => '',
                        'service_name' => '',
                        'delivery_time' => '',
                        'shipping_cost' => '',
                        'quantity' => $item->get_quantity(),
                        'cost_added' => true
                    ];

                foreach ($shippingItems as $shippingItem) {
                    if ($shippingItem['serviceName'] === $ShippingItemDto->getMethodName()) {
                        $shipping_meta_data['company'] = $shippingItem['company'];
                        $shipping_meta_data['service_name'] = $shippingItem['serviceName'];
                        $shipping_meta_data['shipping_cost'] = $shippingItem['freightAmount']['value'];
                        $shipping_meta_data['delivery_time'] = $shippingItem['time'];
                    }
                }

                $item->update_meta_data(
                        Shipping::get_order_item_shipping_meta_key(), wp_json_encode($shipping_meta_data)
                );
                $item->save_meta_data();
            }
        }

        echo wp_json_encode(ResultBuilder::buildOK());
        wp_die();
    }

    public function ajax_update_fulfillment_shipping(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::ORDER_MANAGEMENT)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $errorText = _x('Shipping method not found', 'error text', 'ali2woo');
        $result = ResultBuilder::buildError($errorText);

        $is_wpml = $this->isWpml();

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $shiping_to_country = $_POST['shiping_to_country'] ?? false;
        $items = isset($_POST['items']) && is_array($_POST['items']) ? $_POST['items'] : [];

        $order_items = [];
        foreach ($items as $item) {
            $order_items[] = new OrderItemShippingDto($item['order_item_id'], $item['shipping']);
        }

        if ($shiping_to_country && $order_id) {
            $order = new WC_Order($order_id);

            $UpdateFulfillmentShippingResult = $this->OrderFulfillmentService->updateFulfillmentShipping(
                $order, $order_items, $shiping_to_country, $is_wpml
            );

            $result = ResultBuilder::buildOk(['result' => [
                'order_id' => $order_id,
                'total_order_price' => wc_price(
                        $UpdateFulfillmentShippingResult->getTotalOrderPrice(),
                        ['currency' => $order->get_currency()]
                ),
                'items' => $UpdateFulfillmentShippingResult->getResultItems(),
            ]]);
        } else {
            $errorText = _x('wrong params', 'error text', 'ali2woo');
            $result = ResultBuilder::buildError($errorText);
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_load_fulfillment_place_order(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::ORDER_MANAGEMENT)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $orderItemsIds = isset($_POST['items']) && is_array($_POST['items']) ?
            array_map('intval', $_POST['items']) : [];

        if ($order_id && $orderItemsIds) {
            $WC_Order = new WC_Order($order_id);
            $OrderItems = [];
            foreach ($WC_Order->get_items() as $orderItem) {
                if (in_array($orderItem->get_id(), $orderItemsIds)) {
                    $OrderItems[] = $orderItem;
                }
            }
            $result = $this->OrderFulfillmentService->placeOrder($WC_Order, $OrderItems);
        } else {
            $result = ResultBuilder::buildError('Wrong params');
        }

        echo wp_json_encode($result);
        wp_die();
    }

    public function ajax_sync_order_info(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::ORDER_MANAGEMENT)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        if (empty($_POST['order_id'])) {
            $result = ResultBuilder::buildError('wrong params');
        } else {

            $orderId = intval($_POST['order_id']);
            a2wl_init_error_handler();
            try {
                $result = ResultBuilder::buildOk();
                $WC_Order = wc_get_order($orderId);
                if (!$WC_Order) {
                    $errorMessage = esc_html_x('Order not found', 'error text', 'ali2woo');
                    $result = ResultBuilder::buildError($errorMessage);
                }

                $this->WoocommerceService->syncOrderWithAliexpress($WC_Order);

            } catch (Throwable $Exception) {
                a2wl_print_throwable($Exception);
                $result = ResultBuilder::buildError($Exception->getMessage());
            }
        }

        echo wp_json_encode($result);
        wp_die();
    }

    private function isWpml(): bool
    {
        $is_wpml = false;
        if (is_plugin_active('sitepress-multilingual-cms/sitepress.php')) {
            $default_lang = apply_filters('wpml_default_language', null);
            $current_language = apply_filters('wpml_current_language', null);
            if ($current_language && $current_language !== $default_lang) {
                $is_wpml = true;
            }
        }

        return $is_wpml;
    }
}
