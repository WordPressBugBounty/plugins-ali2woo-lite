<?php

/**
 * Description of WizardPageController
 *
 * @author Ali2Woo Team
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

use Pages;

class WizardPageController extends AbstractModernAdminPage
{
    public const WIZARD_ACTIVATION_KEY = 'a2wl_show_wizard_on_activation';

    private WizardService $WizardService;
    private AdminMenuService $AdminMenuService;

    public function __construct(
        WizardService $WizardService,
        AdminMenuService $AdminMenuService
    ) {
        parent::__construct(
            Pages::getLabel(Pages::WIZARD),
            Pages::getLabel(Pages::WIZARD),
            Capability::pluginAccess(),
            Pages::WIZARD
        );

        $this->WizardService = $WizardService;
        $this->AdminMenuService = $AdminMenuService;

        $this->add_style('a2wl-wizard-style', '/assets/css/pages/wizard.css?=2');

        $AdminMenuService->registerPage($this, AdminMenuService::MENU_TYPE_HIDDEN_PAGE, 30);

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
            $errors = $this->WizardService->handle($_POST);
            $redirect = add_query_arg('setup_wizard', 'success', admin_url('admin.php?page=a2wl_dashboard'));
            wp_redirect($redirect);
            exit;
        }

        $model = $this->WizardService->collectModel();
        $model['errors'] = $errors;
        $model['close_link'] = admin_url('admin.php?page=a2wl_dashboard');

        foreach ($model as $key => $value) {
            $this->model_put($key, $value);
        }

        $this->include_view("wizard.php");
    }

    protected function showWizardOnActivation(): void
    {
        add_action('admin_init', function () {
            if (get_option(self::WIZARD_ACTIVATION_KEY)) {
                delete_option(self::WIZARD_ACTIVATION_KEY);

                wp_safe_redirect(admin_url('admin.php?page=a2wl_wizard'));
                exit;
            }
        });
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
