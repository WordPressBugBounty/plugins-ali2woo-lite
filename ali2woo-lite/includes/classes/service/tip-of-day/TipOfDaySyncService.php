<?php

namespace AliNext_Lite;;

class TipOfDaySyncService
{
    private TipOfDayFactory $TipOfDayFactory;
    private TipOfDayRepository $TipOfDayRepository;
    private TipOfDayProgress $TipOfDayProgress;

    public function __construct(
        TipOfDayFactory $TipOfDayFactory,
        TipOfDayRepository $TipOfDayRepository,
        TipOfDayProgress $TipOfDayProgress
    ) {
        $this->TipOfDayFactory = $TipOfDayFactory;
        $this->TipOfDayRepository = $TipOfDayRepository;
        $this->TipOfDayProgress = $TipOfDayProgress;
    }

    public function syncFromApiData(array $pluginData): void
    {
        if (!isset($pluginData[TipOfDay::FIELD_TIPS_OF_DAY]) || !is_array($pluginData[TipOfDay::FIELD_TIPS_OF_DAY])) {
            return;
        }

        $normalizedTips = [];

        foreach ($pluginData[TipOfDay::FIELD_TIPS_OF_DAY] as $tipData) {
            $normalized = $this->TipOfDayFactory->normalizeFromApiData($tipData);
            $normalizedTips[] = $normalized;
        }

        if (!empty($normalizedTips)) {
            $this->TipOfDayRepository->replaceAll($normalizedTips);
            $this->cleanupStaleTipSeenIds($normalizedTips);
        }
    }

    private function cleanupStaleTipSeenIds(array $normalTips): void
    {
        $newTipIds = array_map(function ($tip) {
            return (string) $tip['id'];
        }, $normalTips);

        $this->TipOfDayProgress->removeStale($newTipIds);
    }
}
