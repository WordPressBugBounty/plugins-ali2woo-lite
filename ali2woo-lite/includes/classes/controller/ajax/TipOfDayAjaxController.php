<?php

/**
 * Description of TipOfDayAjaxController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 *
 * @ajax: true
 */
// phpcs:ignoreFile WordPress.Security.EscapeOutput.OutputNotEscaped
namespace AliNext_Lite;;

use Exception;
use Pages;

class TipOfDayAjaxController extends AbstractController
{
    public const PARAM_ID = 'id';
    public const AJAX_METHOD_TIP_OF_DAY = 'a2wl_tip_of_day_hide';

    private TipOfDayService $TipOfDayService;
    private TipOfDayRepository $TipOfDayRepository;


    public function __construct(
        TipOfDayService $TipOfDayService,
        TipOfDayRepository $TipOfDayRepository,
    ) {
        parent::__construct();

        $this->TipOfDayService = $TipOfDayService;
        $this->TipOfDayRepository = $TipOfDayRepository;

        add_action(sprintf('wp_ajax_%s', self::AJAX_METHOD_TIP_OF_DAY), [$this, 'ajaxHide']);
    }

    public function ajaxHide(): void
    {
        $this->verifyNonceAjax();

        if (!PageGuardHelper::canAccessPage(Pages::SETTINGS)) {
            $result = ResultBuilder::buildError($this->getErrorTextNoPermissions());
            echo wp_json_encode($result);
            wp_die();
        }

        $id = intval($_POST[self::PARAM_ID]);

        a2wl_init_error_handler();

        $result = ResultBuilder::buildOk();

        try {
            $TipOfDay = $this->TipOfDayRepository->getOne($id);

            if (!$TipOfDay) {
                throw new RepositoryException(
                    _x( "Tip of the day with given ID does`t exist", 'error text', 'ali2woo')
                );
            }
            $this->TipOfDayService->hideTip($TipOfDay);

            restore_error_handler();
        } catch (RepositoryException $RepositoryException) {
            $result = ResultBuilder::buildError($RepositoryException->getMessage());
        } catch (Exception $Exception)  {
            a2wl_print_throwable($Exception);
            $result = ResultBuilder::buildError($Exception->getMessage());
        }

        echo wp_json_encode($result);
        wp_die();
    }

}
