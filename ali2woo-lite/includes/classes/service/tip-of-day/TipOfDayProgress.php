<?php

namespace AliNext_Lite;;

class TipOfDayProgress
{
    private Settings $SettingsService;

    public function __construct(Settings $SettingsService)
    {
        $this->SettingsService = $SettingsService;
    }

    public function getSeenIds(): array
    {
        return $this->SettingsService->get(Settings::SETTING_TIP_OF_DAY_SEEN_IDS, []);
    }

    public function addSeenId(string $id): void
    {
        $ids = $this->getSeenIds();
        if (!in_array($id, $ids, true)) {
            $ids[] = $id;
            $this->save($ids);
        }
    }

    public function addSeenIds(array $ids): void
    {
        $existing = $this->getSeenIds();
        $changed = false;
        foreach ($ids as $id) {
            if (!in_array((string) $id, $existing, true)) {
                $existing[] = (string) $id;
                $changed = true;
            }
        }
        if ($changed) {
            $this->save($existing);
        }
    }

    public function reset(): void
    {
        $this->save([]);
    }

    public function removeStale(array $validIds): void
    {
        $validStrings = array_map('strval', $validIds);
        $ids = $this->getSeenIds();
        $filtered = array_values(array_intersect($ids, $validStrings));
        if (count($filtered) !== count($ids)) {
            $this->save($filtered);
        }
    }

    private function save(array $ids): void
    {
        $this->SettingsService->set(Settings::SETTING_TIP_OF_DAY_SEEN_IDS, $ids);
        $this->SettingsService->commit();
    }
}
