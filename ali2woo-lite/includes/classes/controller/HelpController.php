<?php
/**
 * Description of HelpController
 *
 * @author Ali2Woo Team
 * 
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

use Pages;

class HelpController {

    public function __construct() {    
        add_action('a2wl_init_admin_menu', [$this, 'add_submenu_page'], 200);
    }

    public function add_submenu_page($parent_slug): void
    {
        add_submenu_page(
            $parent_slug,
            '',
            Pages::getLabel(Pages::HELP),
            Capability::pluginAccess(),
            'https://help.ali2woo.com/alinext-kb/'
        );
    }
}
