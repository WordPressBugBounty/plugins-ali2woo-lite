<?php

/**
 * Description of TipOfDayRepository
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class TipOfDayRepository
{
    private TipOfDayFactory $TipOfDayFactory;

    public function __construct(TipOfDayFactory $TipOfDayFactory)
    {
        $this->TipOfDayFactory = $TipOfDayFactory;
    }

    public function getOne(int $id): ?TipOfDay
    {
        $tipOfDayData = $this->getAllsAsArray();

        foreach ($tipOfDayData as $tipOfDayItemData) {
            if ($tipOfDayItemData[TipOfDay::FIELD_ID] === $id) {
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

    public function save(TipOfDay $TipOfDay): void
    {
        $tipOfDayDataList = $this->getAllsAsArray();
        $newTipOfDayData = $TipOfDay->toArray();
        $tipOfDayId = $TipOfDay->getId();

        $index = array_search(
            $tipOfDayId,
            array_column($tipOfDayDataList, TipOfDay::FIELD_ID)
        );

        if ($index !== false) {
            $tipOfDayDataList[$index] = $newTipOfDayData;
        } else {
            $tipOfDayDataList[] = $newTipOfDayData;
        }


        $this->commitChanges($tipOfDayDataList);
    }

    public function saveManyOnlyNew(array $data): void
    {
        $tipOfDayDataList = $this->getAllsAsArray();
        $existedTipIdList = array_column($tipOfDayDataList, TipOfDay::FIELD_ID);

        foreach ($data as $dataItem) {
            $TipOfDay = $this->TipOfDayFactory->createFromData($dataItem);

            if (!in_array($TipOfDay->getId(), $existedTipIdList)) {
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

}
