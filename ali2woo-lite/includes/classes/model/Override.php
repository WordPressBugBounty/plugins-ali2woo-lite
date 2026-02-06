<?php
/**
 * Description of Override
 *
 * @author Ali2Woo Team
 */
namespace AliNext_Lite;;

use Automattic\WooCommerce\Utilities\OrderUtil;

class Override
{

    public function __construct()
    {}

    public function has_override($product_id): bool
    {
        global $wpdb;

        if ($product_id) {
            return !!$wpdb->get_var("SELECT 1 FROM $wpdb->options WHERE option_name like '%a2wl_product%' AND option_value like '%\"override_product_id\";i:" . intval($product_id) . "%'");
        } else {
            return false;
        }
    }

    public function override($product_id, $external_id, $change_supplier = false, $override_images = false, $override_title_description = false, $variations = array())
    {
        global $wpdb;

        $result = array("state" => "error", "message" => "Product not found.");

        if ($this->has_override($product_id)) {
            $result = array(
                "state" => "error",
                "message" => esc_html__("You've already selected to override this product. Check your import list and confirm the override to continue.", "ali2woo"),
            );
        } else {
            $override_product = $wpdb->get_row($wpdb->prepare("SELECT p.ID, p.post_title FROM $wpdb->posts p WHERE p.ID=%d", $product_id), ARRAY_A);
            if ($override_product) {
                $product_import_model = new ProductImport();
                $product = $product_import_model->get_product($external_id);
                if ($product) {
                    $product['override_product_id'] = intval($product_id);
                    $product['override_product_title'] = $override_product['post_title'];
                    $product['override_supplier'] = $change_supplier;
                    $product['override_images'] = $override_images;
                    $product['override_title_description'] = $override_title_description;
                    $product['override_variations'] = $variations;

                    $product_import_model->upd_product($product);

                    $result = array(
                        'state' => 'ok',
                        'product_id' => $product_id, 'external_id' => $external_id,
                        'html' => $this->override_message($product_id, $product['override_product_title']),
                    );
                }
            }
        }

        return $result;
    }

