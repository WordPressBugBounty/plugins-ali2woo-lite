<?php

/**
 * Description of MigrateService
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

class MigrateService
{
    private ProductShippingDataRepository $ProductShippingDataRepository;

    public function __construct(
        ProductShippingDataRepository $ProductShippingDataRepository
    ) {
        $this->ProductShippingDataRepository = $ProductShippingDataRepository;

        $this->migrate();
    }

    public function migrate(): void
    {
        $cur_version = get_option('a2wl_db_version', '');
        if (version_compare($cur_version, "3.0.8", '<')) {
            $this->migrate_to_308();
        }

        if (version_compare($cur_version, "3.5.2", '<')) {
            $this->migrate_to_352();
        }

        if (version_compare($cur_version, "3.5.4", '<')) {
            $this->migrate_to_354();
        }

        if (version_compare($cur_version, "3.5.9", '<')) {
            $this->migrate_to_359();
        }

        if (version_compare($cur_version, A2WL()->version, '<')) {
            update_option('a2wl_db_version', A2WL()->version, 'no');
        }
    }

    private function migrate_to_308(): void
    {
        a2wl_error_log('migrate to 3.0.8');
        if (class_exists('AliNext_Lite\ProductShippingMeta')) {
            ProductShippingMeta::clear_in_all_product();;
        }
    }

    private function migrate_to_352(): void
    {
        a2wl_error_log('migrate to 3.5.2');
        $this->ProductShippingDataRepository->clear();
    }

    private function migrate_to_354(): void
    {
        a2wl_error_log('migrate to 3.5.4');
        $this->ProductShippingDataRepository->clear();
    }

    public function migrate_to_359(): void
    {
        a2wl_error_log('migrate 3.5.9');
        $cap = Capability::pluginAccess();
        $allowManager = false;

        if (A2WL()->isAnPlugin()) {
            $allowManager = get_setting(Settings::SETTING_ALLOW_SHOP_MANAGER);
        }

        foreach (PageGuardHelper::getAllRoles() as $roleKey) {
            $role = get_role($roleKey);
            if (!$role) {
                continue;
            }

            if ($roleKey === PageGuardHelper::ROLE_ADMIN || $allowManager) {
                $role->add_cap($cap);
            } else {
                $role->remove_cap($cap);
            }
        }
    }
}
