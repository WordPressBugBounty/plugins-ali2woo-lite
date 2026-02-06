<?php
/**
 * Description of TokenValidatorController
 *
 * @author Ali2Woo Team
 *
 * @autoload: a2wl_admin_init
 */

namespace AliNext_Lite;;

class TokenValidatorController
{
    public function __construct(
        protected AliexpressTokenValidationService $Validator,
    ) {
        $this->Validator->validate();
    }

}
