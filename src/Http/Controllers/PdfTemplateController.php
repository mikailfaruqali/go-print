<?php

declare(strict_types=1);

namespace PDF\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use PDF\Facades\Pdf;
use PDF\Services\ViewFinderService;
use Throwable;

class PdfTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = DB::table('pdf_templates')
            ->orderBy('view')
            ->orderBy('locale')
            ->get()
            ->map(function ($t) {
                $t->options = is_array($t->options) ? $t->options : ((array) (json_decode((string) $t->options, TRUE) ?: []));

                return $t;
            });

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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'view' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:16'],
            'options' => ['nullable', 'array'],
        ]);

        $exists = DB::table('pdf_templates')
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
        $now = date('Y-m-d H:i:s');

        $id = DB::table('pdf_templates')->insertGetId([
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $template = (object) [
            'id' => $id,
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => $options,
        ];

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template created successfully.',
            'data' => $template,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $template = DB::table('pdf_templates')->where('id', $id)->first();

        if (! $template) {
            return response()->json([
                'success' => FALSE,
                'message' => 'Template not found.',
            ], 404);
        }

        $template->options = is_array($template->options)
            ? $template->options
            : ((array) (json_decode((string) $template->options, TRUE) ?: []));

        return response()->json([
            'success' => TRUE,
            'data' => $template,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $template = DB::table('pdf_templates')->where('id', $id)->first();

        if (! $template) {
            return response()->json([
                'success' => FALSE,
                'message' => 'Template not found.',
            ], 404);
        }

        $validated = $request->validate([
            'view' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:16'],
            'options' => ['nullable', 'array'],
        ]);

        $exists = DB::table('pdf_templates')
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

        DB::table('pdf_templates')->where('id', $id)->update([
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $template = (object) [
            'id' => $id,
            'view' => $validated['view'],
            'locale' => $validated['locale'],
            'options' => $options,
        ];

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template updated successfully.',
            'data' => $template,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        DB::table('pdf_templates')->where('id', $id)->delete();

        return response()->json([
            'success' => TRUE,
            'message' => 'PDF Template deleted successfully.',
        ]);
    }

    public function preview(Request $request): Response
    {
        $viewName = $request->input('view', '');
        $rawOptions = $request->input('options', []);
        $options = is_string($rawOptions) ? (json_decode($rawOptions, TRUE) ?: []) : (array) $rawOptions;
        $options = $this->sanitizeOptions($options);

        $pdf = Pdf::make();

        $contentHtml = trim((string) ($options['contentHtml'] ?? ''));
        if ($contentHtml !== '') {
            $pdf->content($contentHtml);
        } elseif ($viewName !== '' && view()->exists($viewName)) {
            try {
                $pdf->content(view($viewName, []));
            } catch (Throwable) {
                $pdf->content("<div style='font-family: sans-serif; padding: 30px; text-align: center; border: 2px dashed #ccc;'><h2>View: {$viewName}</h2><p>Previewing template layout</p></div>");
            }
        } else {
            $pdf->content("<div style='font-family: sans-serif; padding: 40px; text-align: center;'><h2>Sample Document Preview</h2><p>Configure options on the left to see live preview.</p></div>");
        }

        $pdf->applyTemplateOptions($options);

        return $pdf->withViewer()->inline('template-preview.pdf');
    }

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
