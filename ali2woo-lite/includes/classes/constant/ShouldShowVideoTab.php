<?php

/**
 * Description of ShouldShowVideoTab
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class ShouldShowVideoTab
{
    const SHOW = 'show';
    const HIDE = 'hide';

    public static function getAll(): array
    {
        return [
            self::SHOW,
            self::HIDE,
        ];
    }
}
