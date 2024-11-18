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
        $tipOfDayData = $this->getAllsAsArray();

        foreach ($tipOfDayData as &$TipOfDayItemData)
        {
            if ($TipOfDayItemData[TipOfDay::FIELD_ID] === $TipOfDay->getId()) {
                $TipOfDayItemData = $TipOfDay->toArray();
                set_setting(Settings::SETTING_TIP_OF_DAY, array_values($tipOfDayData));
                settings()->commit();
                return;
            }
        }
    }

    private function getAllsAsArray(): array
    {
        $tipOfDayData = get_setting(Settings::SETTING_TIP_OF_DAY, []);

        return $tipOfDayData && is_array($tipOfDayData) ? $tipOfDayData : [];
    }

}
