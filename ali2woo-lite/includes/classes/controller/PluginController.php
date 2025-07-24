<?php

/**
 * Description of PluginController
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

class PluginController extends AbstractController
{
    public function __construct() {
        parent::__construct();

        add_filter('plugin_row_meta', [$this, 'addCustomPluginRowMeta'], 10, 2);
    }

    public function addCustomPluginRowMeta($plugin_meta, $plugin_file)
    {
        if (plugin_basename($plugin_file) === A2WL()->plugin_name) {
            $custom_links[] = '<a href="' . admin_url('admin.php?page=' . Pages::SETTINGS) . '">' .
                __('Settings', 'ali2woo') . '</a>';

            if (A2WL()->isAnPlugin() && A2WL()->isFreePlugin()) {
                $custom_links[] =
                    '<a href="https://ali2woo.com/pricing/?utm_source=lite&utm_medium=plugin_row_meta&utm_campaign=alinext-lite" target="_blank">Get the Pro Version</a>';
            }

            $plugin_meta = array_merge($custom_links, $plugin_meta);
        }

        return $plugin_meta;
    }
}
