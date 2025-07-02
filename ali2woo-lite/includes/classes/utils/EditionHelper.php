<?php
namespace AliNext_Lite;;

class EditionHelper
{
    public static function isLite(): bool
    {
        return a2wl_check_defined('ALINEXT_IS_LITE');
    }

    public static function isFull(): bool
    {
        return !self::isLite();
    }
}
