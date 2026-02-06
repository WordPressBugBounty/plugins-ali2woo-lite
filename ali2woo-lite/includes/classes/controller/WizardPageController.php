<?php

/**
 * Description of WizardPageController
 *
 * @author Ali2Woo Team
 * 
 * @autoload: a2wl_admin_init 
 */

namespace AliNext_Lite;;

use Pages;

class WizardPageController extends AbstractAdminPage {

    public const WIZARD_ACTIVATION_KEY = 'a2wl_show_wizard_on_activation';

    public function __construct(
        protected WizardService $wizardService,
    ) {
        parent::__construct(
            Pages::getLabel(Pages::WIZARD),
            Pages::getLabel(Pages::WIZARD),
            Capability::pluginAccess(),
            Pages::WIZARD,
            30,
            2
        );

        $this->showNotification();
        $this->showWizardOnActivation();
    }


    public function render($params = []): void
    {
        if (!empty($_POST)) {
            check_admin_referer(self::PAGE_NONCE_ACTION, self::NONCE);
        }

        if (!PageGuardHelper::canAccessPage(Pages::WIZARD)) {
            wp_die($this->getErrorTextNoPermissions());
        }

        $errors = [];
        if (isset($_POST['wizard_form'])) {
            $errors = $this->wizardService->handle($_POST);
            $redirect = add_query_arg('setup_wizard', 'success', admin_url('admin.php?page=a2wl_dashboard'));
            wp_redirect($redirect);
            exit;
        }

        $model = $this->wizardService->collectModel();
        $model['errors'] = $errors;
        $model['close_link'] = admin_url('admin.php?page=a2wl_dashboard');

        foreach ($model as $key => $value) {
            $this->model_put($key, $value);
        }

        $this->include_view("wizard.php");
    }

    protected function showWizardOnActivation(): void
    {
        if (get_option(self::WIZARD_ACTIVATION_KEY)) {
            delete_option(self::WIZARD_ACTIVATION_KEY);

            wp_safe_redirect(admin_url('admin.php?page=a2wl_wizard'));
            exit;
        }
    }

    protected function showNotification(): void
    {
        if (isset($_GET['setup_wizard'])) {
            $wizardAlerts[] = PermanentAlert::build(
                esc_html__('Setup Wizard has applied preferred settings!', 'ali2woo'),
                PermanentAlert::TYPE_SUCCESS
            );

            add_filter('a2wl_get_permanent_alerts', function (array $alerts) use ($wizardAlerts) {
                return array_merge($alerts, $wizardAlerts);
            });
        }
    }
}
