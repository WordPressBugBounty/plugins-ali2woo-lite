<?php

/**
 * Description of TipOfDay
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class TipOfDay
{
    public const FIELD_ID = 'id';
    public const FIELD_NAME = 'name';
    public const FIELD_HTML_CONTENT = 'html_content';
    public const FIELD_IS_HIDDEN = 'is_hidden';


    public int $id;
    public string $name;
    public string $htmlContent;
    public bool $isHidden;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
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

    public function toArray(): array
    {

        return [
            self::FIELD_ID => $this->getId(),
            self::FIELD_NAME => $this->getName(),
            self::FIELD_HTML_CONTENT => $this->getHtmlContent(),
            self::FIELD_IS_HIDDEN => $this->isHidden(),
        ];
    }

}
