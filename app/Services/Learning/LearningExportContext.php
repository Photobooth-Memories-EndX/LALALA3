<?php

namespace App\Services\Learning;

class LearningExportContext
{
    /** @var array<string, string> */
    protected array $categories = [];

    /** @var array<string, string> */
    protected array $scores = [];

    /** @var array<string, string> */
    protected array $certificates = [];

    public function setCategory(string $key, string $value): void
    {
        $this->categories[$key] = $value;
    }

    public function setScore(string $key, string $value): void
    {
        $this->scores[$key] = $value;
    }

    public function setCertificate(string $key, string $value): void
    {
        $this->certificates[$key] = $value;
    }

    public function resolveCategory(object $row): string
    {
        $key = $this->rowKey($row);
        if (isset($this->categories[$key])) {
            return $this->categories[$key];
        }

        $isPlaylist = (($row->source_type ?? '') === 'playlist')
            || (!empty($row->trainingplan_id))
            || (($row->category_source ?? '') === 'Playlist');

        if ($isPlaylist) {
            $title = trim((string) ($row->playlist_title ?? ($row->trainingplan->title ?? ($row->trainingPlan->title ?? ''))));
            return $title !== '' ? 'Playlist: ' . $title : 'Playlist';
        }

        return 'Content';
    }

    public function resolveScore(object $row): string
    {
        $key = $this->rowKey($row);
        if (isset($this->scores[$key])) {
            return $this->scores[$key];
        }

        if (isset($row->assessment_score) && $row->assessment_score !== null && $row->assessment_score !== '') {
            return $this->formatScoreValue($row->assessment_score);
        }
        if (isset($row->score) && $row->score !== null && $row->score !== '') {
            return $this->formatScoreValue($row->score);
        }

        return 'Not Applicable';
    }

    public function resolveCertificate(object $row): string
    {
        $key = $this->rowKey($row);
        if (isset($this->certificates[$key])) {
            return $this->certificates[$key];
        }

        if (isset($row->certificate) && $row->certificate !== null && $row->certificate !== '') {
            $certVal = trim((string) $row->certificate);
            if (in_array(strtolower($certVal), ['ready', 'available', 'yes', '1', 'true'], true)) {
                return 'Available';
            }
            if (in_array(strtolower($certVal), ['not available', 'no', '0', 'false', 'unavailable'], true)) {
                return 'Not Available';
            }
            return $certVal;
        }

        return 'Not Available';
    }

    public function rowKey(object $row): string
    {
        $userId = (int) ($row->user_id ?? ($row->user->id ?? 0));
        $sourceType = (string) ($row->source_type ?? 'content');
        $contentId = (int) ($row->content_id ?? ($row->content->id ?? 0));
        $tpId = (int) ($row->trainingplan_id ?? ($row->trainingPlan->id ?? 0));
        $sourceId = (int) ($row->source_id ?? 0);

        return "{$userId}_{$sourceType}_{$contentId}_{$tpId}_{$sourceId}";
    }

    public function formatScoreValue(mixed $val): string
    {
        if (!is_numeric($val)) {
            return '-';
        }
        return (string) (int) round((float) $val);
    }
}
