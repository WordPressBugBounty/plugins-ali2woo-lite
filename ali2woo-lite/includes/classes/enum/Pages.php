<?php

/* * class
 * Description of Pages
 *
 * @author Ali2Woo Team
 *
 */

final class Pages {

    public const DASHBOARD = 'a2wl_dashboard';
    public const IMPORT_LIST = 'a2wl_import';
    public const STORE = 'a2wl_store';
    public const TRANSFER = 'a2wl_transfer';
    public const MIGRATION_TOOL = 'a2wl_converter';
    public const SETTINGS = 'a2wl_setting';
    public const ADDONS = 'a2wl_addons';
    public const DEBUG = 'a2wl_debug';
    public const SHIPPING = 'a2wl_shipping';
    public const JSON_API = 'a2wl_json_api';

    public const ORDER_MANAGEMENT = 'a2wl_order';
    public const WIZARD = 'a2wl_wizard';
    public const HELP = 'a2wl_help';

    public static function getLabels(): array
    {

        return [
            self::DASHBOARD => esc_html_x('Search Products', 'page title', 'ali2woo'),
            self::IMPORT_LIST => esc_html_x('Import List', 'page title', 'ali2woo'),
            self::ORDER_MANAGEMENT => esc_html_x('Order Management', 'page title', 'ali2woo'),
            self::STORE => esc_html_x('Search In Store', 'page title', 'ali2woo'),
            self::TRANSFER => esc_html_x('Transfer', 'page title', 'ali2woo'),
            self::MIGRATION_TOOL => esc_html_x('Migration Tool', 'page title', 'ali2woo'),
            self::SETTINGS => esc_html_x('Settings', 'page title', 'ali2woo'),
            self::ADDONS => esc_html_x('Add-ons', 'page title', 'ali2woo'),
            self::DEBUG => esc_html_x('Debug', 'page title', 'ali2woo'),
            self::SHIPPING => esc_html_x('Shipping List', 'page title', 'ali2woo'),
            self::JSON_API => esc_html_x('JSON API', 'page title', 'ali2woo'),
            self::WIZARD => esc_html_x('Wizard', 'page title', 'ali2woo'),
            self::HELP => esc_html_x('Help', 'page title', 'ali2woo'),
        ];
    }

    public static function getLabel(string $slug): string
    {
        $labels = self::getLabels();

        return $labels[$slug] ?? $slug;
    }
}
