<?php

declare(strict_types=1);

namespace PDF\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $view
 * @property string $locale
 * @property array<string, mixed>|null $options
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PdfTemplate extends Model
{
    /**
     * @var string
     */
    protected $table = 'pdf_templates';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'view',
        'locale',
        'options',
    ];

    /**
     * Resolve template options for a given view name and locale.
     * Matches exact view + locale, falling back to view + '*' or view + 'all',
     * and optionally falls back to a default view (e.g. '*' or a fallback template view).
     *
     * @param  string  $view  The target view name (e.g. 'invoices.show')
     * @param  string|null  $locale  The target locale (e.g. 'en', 'ar')
     * @param  string|null  $fallbackView  Optional fallback view name (e.g. '*' or 'default')
     * @return array<string, mixed>
     */
    public static function resolveOptionsForView(string $view, ?string $locale = NULL, ?string $fallbackView = NULL): array
    {
        $resolvedLocale = $locale ?? (function_exists('app') ? app()->getLocale() : 'en');
        $views = array_filter(array_unique([$view, $fallbackView, '*']));

        /** @var Collection<int, self> $templates */
        $templates = static::query()
            ->whereIn('view', $views)
            ->whereIn('locale', [$resolvedLocale, '*', 'all'])
            ->get();

        if ($templates->isEmpty()) {
            return [];
        }

        // 1. Exact view + exact locale
        $template = $templates->where('view', $view)->firstWhere('locale', $resolvedLocale)
            // 2. Exact view + wildcard locale
            ?? $templates->where('view', $view)->firstWhere('locale', '*')
            ?? $templates->where('view', $view)->firstWhere('locale', 'all');

        // 3. Fallback view if specified
        if (! $template && $fallbackView !== NULL) {
            $template = $templates->where('view', $fallbackView)->firstWhere('locale', $resolvedLocale)
                ?? $templates->where('view', $fallbackView)->firstWhere('locale', '*')
                ?? $templates->where('view', $fallbackView)->firstWhere('locale', 'all');
        }

        // 4. Global wildcard view '*'
        if (! $template) {
            $template = $templates->where('view', '*')->firstWhere('locale', $resolvedLocale)
                ?? $templates->where('view', '*')->firstWhere('locale', '*')
                ?? $templates->where('view', '*')->firstWhere('locale', 'all');
        }

        return (array) ($template?->options ?? []);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }
}
