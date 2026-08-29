<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use PDF\Http\Controllers\PdfTemplateController;

$middleware = config('pdf.middleware', ['web']);
$prefix = config('pdf.route_prefix', 'pdf-templates');

Route::middleware($middleware)
    ->prefix($prefix)
    ->name('pdf.templates.')
    ->group(function (): void {
        Route::get('/', [PdfTemplateController::class, 'index'])->name('index');
        Route::post('/', [PdfTemplateController::class, 'store'])->name('store');
        Route::get('/{id}', [PdfTemplateController::class, 'show'])->name('show');
        Route::put('/{id}', [PdfTemplateController::class, 'update'])->name('update');
        Route::delete('/{id}', [PdfTemplateController::class, 'destroy'])->name('destroy');
        Route::post('/preview', [PdfTemplateController::class, 'preview'])->name('preview');
    });
