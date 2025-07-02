<?php
/**
 * Description of BlankConverter
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

use Pages;

class BlankConverter extends AbstractAdminPage
{
    public function __construct()
    {
        if (!apply_filters('a2wl_converter_installed', false)) {
            parent::__construct(
                    Pages::getLabel(Pages::MIGRATION_TOOL),
                    Pages::getLabel(Pages::MIGRATION_TOOL),
                    Capability::pluginAccess(),
                Pages::MIGRATION_TOOL,
                    1000
            );
        }
    }

    public function render($params = []): void
    {
        if (!PageGuardHelper::canAccessPage(Pages::MIGRATION_TOOL)) {
            wp_die($this->getErrorTextNoPermissions());
        }

        ?>
        <h1><?php echo Pages::getLabel(Pages::MIGRATION_TOOL); ?></h1>
        <p>The conversion plugin is not installed.</p>
        <p><a href="#">Download and install plugin</a></p>
        <?php
    }
}
