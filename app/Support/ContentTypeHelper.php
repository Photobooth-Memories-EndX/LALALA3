<?php

namespace App\Support;

class ContentTypeHelper
{
    /**
     * Map internal/database content type to user-facing display label.
     *
     * @param string|null $type
     * @return string
     */
    public static function label(?string $type): string
    {
        if ($type === null || $type === '') {
            return '';
        }

        if (strtolower($type) === 'youtube') {
            return 'MOOC';
        }

        return $type;
    }
}
