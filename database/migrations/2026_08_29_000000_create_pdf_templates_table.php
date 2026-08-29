<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('view')->index();
            $table->string('locale', 16)->default('*')->index();
            $table->json('options')->nullable();
            $table->timestamps();

            $table->unique(['view', 'locale'], 'pdf_templates_view_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_templates');
    }
};
