<?php

/**
 * Description of AliexpressCategoryDto
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class AliexpressCategoryDto
{
    public function __construct(
        private int $id,
        private int $parentId,
        private string $name
    ) {}

    public function getId(): float
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }
}
