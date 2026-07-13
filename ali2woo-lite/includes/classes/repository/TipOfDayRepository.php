<?php

namespace AliNext_Lite;;

class TipOfDayRepository
{
    private TipOfDayFactory $TipOfDayFactory;

    public function __construct(TipOfDayFactory $TipOfDayFactory)
    {
        $this->TipOfDayFactory = $TipOfDayFactory;
    }

    public function getOne(int|string $id): ?TipOfDay
    {
        $tipOfDayData = $this->getAllsAsArray();

        foreach ($tipOfDayData as $tipOfDayItemData) {
            if ((string) $tipOfDayItemData[TipOfDay::FIELD_ID] === (string) $id) {
                return $this->TipOfDayFactory->createFromData($tipOfDayItemData);
            }
        }

        return null;
    }

    public function getFirstShown(): ?TipOfDay
    {
        $tipOfDayData = $this->getAllsAsArray();

        foreach ($tipOfDayData as $tipOfDayItemData) {
            $TipOfDay = $this->TipOfDayFactory->createFromData($tipOfDayItemData);
            if (!$TipOfDay->isHidden()) {
                return $TipOfDay;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        $tipOfDayData = $this->getAllsAsArray();
        $tips = [];

        foreach ($tipOfDayData as $tipOfDayItemData) {
            $tips[] = $this->TipOfDayFactory->createFromData($tipOfDayItemData);
        }

        return $tips;
    }

    public function save(TipOfDay $TipOfDay): void
    {
        $tipOfDayDataList = $this->getAllsAsArray();
        $newTipOfDayData = $TipOfDay->toArray();
        $tipOfDayId = $TipOfDay->getId();

        $index = $this->findIndexById($tipOfDayDataList, $tipOfDayId);

        if ($index !== false) {
            $tipOfDayDataList[$index] = $newTipOfDayData;
        } else {
            $tipOfDayDataList[] = $newTipOfDayData;
        }

        $this->commitChanges($tipOfDayDataList);
    }

    public function replaceAll(array $tips): void
    {
        if (empty($tips)) {
            return;
        }

        $data = [];
        foreach ($tips as $tip) {
            if ($tip instanceof TipOfDay) {
                $data[] = $tip->toArray();
            } elseif (is_array($tip)) {
                $data[] = $tip;
            }
        }

        if (empty($data)) {
            return;
        }

        $this->commitChanges($data);
    }

    public function deleteAll(): void
    {
        set_setting(Settings::SETTING_TIP_OF_DAY, []);
        settings()->commit();
    }

    public function saveManyOnlyNew(array $data): void
    {
        $tipOfDayDataList = $this->getAllsAsArray();
        $existedTipIdList = array_map('strval', array_column($tipOfDayDataList, TipOfDay::FIELD_ID));

        foreach ($data as $dataItem) {
            $TipOfDay = $this->TipOfDayFactory->createFromData($dataItem);

            if (!in_array((string) $TipOfDay->getId(), $existedTipIdList, true)) {
                $this->save($TipOfDay);
            }
        }
    }

    private function getAllsAsArray(): array
    {
        $tipOfDayData = get_setting(Settings::SETTING_TIP_OF_DAY, []);

        return $tipOfDayData && is_array($tipOfDayData) ? $tipOfDayData : [];
    }

    private function commitChanges(array $tipOfDayDataList): void
    {
        set_setting(Settings::SETTING_TIP_OF_DAY, array_values($tipOfDayDataList));
        settings()->commit();
    }

    private function findIndexById(array $list, int|string $id): int|false
    {
        foreach ($list as $index => $item) {
            if ((string) ($item[TipOfDay::FIELD_ID] ?? '') === (string) $id) {
                return $index;
            }
        }
        return false;
    }
}
