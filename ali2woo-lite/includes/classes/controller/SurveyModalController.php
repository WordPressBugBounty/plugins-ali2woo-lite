<?php

/**
 * Description of SurveyModalController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 * @ajax: true
 */

namespace AliNext_Lite;;

use Pages;

class SurveyModalController extends AbstractController
{
    protected ExitSurveyService $ExitSurveyService;
    protected AssetService $AssetService;

    public function __construct(ExitSurveyService $ExitSurveyService, AssetService $AssetService)
    {
        parent::__construct(A2WL()->plugin_path() . '/view/');

        $this->ExitSurveyService = $ExitSurveyService;
        $this->AssetService = $AssetService;

        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('wp_ajax_a2wl_exit_survey_submit', [$this, 'ajaxExitSurveySubmit']);
    }

    public function assets(): void
    {
        if (user_can(get_current_user_id(), 'manage_options') === false) {
            return;
        }

        $currentScreen = get_current_screen();
        if (isset($currentScreen->id) && 'plugins' === $currentScreen->id) {
            wp_enqueue_style(
                'a2wl-exit-survey',
                A2WL()->plugin_url() . '/assets/css/exit-survey.css',
                [],
                A2WL()->version
            );

            wp_register_script(
                'a2wl-exit-survey',
                A2WL()->plugin_url() . '/assets/js/exit-survey.js',
                ['jquery'],
                A2WL()->version,
                true
            );

            wp_localize_script(
                'a2wl-exit-survey',
                'a2wl_data',
                [
                    'nonce' => wp_create_nonce(self::AJAX_NONCE_ACTION),
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'logo' => $this->AssetService->getLogoUrl('logo-155.png'),
                    'deactivateLinkId' => $this->ExitSurveyService->getDeactivateLinkId(),
                    'lang' => [
                        'title' => _x('We\'re sorry to see you go!', 'survey modal', 'ali2woo'),
                        'subtitle' => _x(
                            'Before you deactivate AliNext (Lite version), please take a moment to share your reason. ' .
                                 'Your feedback helps us improve and make AliNext (Lite version) better for everyone.',
                            'survey modal',
                            'ali2woo'
                        ),
                        'reason_complex' => _x(
                            'The plugin was too complicated to set up or use',
                            'survey modal',
                            'ali2woo'
                        ),
                        'reason_conflict' => _x(
                            'Conflicts with other plugins or my theme',
                            'survey modal',
                            'ali2woo'
                        ),
                        'reason_other' => _x('Other', 'survey modal', 'ali2woo'),
                        'other_placeholder' => _x(
                            'Please provide details...',
                            'survey modal',
                            'ali2woo'
                        ),
                        'contact_title' => _x('May we contact you?', 'survey modal', 'ali2woo'),
                        'contact_text' => _x(
                            'We’re improving AliNext (Lite version) and sometimes ask users for extra feedback. ' .
                                'Would you be open to us reaching out? If so, please leave your email below.',
                            'survey modal',
                            'ali2woo'
                        ),
                        'email_placeholder' => _x('Email address...', 'survey modal', 'ali2woo'),
                        'submit_btn' => _x('Submit & Deactivate', 'survey modal', 'ali2woo'),
                        'skip_btn' => _x('Skip & Deactivate', 'survey modal', 'ali2woo'),
                    ]
                ]
            );

            wp_enqueue_script('a2wl-exit-survey');
        }
    }

    public function ajaxExitSurveySubmit(): void
    {
        check_admin_referer(self::AJAX_NONCE_ACTION, self::NONCE);

        if (!PageGuardHelper::canAccessPage(Pages::ORDER_MANAGEMENT)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $reason        = sanitize_text_field($_POST['reason'] ?? '');
        $other         = sanitize_text_field($_POST['other'] ?? '');
        $contact_email = sanitize_email($_POST['contact_email'] ?? '');

        $dto = ExitSurveyDTO::build($reason, $other, $contact_email);
        $this->ExitSurveyService->send($dto);

        echo wp_json_encode(ResultBuilder::buildOk());
        wp_die();
    }
}
