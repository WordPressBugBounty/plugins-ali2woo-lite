<?php

namespace AliNext_Lite;;

class TipOfDay
{
    public const TYPE_TIP = 'tip';

    public const FIELD_ID = 'id';
    public const FIELD_NAME = 'name';
    public const FIELD_HTML_CONTENT = 'html_content';
    public const FIELD_IS_HIDDEN = 'is_hidden';
    public const FIELD_TYPE = 'type';
    public const FIELD_CTA_URL = 'cta_url';
    public const FIELD_CTA_LABEL = 'cta_label';
    public const FIELD_PLAN = 'plan';
    public const FIELD_BILLING_CYCLE = 'billing_cycle';
    public const FIELD_TIPS_OF_DAY = 'tipsOfDay';

    public int|string $id = 0;
    public string $name;
    public string $htmlContent;
    public bool $isHidden;
    public string $type = self::TYPE_TIP;
    public ?string $ctaUrl = null;
    public ?string $ctaLabel = null;
    public ?string $plan = 'all';
    public ?string $billingCycle = null;

    public function getId(): int|string
    {
        return $this->id;
    }

    public function setId(int|string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $Name): self
    {
        $this->name = $Name;

        return $this;
    }

    public function getHtmlContent(): string
    {
        return $this->htmlContent;
    }
    public function setHtmlContent(string $htmlContent): self
    {
        $this->htmlContent = $htmlContent;

        return $this;
    }

    public function isHidden(): bool
    {
        return $this->isHidden;
    }

    public function setIsHidden(bool $isHidden): self
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getCtaUrl(): ?string
    {
        return $this->ctaUrl;
    }
    public function setCtaUrl(?string $ctaUrl): self
    {
        $this->ctaUrl = $ctaUrl;

        return $this;
    }

    public function getCtaLabel(): ?string
    {
        return $this->ctaLabel;
    }
    public function setCtaLabel(?string $ctaLabel): self
    {
        $this->ctaLabel = $ctaLabel;

        return $this;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setPlan(?string $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getBillingCycle(): ?string
    {
        return $this->billingCycle;
    }

    public function setBillingCycle(?string $billingCycle): self
    {
        $this->billingCycle = $billingCycle;

        return $this;
    }

    public function toArray(): array
    {
        return [
            self::FIELD_ID => $this->getId(),
            self::FIELD_NAME => $this->getName(),
            self::FIELD_HTML_CONTENT => $this->getHtmlContent(),
            self::FIELD_IS_HIDDEN => $this->isHidden(),
            self::FIELD_TYPE => $this->getType(),
            self::FIELD_CTA_URL => $this->getCtaUrl(),
            self::FIELD_CTA_LABEL => $this->getCtaLabel(),
            self::FIELD_PLAN => $this->getPlan(),
            self::FIELD_BILLING_CYCLE => $this->getBillingCycle(),
        ];
    }

    public static function getDefaultData(): array
    {
        $htmlContent = <<<HTML
    <p>
    Connect your AliExpress account via Access Token to enable extended functionality in AliNext (Lite version).
    The available features depend on your active plugin plan.
    </p>
    <p>
    Go to <strong>AliNext (Lite version) → Settings → Account</strong>, click <strong>"Get Access Token"</strong>,
    and log in to your AliExpress account to authorize the connection.
    </p>
    <p style="margin-top:15px;">
    <a target="_blank" href="https://help.ali2woo.com/codex/how-to-get-access-token-from-aliexpress/?utm_source=alinext-lite&amp;utm_medium=tip_of_day&amp;utm_campaign=connect_access_token" class="a2wl-tip-of-day-cta-link">Learn how to get your Access Token →</a>
    </p>
HTML;

        return [
            [
                self::FIELD_ID => 'connect_access_token',
                self::FIELD_NAME => 'Tip of the Day: Connect Your AliExpress Account',
                self::FIELD_HTML_CONTENT => $htmlContent,
                self::FIELD_IS_HIDDEN => false,
                self::FIELD_TYPE => self::TYPE_TIP,
                self::FIELD_PLAN => 'all',
                self::FIELD_BILLING_CYCLE => null,
            ],
        ];
    }

}
