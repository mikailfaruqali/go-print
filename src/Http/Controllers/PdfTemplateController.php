<?php

declare(strict_types=1);

namespace PDF\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use PDF\Facades\Pdf;
use PDF\Models\PdfTemplate;
use PDF\Services\ViewFinderService;
use Throwable;

class PdfTemplateController extends Controller
{
    /**
     * Display the PDF Templates management interface.
     */
    public function index(): Response
    {
        $templates = PdfTemplate::query()
            ->orderBy('view')
            ->orderBy('locale')
            ->get();

        $availableViews = ViewFinderService::getAvailableViews();

        $supportedLocales = (array) config('pdf.locales', ['en', 'ar', 'ckb', 'ku', 'fr', 'de', 'es', 'tr', 'fa']);
        if (! in_array('*', $supportedLocales, TRUE)) {
            array_unshift($supportedLocales, '*');
        }

        $paperSizes = [
            'A4', 'Letter', 'Legal', 'A3', 'A5', 'A6', 'A0', 'A1', 'A2',
            'B4', 'B5', 'Tabloid', 'Ledger', 'Executive',
        ];

        return response()->view('pdf::templates.index', [
            'templates' => $templates,
            'availableViews' => $availableViews,
            'supportedLocales' => $supportedLocales,
            'paperSizes' => $paperSizes,
        ]);
    }

    /**
     * Store a newly created template in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'view' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:16'],
            'options' => ['nullable', 'array'],
        ]);

        $exists = PdfTemplate::query()
            ->where('view', $validated['view'])
            ->where('locale', $validated['locale'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => FALSE,
                'message' => "A template for view '{$validated['view']}' and locale '{$validated['locale']}' already exists.",
            ], 422);
        }

        $options = $this->sanitizeOptions($request->input('options', []));

        $template = PdfTemplate::create([
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => $options,
        ]);

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template created successfully.',
            'data' => $template,
        ], 201);
    }

    /**
     * Get a specific template as JSON.
     */
    public function show(int $id): JsonResponse
    {
        $template = PdfTemplate::findOrFail($id);

        return response()->json([
            'success' => TRUE,
            'data' => $template,
        ]);
    }

    /**
     * Update the specified template in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = PdfTemplate::findOrFail($id);

        $validated = $request->validate([
            'view' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:16'],
            'options' => ['nullable', 'array'],
        ]);

        $exists = PdfTemplate::query()
            ->where('view', $validated['view'])
            ->where('locale', $validated['locale'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => FALSE,
                'message' => "Another template for view '{$validated['view']}' and locale '{$validated['locale']}' already exists.",
            ], 422);
        }

        $options = $this->sanitizeOptions($request->input('options', []));

        $template->update([
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => $options,
        ]);

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template updated successfully.',
            'data' => $template,
        ]);
    }

    /**
     * Remove the specified template from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $template = PdfTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template deleted successfully.',
        ]);
    }

    /**
     * Preview the PDF with template options applied.
     */
    public function preview(Request $request): Response
    {
        $viewName = $request->input('view', '');
        $rawOptions = $request->input('options', []);
        $options = is_string($rawOptions) ? (json_decode($rawOptions, TRUE) ?: []) : (array) $rawOptions;
        $options = $this->sanitizeOptions($options);

        $pdf = Pdf::make();

        // 1. Resolve content HTML: custom HTML override or sample render of view
        $contentHtml = trim((string) ($options['contentHtml'] ?? ''));
        if ($contentHtml !== '') {
            $pdf->content($contentHtml);
        } elseif ($viewName !== '' && view()->exists($viewName)) {
            try {
                $pdf->content(view($viewName, []));
            } catch (Throwable) {
                // If view requires variables that aren't supplied, fallback to dummy container with view name
                $pdf->content("<div style='font-family: sans-serif; padding: 30px; text-align: center; border: 2px dashed #ccc;'><h2>View: {$viewName}</h2><p>Previewing template layout</p></div>");
            }
        } else {
            $pdf->content("<div style='font-family: sans-serif; padding: 40px; text-align: center;'><h2>Sample Document Preview</h2><p>Configure options on the left to see live preview.</p></div>");
        }

        // 2. Apply all options to Pdf instance
        $pdf->applyTemplateOptions($options);

        // Always preview with built-in sn-kit viewer inline
        return $pdf->withViewer()->inline('template-preview.pdf');
    }

    /**
     * Sanitize and format options array.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function sanitizeOptions(array $options): array
    {
        $clean = [];

        $stringFields = [
            'paper', 'orientation', 'margin', 'marginTop', 'marginBottom',
            'marginLeft', 'marginRight', 'headerHeight', 'footerHeight',
            'headerSpacing', 'footerSpacing', 'headerOffset', 'footerOffset',
            'title', 'author', 'subject', 'keywords', 'baseUrl', 'theme',
            'dir', 'icon', 'fontFamily', 'fontStack', 'fontPath',
            'headerHtml', 'footerHtml', 'watermarkHtml', 'contentHtml',
        ];

        foreach ($stringFields as $stringField) {
            if (isset($options[$stringField]) && trim((string) $options[$stringField]) !== '') {
                $clean[$stringField] = (string) $options[$stringField];
            }
        }

        if (isset($options['watermarkOpacity']) && is_numeric($options['watermarkOpacity'])) {
            $clean['watermarkOpacity'] = (float) $options['watermarkOpacity'];
        }

        if (isset($options['scale']) && is_numeric($options['scale'])) {
            $clean['scale'] = (float) $options['scale'];
        }

        if (isset($options['pageOffset']) && is_numeric($options['pageOffset'])) {
            $clean['pageOffset'] = (int) $options['pageOffset'];
        }

        if (isset($options['totalOffset']) && is_numeric($options['totalOffset'])) {
            $clean['totalOffset'] = (int) $options['totalOffset'];
        }

        if (isset($options['timeout']) && is_numeric($options['timeout'])) {
            $clean['timeout'] = (int) $options['timeout'];
        }

        $boolFields = [
            'watermarkBehind', 'preferCssPageSize', 'withViewer', 'quiet',
        ];

        foreach ($boolFields as $boolField) {
            if (isset($options[$boolField])) {
                $clean[$boolField] = filter_var($options[$boolField], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $clean;
    }
}
