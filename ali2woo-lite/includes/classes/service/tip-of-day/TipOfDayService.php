<?php

namespace AliNext_Lite;;

use Throwable;

class TipOfDayService
{
    public const TIP_START_DELAY_DAYS = 1;

    private TipOfDayRepository $TipOfDayRepository;
    private Settings $SettingsService;
    private TipOfDayProgress $TipOfDayProgress;
    private PurchaseCodeInfoService $purchaseCodeInfoService;

    public function __construct(
        TipOfDayRepository $TipOfDayRepository,
        Settings $SettingsService,
        TipOfDayProgress $TipOfDayProgress,
        PurchaseCodeInfoService $purchaseCodeInfoService
    ) {
        $this->TipOfDayRepository = $TipOfDayRepository;
        $this->SettingsService = $SettingsService;
        $this->TipOfDayProgress = $TipOfDayProgress;
        $this->purchaseCodeInfoService = $purchaseCodeInfoService;
    }

    public function getNextTip(): ?TipOfDay
    {
        if ($this->isDisabled()) {
            return null;
        }

        if (!$this->shouldDisplayToday()) {
            return null;
        }

        $allTips = $this->TipOfDayRepository->findAll();
        $allTips = $this->filterByPlan($allTips);
        $seenIds = $this->TipOfDayProgress->getSeenIds();

        foreach ($allTips as $tip) {
            if ($tip->isHidden()) {
                continue;
            }
            if (!in_array((string) $tip->getId(), $seenIds, true)) {
                $this->saveLastDisplayDate();
                return $tip;
            }
        }

        return null;
    }

    public function markTipAsSeen(TipOfDay $tipOfDay): void
    {
        $this->TipOfDayProgress->addSeenId((string) $tipOfDay->getId());
    }

    public function disableTips(): void
    {
        $this->SettingsService->set(Settings::SETTINGS_TIP_OF_DAY_DISABLED, true);
        $this->SettingsService->commit();
    }

    public function enableTips(): void
    {
        $this->SettingsService->set(Settings::SETTINGS_TIP_OF_DAY_DISABLED, false);
        $this->SettingsService->commit();
    }

    public function isDisabled(): bool
    {
        return (bool) $this->SettingsService->get(Settings::SETTINGS_TIP_OF_DAY_DISABLED, false);
    }

    public function hideTip(TipOfDay $tipOfDay): void
    {
        $tipOfDay->setIsHidden(true);
        $this->TipOfDayRepository->save($tipOfDay);
        $this->markTipAsSeen($tipOfDay);
    }

    public function shouldDisplayToday(): bool
    {
        if (a2wl_check_defined("A2WL_DEMO_MODE")) {
            return false;
        }

        $activationDate = get_option(Settings::SETTING_PLUGIN_ACTIVATION_DATE);
        if ($activationDate) {
            $delayEnd = $activationDate + self::TIP_START_DELAY_DAYS * DAY_IN_SECONDS;
            if (time() < $delayEnd) {
                return false;
            }
        }

        $interval = (int) $this->SettingsService->get(
            Settings::SETTING_TIP_OF_DAY_INTERVAL,
            86400
        );

        $lastDate = $this->SettingsService->get(
            Settings::SETTING_TIP_OF_DAY_LAST_DATE,
            null
        );

        if (is_null($lastDate)) {
            return true;
        }

        $lastTimestamp = strtotime((string) $lastDate);
        if ($lastTimestamp === false) {
            return true;
        }

        if (time() < $lastTimestamp + $interval) {
            return false;
        }

        return true;
    }

    private function filterByPlan(array $tips): array
    {
        $plan = $this->resolveUserPlan();
        $billingCycle = $this->resolveUserBillingCycle();

        return array_filter($tips, function (TipOfDay $tip) use ($plan, $billingCycle) {
            $tipPlan = $tip->getPlan();

            if ($tipPlan === null) {
                return true;
            }

            $plans = array_map('trim', explode('|', $tipPlan));

            if (in_array('all', $plans, true)) {
                return true;
            }

            if (!in_array($plan, $plans, true)) {
                return false;
            }

            $tipBilling = $tip->getBillingCycle();
            if ($tipBilling !== null && $tipBilling !== $billingCycle) {
                return false;
            }

            return true;
        });
    }

    private function resolveUserPlan(): string
    {
        if (EditionHelper::isLite()) {
            return 'lite';
        }

        try {
            $purchaseCodeInfo = $this->purchaseCodeInfoService->getFromCache();

            return $purchaseCodeInfo->getTariffCode() ?? 'lite';
        } catch (Throwable $e) {
            return 'lite';
        }
    }

    private function resolveUserBillingCycle(): ?string
    {
        //todo: retrieve billing_cycle from PurchaseCodeInfo when available.
        //      add billing_cycle field to PurchaseCodeInfo and parse from API response.
        return 'monthly';
    }

    private function saveLastDisplayDate(): void
    {
        $this->SettingsService->set(Settings::SETTING_TIP_OF_DAY_LAST_DATE, current_time('mysql'));
        $this->SettingsService->commit();
    }
}
