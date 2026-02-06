<?php

/**
 * Description of AliexpressRegionRepository
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class AliexpressRegionRepository
{
    public function get(): string
    {
        $aliexpressRegion = get_setting(Settings::SETTING_ALIEXPRESS_REGION);

        if (a2wl_check_defined('A2WL_API_REGION')) {
            $aliexpressRegion = A2WL_API_REGION;
        }

        return $aliexpressRegion;
    }

    public function set(string $region): void
    {
        set_setting(Settings::SETTING_ALIEXPRESS_REGION, $region);
    }

    public function getAllWithLabels(): array
    {
        return [
            'US' => 'United States',
            'UK' => 'United Kingdom',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'AR' => 'Argentina',
            'ZA' => 'South Africa',
            'AU' => 'Australia',
            'CA' => 'Canada',
            'FR' => 'France',
            'BR' => 'Brazil',
            'HR' => 'Croatia',
            'PT' => 'Portugal',
            'SE' => 'Sweden',
            'DE' => 'Germany',
            'IT' => 'Italy',
            'FI' => 'Finland',
            'GR' => 'Greece',
            'RU' => 'Russia',
            'NL' => 'Netherlands',
            'IE' => 'Ireland',
            'ES' => 'Spain',
            'EE' => 'Estonia',
            'IN' => 'India',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'PL' => 'Poland',
            'BD' => 'Bangladesh',
            'CR' => 'Costa Rica',
            'KW' => 'Kuwait',
            'LK' => 'Sri Lanka',
            'MT' => 'Malta',
            'MC' => 'Monaco',
            'EC' => 'Ecuador',
            'PS' => 'Palestine',
            'SG' => 'Singapore',
            'KR' => 'South Korea',
            'JP' => 'Japan',

        ];
    }
}
