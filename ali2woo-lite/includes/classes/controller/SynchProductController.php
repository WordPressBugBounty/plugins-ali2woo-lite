<?php

/**
 * Description of SynchProductController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_init
 *
 * @cron: true
 */

namespace AliNext_Lite;;

use Throwable;

class SynchProductController extends AbstractController
{

    protected ProductService $ProductService;
    protected ProductReviewsService $ProductReviewsService;
    protected Woocommerce $WoocommerceModel;
    protected PriceFormulaService $PriceFormulaService;
    protected WoocommerceService $WoocommerceService;

    private $update_per_schedule = 100;
    private $update_per_request = 5;
    private $update_period_delay = 60 * 60 * 24;

    public function __construct(
        ProductService $ProductService,
        ProductReviewsService $ProductReviewsService,
        Woocommerce $WoocommerceModel,
        PriceFormulaService $PriceFormulaService,
        WoocommerceService $WoocommerceService
    ) {
        parent::__construct();

        $this->ProductService = $ProductService;
        $this->ProductReviewsService = $ProductReviewsService;
        $this->WoocommerceModel = $WoocommerceModel;
        $this->PriceFormulaService = $PriceFormulaService;
        $this->WoocommerceService = $WoocommerceService;

        add_action('a2wl_install', array($this, 'install'));

        add_action('a2wl_uninstall', array($this, 'uninstall'));

        add_filter('cron_schedules', array($this, 'init_reccurences'));

        add_action('admin_init', array($this, 'init'));

        add_action('a2wl_synch_event_check', array($this, 'synch_event_check'));

        if (get_setting('auto_update')) {
            add_action('a2wl_update_products_event', array($this, 'update_products_event'));

            if (get_setting('email_alerts')) {
                add_action('a2wl_email_alerts_event', array($this, 'email_alerts_event'));
            }
        }

        if (get_setting('load_review') && get_setting('review_status')) {
            add_action('a2wl_update_reviews_event', array($this, 'update_reviews_event'));
        }
    }

    public function init_reccurences($schedules)
    {
        $schedules['a2wl_5_mins'] = array('interval' => 5 * 60, 'display' => esc_html__('Every 5 Minutes', 'ali2woo'));
        $schedules['a2wl_15_mins'] = array('interval' => 15 * 60, 'display' => esc_html__('Every 15 Minutes', 'ali2woo'));
        return $schedules;
    }

    public function init()
    {
        add_action('a2wl_set_setting_email_alerts', array($this, 'toggle_email_alerts'), 10, 3);

        add_action('a2wl_set_setting_auto_update', array($this, 'togle_auto_update'), 10, 3);

        add_action('a2wl_set_setting_review_status', array($this, 'togle_update_reviews'), 10, 3);

        if (!wp_next_scheduled('a2wl_synch_event_check')) {
            wp_schedule_event(time(), 'a2wl_5_mins', 'a2wl_synch_event_check');
        }
    }

    public function synch_event_check()
    {
        // check: is a2wl_update_products_event, update_reviews_event exist. if no, create it.

        if (!wp_next_scheduled('a2wl_update_products_event') && get_setting('auto_update')) {
            $this->schedule_event();
        }

        if (!wp_next_scheduled('a2wl_update_reviews_event') && get_setting('load_review') && get_setting('review_status')) {
            $this->schedule_reviews_event();
        }

        if (!wp_next_scheduled('a2wl_email_alerts_event') && get_setting('auto_update') && get_setting('email_alerts')) {
            $this->schedule_email_alerts_event();
        }
    }

    public function install()
    {

        $this->unschedule_event();
        if (get_setting('auto_update')) {
            $this->schedule_event();
        }

        $this->unschedule_email_alerts_event();
        if (get_setting('auto_update') && get_setting('email_alerts')) {
            $this->schedule_email_alerts_event();
        }

        $this->unschedule_reviews_event();
        if (get_setting('load_review') && get_setting('review_status')) {
            $this->schedule_reviews_event();
        }

        // reset a2wl_synch_event_check
        wp_clear_scheduled_hook('a2wl_synch_event_check');
    }

    public function uninstall()
    {
        $this->unschedule_event();
        $this->schedule_email_alerts_event();
        $this->unschedule_reviews_event();

        // reset a2wl_synch_event_check
        wp_clear_scheduled_hook('a2wl_synch_event_check');
    }

    public function toggle_email_alerts($old_value, $value, $option)
    {
        if ($old_value !== $value) {
            $this->unschedule_email_alerts_event();
            if ($value) {
                $this->schedule_email_alerts_event();
            }
        }
    }

    public function togle_auto_update($old_value, $value, $option)
    {
        if ($old_value !== $value) {
            $this->unschedule_event();
            if ($value) {
                $this->schedule_event();
            }
        }
    }

