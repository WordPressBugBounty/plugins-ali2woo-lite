<?php

/**
 * Description of VideoShortcodeService
 *
 * @author Ali2Woo Team
 */

namespace AliNext_Lite;;

class VideoShortcodeService
{
    public function buildFromVideoData(array $videoData): string
    {
        if (empty($videoData)) {
            return '';
        }

        $videoUrl = Utils::getProductVideoUrl(['video' => $videoData]);
        $videoPoster = Utils::getProductVideoPoster(['video' => $videoData]);

        return sprintf(
            '[video src="%s" poster="%s"]',
            $videoUrl,
            $videoPoster ?? 'none'
        );
    }
}