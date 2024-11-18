<?php
/**
 * Description of TipOfDayFactory
 *
 * @author Ali2Woo Team
 *
 */

namespace AliNext_Lite;;

class TipOfDayFactory
{
    public function createFromData(array $data): TipOfDay
    {

        return (new TipOfDay())
            ->setId($data[TipOfDay::FIELD_ID])
            ->setName($data[TipOfDay::FIELD_NAME])
            ->setHtmlContent($data[TipOfDay::FIELD_HTML_CONTENT])
            ->setIsHidden($data[TipOfDay::FIELD_IS_HIDDEN]);
    }

    public function create(
        int $id, string $name, string $htmlContent, bool $isHidden = false
    ): TipOfDay {

        return $this->createFromData([
            TipOfDay::FIELD_ID => $id,
            TipOfDay::FIELD_NAME => $name,
            TipOfDay::FIELD_HTML_CONTENT => $htmlContent,
            TipOfDay::FIELD_IS_HIDDEN => $isHidden
        ]);
    }
}