    public function find_orders($product_id)
    {
        global $wpdb;

        // Define the default WooCommerce statuses that mean an order is fulfilled
        $fulfilled_statuses = ['wc-completed', 'wc-cancelled', 'wc-refunded'];

        // Get the custom "delivered" status from plugin settings
        $delivered_order_status = get_setting('delivered_order_status');

        if ($delivered_order_status && !in_array($delivered_order_status, $fulfilled_statuses, true)) {
            $fulfilled_statuses[] = $delivered_order_status;
        }

        $statuses_sql = "'" . implode("','", array_map('esc_sql', $fulfilled_statuses)) . "'";

        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $query = "
            SELECT variation_id, max(variation_attributes) as variation_attributes, max(thumbnail) as thumbnail, count(order_id) as cnt
            FROM (
                SELECT wi.order_id as order_id, wim2.meta_value as variation_id,
                       group_concat(t1.name SEPARATOR '#') as variation_attributes,
                       max(p2.guid) as thumbnail
                FROM {$wpdb->prefix}woocommerce_order_items wi
                INNER JOIN {$wpdb->prefix}wc_orders p1
                    ON (p1.ID=wi.order_id AND NOT p1.status IN ($statuses_sql))
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta wim1
                    ON (wi.order_item_id=wim1.order_item_id AND wim1.meta_key='_product_id' AND wim1.meta_value=%d)
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta wim2
                    ON (wi.order_item_id=wim2.order_item_id AND wim2.meta_key='_variation_id')
                INNER JOIN {$wpdb->postmeta} pm1
                    ON (pm1.post_id=wim2.meta_value AND pm1.meta_key LIKE 'attribute_%')
                LEFT JOIN {$wpdb->postmeta} pm2
                    ON (pm2.post_id=wim2.meta_value AND pm2.meta_key LIKE '_thumbnail_id')
                LEFT JOIN {$wpdb->posts} p2
                    ON (pm2.meta_value=p2.ID)
                INNER JOIN {$wpdb->term_taxonomy} tt1
                    ON (tt1.taxonomy=SUBSTRING(pm1.meta_key, 11))
                INNER JOIN {$wpdb->terms} t1
                    ON (t1.term_id=tt1.term_id AND t1.slug=pm1.meta_value)
                GROUP BY order_id, variation_id
            ) as q
            GROUP BY variation_id";
        } else {
            // Otherwise, fall back to using wp_posts table for order statuses
            $query = "
            SELECT variation_id, max(variation_attributes) as variation_attributes, max(thumbnail) as thumbnail, count(order_id) as cnt
            FROM (
                SELECT wi.order_id as order_id, wim2.meta_value as variation_id,
                       group_concat(t1.name SEPARATOR '#') as variation_attributes,
                       max(p2.guid) as thumbnail
                FROM {$wpdb->prefix}woocommerce_order_items wi
                INNER JOIN {$wpdb->posts} p1
                    ON (p1.ID=wi.order_id AND NOT p1.post_status IN ($statuses_sql))
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta wim1
                    ON (wi.order_item_id=wim1.order_item_id AND wim1.meta_key='_product_id' AND wim1.meta_value=%d)
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta wim2
                    ON (wi.order_item_id=wim2.order_item_id AND wim2.meta_key='_variation_id')
                INNER JOIN {$wpdb->postmeta} pm1
                    ON (pm1.post_id=wim2.meta_value AND pm1.meta_key LIKE 'attribute_%')
                LEFT JOIN {$wpdb->postmeta} pm2
                    ON (pm2.post_id=wim2.meta_value AND pm2.meta_key LIKE '_thumbnail_id')
                LEFT JOIN {$wpdb->posts} p2
                    ON (pm2.meta_value=p2.ID)
                INNER JOIN {$wpdb->term_taxonomy} tt1
                    ON (tt1.taxonomy=SUBSTRING(pm1.meta_key, 11))
                INNER JOIN {$wpdb->terms} t1
                    ON (t1.term_id=tt1.term_id AND t1.slug=pm1.meta_value)
                GROUP BY order_id, variation_id
            ) as q
            GROUP BY variation_id";
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $query,
                $product_id
            ),
            ARRAY_A
        );
    }



    /**
     * Update order items when product variations are overridden.
     *
     * @param int   $product_id
     * @param array $override_variations
     *
     * NOTE:
     * Before this function call we have already updated the original product with the new override-product in the database.
     * Here we check if the product still has variants, and if so, update related order items.
     * BUT: if the override-product is a simple product and the original was variable,
     * then the query below will not return any results and related order items will not be updated.
     * This means order items may still reference old '_variation_id' values that no longer exist.
     * Perhaps this is acceptable, but for correct order fulfillment each order item should have valid data.
     * In the future, if users report issues, we may need to extend this logic:
     * - detect when the override-product is simple,
     * - and remove '_variation_id' from each order item meta to avoid broken references.
     */
    public static function updateOrderItemsOnOverride($product_id, $override_variations): void
    {
        global $wpdb;

        // Build mapping external_variation_id > variation_id
        $variations_to_override = [];
        foreach ($override_variations as $v) {
            $variations_to_override[$v['external_variation_id']] = $v['variation_id'];
        }

        if (empty($variations_to_override)) {
            return;
        }

        $in_data = implode(",", array_map(function ($v) {
            global $wpdb;
            return "'" . $wpdb->_real_escape($v) . "'";
        }, array_keys($variations_to_override)));

        // Build fulfilled statuses SQL string (includes custom Delivered if set)
        $fulfilled_statuses = ['wc-completed', 'wc-cancelled', 'wc-refunded'];
        $delivered_order_status = get_setting('delivered_order_status');
        if ($delivered_order_status && !in_array($delivered_order_status, $fulfilled_statuses, true)) {
            $fulfilled_statuses[] = $delivered_order_status;
        }
        $statuses_sql = "'" . implode("','", array_map('esc_sql', $fulfilled_statuses)) . "'";

        // Find new variations
        $new_variations_query = "SELECT pm.post_id as variation_id, pm.meta_value as external_variation_id
                             FROM {$wpdb->postmeta} pm
                             INNER JOIN {$wpdb->posts} p on (p.ID=pm.post_id)
                             WHERE p.post_parent=%d and pm.meta_key='external_variation_id' and pm.meta_value in ($in_data)";

        $new_variations = $wpdb->get_results(
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->prepare($new_variations_query, $product_id), ARRAY_A
        );

        foreach ($new_variations as $v) {
            if (isset($variations_to_override[$v['external_variation_id']])) {
                if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                    $update_query = "UPDATE {$wpdb->prefix}woocommerce_order_itemmeta oim
                                 INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON (oi.order_item_id=oim.order_item_id)
                                 INNER JOIN {$wpdb->prefix}wc_orders p ON (p.ID=oi.order_id)
                                 SET oim.meta_value=%d
                                 WHERE oim.meta_key='_variation_id' AND oim.meta_value=%d
                                 AND NOT p.status IN ($statuses_sql)";
                } else {
                    $update_query = "UPDATE {$wpdb->prefix}woocommerce_order_itemmeta oim
                                 INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON (oi.order_item_id=oim.order_item_id)
                                 INNER JOIN {$wpdb->posts} p ON (p.ID=oi.order_id)
                                 SET oim.meta_value=%d
                                 WHERE oim.meta_key='_variation_id' AND oim.meta_value=%d
                                 AND NOT p.post_status IN ($statuses_sql)";
                }

                $wpdb->query(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $wpdb->prepare(
                        $update_query,
                        $v['variation_id'],
                        $variations_to_override[$v['external_variation_id']]
                    )
                );
            }
        }
    }

    public function find_variations($product_id)
    {
        global $wpdb;
        $query = "SELECT p.id AS variation_id, group_concat(t1.name SEPARATOR '#') AS variation_attributes, " .
                 "max(p2.guid) thumbnail FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm1 " .
                 "ON (pm1.post_id=p.ID AND pm1.meta_key like'attribute_%') LEFT JOIN {$wpdb->postmeta} pm2 " .
                 "ON (pm2.post_id=p.ID AND pm2.meta_key like'_thumbnail_id') LEFT JOIN {$wpdb->posts} p2 " .
                 "ON (pm2.meta_value=p2.ID) INNER JOIN {$wpdb->term_taxonomy} tt1 " .
                 "ON (tt1.taxonomy=substring(pm1.meta_key, 11)) INNER JOIN {$wpdb->terms} t1 " .
                 "ON (t1.term_id=tt1.term_id and t1.slug=pm1.meta_value) " .
                 "WHERE p.post_type='product_variation' AND p.post_parent=%d GROUP BY variation_id";

        return $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $query,
                $product_id
            ),
            ARRAY_A
        );
    }

    public function cancel_override($external_id)
    {
        $result = array("state" => "error", "message" => "Product not found.");

        $product_import_model = new ProductImport();

        $product = $product_import_model->get_product($external_id);

        if ($product) {
            unset($product['override_product_id']);
            unset($product['override_product_title']);
            unset($product['override_supplier']);
            unset($product['override_images']);
            unset($product['override_title_description']);
            unset($product['override_variations']);

            $product_import_model->upd_product($product, false);

            $result = array('state' => 'ok');
        }

        return $result;
    }

    public function override_message(int $product_id, string $product_title): string
    {
        $url = esc_url(admin_url('post.php?post=' . $product_id . '&action=edit'));
        $title = esc_html($product_title);

        /* translators: %s is product title */
        $msg_text = sprintf(
            __('This product will override %s. Click "Override" to proceed.', 'ali2woo'),
            $title
        );

        $msg = str_replace($title, sprintf('<a href="%s">%s</a>', $url, $title), $msg_text);

        $btn_text = esc_html__('Cancel Override', 'ali2woo');
        $btn = sprintf(
            '<button class="btn btn-default cancel-override" type="button">%s</button>',
            $btn_text
        );

        return sprintf(
            '<div><div style="padding-bottom:8px;">%s</div>%s</div>', $msg, $btn
        );
    }

}
