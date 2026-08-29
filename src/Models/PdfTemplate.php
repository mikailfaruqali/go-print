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
            ->whereIn('view', [$view, '*', 'all'])
            ->whereIn('locale', [$resolvedLocale, '*', 'all'])
            ->get(['view', 'locale', 'options']);

        if ($rows->isEmpty()) {
            return [];
        }

        $row = $rows->first(fn ($r): bool => $r->view === $view && $r->locale === $resolvedLocale)
            ?? $rows->first(fn ($r): bool => $r->view === $view && in_array($r->locale, ['*', 'all'], TRUE))
            ?? $rows->first(fn ($r): bool => in_array($r->view, ['*', 'all'], TRUE) && $r->locale === $resolvedLocale)
            ?? $rows->first(fn ($r): bool => in_array($r->view, ['*', 'all'], TRUE) && in_array($r->locale, ['*', 'all'], TRUE))
            ?? $rows->first();

        $raw = $row?->options;

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = is_string($raw) ? json_decode($raw, TRUE) : NULL;

        return is_array($decoded) ? $decoded : [];
    }
}
