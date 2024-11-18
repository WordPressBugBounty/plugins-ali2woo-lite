<?php

/**
 * Description of TipOfDayService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class TipOfDayService
{
    private TipOfDayFactory $TipOfDayFactory;
    private TipOfDayRepository $TipOfDayRepository;
    private Settings $SettingsService;

    public function __construct(
        TipOfDayFactory $TipOfDayFactory,
        TipOfDayRepository $TipOfDayRepository,
        Settings $SettingsService
    ) {
        $this->TipOfDayFactory = $TipOfDayFactory;
        $this->TipOfDayRepository = $TipOfDayRepository;
        $this->SettingsService = $SettingsService;
    }

    public function getNextTip(): ?TipOfDay
    {
        if (!$this->shouldDisplayToday()) {
            return null;
        }

        return $this->TipOfDayRepository->getFirstShown();
    }

    public function hideTip(TipOfDay $tipOfDay): void
    {
        $tipOfDay->setIsHidden(true);
        $this->TipOfDayRepository->save($tipOfDay);
        $this->SettingsService->set(Settings::SETTING_TIP_OF_DAY_LAST_DATE, gmdate("Y-m-j H:i:s"));
        $this->SettingsService->commit();
    }

    public function shouldDisplayToday(): bool
    {
        if (a2wl_check_defined("A2WL_DEMO_MODE")) {
            return false;
        }

        $lastDate = $this->SettingsService->get(Settings::SETTING_TIP_OF_DAY_LAST_DATE, null);

        if (is_null($lastDate)) {
            return true;
        }

        if (strtotime('-1 day') < strtotime($lastDate)) {
           return false;
        }

        return true;
    }
}
