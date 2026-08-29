<?php

declare(strict_types=1);

namespace PDF\Models;

use Illuminate\Support\Facades\DB;

class PdfTemplate
{
    public static function resolveOptionsForView(string $view, ?string $locale = NULL): array
    {
        $resolvedLocale = $locale ?? (function_exists('app') ? app()->getLocale() : 'en');

        $rows = DB::table('pdf_templates')
            ->where('view', $view)
            ->whereIn('locale', [$resolvedLocale, '*', 'all'])
            ->get(['locale', 'options']);

        if ($rows->isEmpty()) {
            return [];
        }

        $row = $rows->firstWhere('locale', $resolvedLocale)
            ?? $rows->firstWhere('locale', '*')
            ?? $rows->firstWhere('locale', 'all')
            ?? $rows->first();

        $raw = $row?->options;

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = is_string($raw) ? json_decode($raw, TRUE) : NULL;

        return is_array($decoded) ? $decoded : [];
    }
}
