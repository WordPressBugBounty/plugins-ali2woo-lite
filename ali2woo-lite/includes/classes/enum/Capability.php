<?php

/* * class
 * Description of Capability
 *
 * @author Ali2Woo Team
 *
 */

namespace AliNext_Lite;;

final class Capability {

    public static function pluginAccess(): string
    {
        return 'use_' . A2WL()->isAnPlugin() ? 'alinext-lite' : 'ali2woo';
    }
}
