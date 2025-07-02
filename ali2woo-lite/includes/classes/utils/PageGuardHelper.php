<?php

/**
 * Description of AccessHelper
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class PageGuardHelper
{
    public const ROLE_ADMIN = 'administrator';
    public const ROLE_SHOP_MANAGER = 'shop_manager';

    public static function getAllRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_SHOP_MANAGER,
        ];
    }

    public static function isAdmin(): bool {
        return in_array(self::ROLE_ADMIN, wp_get_current_user()->roles);
    }

    public static function canAccessPage(string $slug): bool
    {
        // Block early if user lacks plugin-level access
        if (!current_user_can(Capability::pluginAccess())) {
            return false;
        }

        if (EditionHelper::isLite()) {
            return true;
        }

        
    }

}
