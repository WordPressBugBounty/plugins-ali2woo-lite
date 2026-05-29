<?php
/* * class
 * Description of AdminMenuService
 *
 * @author Ali2Woo Team
 *
 */
namespace AliNext_Lite;;
class AdminMenuService
{
    public const MENU_TYPE_STANDARD = 0;
    public const MENU_TYPE_LINK_ONLY = 1;
    public const MENU_TYPE_HIDDEN_PAGE = 2;

    public function registerPage(
        AbstractModernAdminPage $Page, int $menuType = 0, int $priority = 10
    ): void {

        add_action('a2wl_init_admin_menu', function() use ($Page, $menuType) {
            switch ($menuType) {
                case self::MENU_TYPE_STANDARD:
                    add_submenu_page(
                        'ali2woo',
                        $Page->getPageTitle(),
                        $Page->getMenuTitle(),
                        $Page->getCapability(),
                        $Page->getSlug(),
                        [$Page, 'render']
                    );
                    break;

                case self::MENU_TYPE_LINK_ONLY:
                    add_submenu_page(
                        'ali2woo',
                        $Page->getPageTitle(),
                        $Page->getMenuTitle(),
                        $Page->getCapability(),
                        $Page->getSlug()
                    );
                    break;

                case self::MENU_TYPE_HIDDEN_PAGE:
                    add_menu_page(
                        $Page->getPageTitle(),
                        '',
                        $Page->getCapability(),
                        $Page->getSlug(),
                        [$Page, 'render'],
                        '',
                        99
                    );
                    break;
            }

            if ($menuType === self::MENU_TYPE_HIDDEN_PAGE) {
                remove_menu_page($Page->getSlug());
            }
        }, $priority);

    }
}