    public function togle_update_reviews($old_value, $value, $option)
    {
        if ($old_value !== $value) {
            $this->unschedule_reviews_event();
            if ($value) {
                $this->schedule_reviews_event();
            }
        }

    }

    // Cron auto update event
    public function update_products_event(): void
    {
        if (!get_setting('auto_update') || $this->is_process_running('a2wl_update_products_event')) {
            return;
        }

        $this->lock_process('a2wl_update_products_event');

        a2wl_init_error_handler();
        try {
            $update_per_schedule = apply_filters('a2wl_update_per_schedule',
                a2wl_check_defined('A2WL_UPDATE_PER_SCHEDULE') ? intval(A2WL_UPDATE_PER_SCHEDULE) : $this->update_per_schedule
            );

            $update_per_request = apply_filters('a2wl_update_per_request',
                a2wl_check_defined('A2WL_UPDATE_PER_REQUEST') ? intval(A2WL_UPDATE_PER_REQUEST) : $this->update_per_request
            );

            $update_period_delay = apply_filters('a2wl_update_period_delay',
                a2wl_check_defined('A2WL_UPDATE_PERIOD_DELAY') ? intval(A2WL_UPDATE_PERIOD_DELAY) : $this->update_period_delay
            );

            $product_ids = $this->WoocommerceModel->get_sorted_products_ids(
                "_a2w_last_update",
                $update_per_schedule,
                ['value' => time() - $update_period_delay, 'compare' => '<'],
                get_setting('untrash_product')
            );

            $on_price_changes = get_setting('on_price_changes');
            $on_stock_changes = get_setting('on_stock_changes');

            $product_map = array();
            foreach ($product_ids as $product_id) {
                try {
                    $product = $this->WoocommerceService->getProduct($product_id);
                } catch (RepositoryException|ServiceException $Exception) {
                    continue;
                }

                if (!$product['disable_sync']) {
                    $product['disable_var_price_change'] = $product['disable_var_price_change'] || $on_price_changes !== "update";
                    $product['disable_var_quantity_change'] = $product['disable_var_quantity_change'] || $on_stock_changes !== "update";
                    $product_map[strval($product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID])] = $product;
                } else {
                    // update meta for skipped products
                    update_post_meta($product['post_id'], '_a2w_last_update', time());
                }

                unset($product);
            }

            while ($product_map) {
                $tmp_product_map = array_slice($product_map, 0, $update_per_request, true);
                $product_map = array_diff_key($product_map, $tmp_product_map);

                if (count($tmp_product_map) > 0) {
                    $result = $this->ProductService->synchronizeProducts($tmp_product_map);

                    if ($result['state'] !== 'error') {
                        foreach ($result['products'] as $product) {
                            if (!empty($tmp_product_map[$product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID]])) {
                                try {
                                    $product = array_replace_recursive($tmp_product_map[strval($product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID])], $product);
                                    $product = $this->PriceFormulaService->applyFormula($product);
                                    $this->WoocommerceModel->upd_product($product['post_id'], $product);
                                    if ($result['state'] !== 'ok') {
                                        $errorLogMessage = sprintf(
                                            "Automatically synced product (ID: %d) at %s - failed: %s!",
                                            $product['post_id'], date("j, Y, g:i a"), $result['error']
                                        );
                                        a2wl_error_log($errorLogMessage);
                                    } else {
                                        $infoLogMessage = sprintf(
                                            "Automatically synced product (ID: %d) at %s - success!",
                                            $product['post_id'],
                                            date("j, Y, g:i a")
                                        );
                                        a2wl_info_log($infoLogMessage);
                                    }
                                    unset($tmp_product_map[$product[ImportedProductService::FIELD_EXTERNAL_PRODUCT_ID]]);
                                } catch (Throwable $e) {
                                    a2wl_print_throwable($e);
                                }
                            }
                        }

                        delete_transient('_a2w_daily_limits_warning');
                    } else {
                        // update daily limit warning
                        $limitIsReached = isset($result['error_code']) &&
                            $result['error_code'] == 5001 &&
                            isset($result['time_left']);
                        if ($limitIsReached) {
                            set_transient(
                                '_a2w_daily_limits_warning',
                                [
                                    'limit' => $result['call_limit'],
                                    'until' => time() + $result['time_left']
                                ],
                                time() + $result['time_left']
                            );
                        } else {
                            a2wl_error_log($result['message']);
                        }
                    }

                    unset($result);
                }
            }
        } catch (Throwable $e) {
            a2wl_print_throwable($e);
        }

        $this->unlock_process('a2wl_update_products_event');

        if (get_setting('auto_update')) {
            $this->schedule_event();
        } else {
            $this->unschedule_event();
        }
    }

    public function email_alerts_event()
    {

        if (!get_setting('auto_update') || !get_setting('email_alerts') || $this->is_process_running('a2wl_email_alerts_event')) {
            return;
        }

        $this->lock_process('a2wl_email_alerts_event');

        a2wl_init_error_handler();
        try {
            $product_change_model = new ProductChange();

            $to = get_setting('email_alerts_email');

            if ($to) {

                $items = $product_change_model->get_all();
                //we update data of product_change_model in the add_variation method in AliNext_Lite\Woocommerce
                //the collision of these two events is almost unreal, therefore we neglect it
                $product_change_model->clear_all();

                if ($items) {

                    $items_per_email = 100;
                    $chunks = array_chunk($items, $items_per_email, true);

                    a2wl_info_log('Total changes: ' . count($items) . ', total emails: ' . count($chunks));

                    foreach ($chunks as $chunk_items) {
                        $result = $this->send_email_alert($to, $chunk_items);
                    }

                }

            }

        } catch (Throwable $e) {
            a2wl_print_throwable($e);
        }

        $this->unlock_process('a2wl_email_alerts_event');

        if (get_setting('auto_update') && get_setting('email_alerts')) {
            $this->schedule_email_alerts_event();
        } else {
            $this->unschedule_email_alerts_event();
        }

    }

    private function send_email_alert($to, $items)
    {

        $items = $this->format_items_for_email_alert($items);

        $this->model_put("email_heading", esc_html__('AliNext (Lite version) report: Changes in your products', 'ali2woo'));
        $this->model_put("email_subheading", esc_html__('Changes occurred in the last half-hour in your store', 'ali2woo'));
        $this->model_put("items", $items);
        $this->model_put("email_footer_text", esc_html__('The report is generated by the Email alerts module in AliNext (Lite version) at', 'ali2woo') . ' ' . gmdate("F j, Y, g:i a"));

        ob_start();
        $this->include_view(
            array("emails/email-header.php", "emails/product-changes.php", "emails/email-footer.php"));

        $message = ob_get_clean();

        $result = wc_mail($to, esc_html__('AliNext (Lite version) report: Changes in your products', 'ali2woo'), $message);
        a2wl_info_log('Email with product changes: ' . ($result ? ' is sent' : ' isn`t sent'));

        return $result;

    }

    private function format_items_for_email_alert($items)
    {

        $formatted_items = array();

        foreach ($items as $product_id => $item) {

            $product = wc_get_product($product_id);

            $formatted_items[$product_id] = $item;

            $formatted_items[$product_id]['image-src'] = $product->get_image();
            $formatted_items[$product_id]['title'] = $product->get_formatted_name();
            $formatted_items[$product_id]['url'] = get_permalink($product_id);
            $formatted_items[$product_id]['original_url'] = get_post_meta($product_id, '_a2w_product_url', true);

        }

        return $formatted_items;

    }

    public function update_reviews_event(): void
    {
        if (!get_setting('load_review') || !get_setting('review_status')
            || $this->is_process_running('a2wl_update_reviews_event')) {
            return;
        }

        $this->lock_process('a2wl_update_reviews_event');

        a2wl_init_error_handler();
        try {
            $this->ProductReviewsService->loadReviewsForOldestUpdatedProducts();
        } catch (Throwable $e) {
            a2wl_print_throwable($e);
        }

        $this->unlock_process('a2wl_update_reviews_event');

        if (get_setting('load_review') && get_setting('review_status')) {
            $this->schedule_reviews_event();
        } else {
            $this->unschedule_reviews_event();
        }
    }

    private function schedule_event()
    {
        if (!($timestamp = wp_next_scheduled('a2wl_update_products_event'))) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS * 5, 'a2wl_update_products_event');
        }
    }

    private function unschedule_event()
    {
        wp_clear_scheduled_hook('a2wl_update_products_event');
    }

    private function schedule_email_alerts_event()
    {
        if (!($timestamp = wp_next_scheduled('a2wl_email_alerts_event'))) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS * 30, 'a2wl_email_alerts_event');
        }
    }

    private function unschedule_email_alerts_event()
    {
        wp_clear_scheduled_hook('a2wl_email_alerts_event');
    }

    private function schedule_reviews_event()
    {
        if (!($timestamp = wp_next_scheduled('a2wl_update_reviews_event'))) {
            wp_schedule_single_event(time() + MINUTE_IN_SECONDS * 30, 'a2wl_update_reviews_event');
        }
    }

    private function unschedule_reviews_event()
    {
        wp_clear_scheduled_hook('a2wl_update_reviews_event');
    }

    protected function is_process_running($process)
    {
        if (get_site_transient($process . '_process_lock')) {
            return true;
        }

        return false;
    }

    protected function lock_process($process)
    {
        set_site_transient($process . '_process_lock', microtime(), MINUTE_IN_SECONDS * 2);
    }

    protected function unlock_process($process)
    {
        delete_site_transient($process . '_process_lock');
    }

}
