<?php

namespace AliNext_Lite;;

class TipOfDayFactory
{
    public function normalizeFromApiData(array $tipData): array
    {
        if (isset($tipData['html_content'])) {
            $tipData['html_content'] = html_entity_decode($tipData['html_content']);
        }

        $tipData[TipOfDay::FIELD_TYPE] ??= TipOfDay::TYPE_TIP;
        $tipData[TipOfDay::FIELD_CTA_URL] ??= null;
        $tipData[TipOfDay::FIELD_CTA_LABEL] ??= null;
        $tipData[TipOfDay::FIELD_PLAN] ??= 'all';
        $tipData[TipOfDay::FIELD_BILLING_CYCLE] ??= null;

        return $tipData;
    }

    public function createFromData(array $data): TipOfDay
    {
        $tip = new TipOfDay();
        $tip->setId($data[TipOfDay::FIELD_ID] ?? 0)
            ->setName($data[TipOfDay::FIELD_NAME] ?? '')
            ->setHtmlContent($data[TipOfDay::FIELD_HTML_CONTENT] ?? '')
            ->setIsHidden($data[TipOfDay::FIELD_IS_HIDDEN] ?? false)
            ->setType($data[TipOfDay::FIELD_TYPE] ?? TipOfDay::TYPE_TIP)
            ->setCtaUrl($data[TipOfDay::FIELD_CTA_URL] ?? null)
            ->setCtaLabel($data[TipOfDay::FIELD_CTA_LABEL] ?? null)
            ->setPlan($data[TipOfDay::FIELD_PLAN] ?? 'all')
            ->setBillingCycle($data[TipOfDay::FIELD_BILLING_CYCLE] ?? null);

        return $tip;
    }

    public function create(
        int|string $id = 0,
        string $name = '',
        string $htmlContent = '',
        bool $isHidden = false,
        string $type = TipOfDay::TYPE_TIP,
        ?string $ctaUrl = null,
        ?string $ctaLabel = null,
        ?string $plan = 'all',
        ?string $billingCycle = null
    ): TipOfDay {
        return $this->createFromData([
            TipOfDay::FIELD_ID => $id,
            TipOfDay::FIELD_NAME => $name,
            TipOfDay::FIELD_HTML_CONTENT => $htmlContent,
            TipOfDay::FIELD_IS_HIDDEN => $isHidden,
            TipOfDay::FIELD_TYPE => $type,
            TipOfDay::FIELD_CTA_URL => $ctaUrl,
            TipOfDay::FIELD_CTA_LABEL => $ctaLabel,
            TipOfDay::FIELD_PLAN => $plan,
            TipOfDay::FIELD_BILLING_CYCLE => $billingCycle,
        ]);
    }
}
