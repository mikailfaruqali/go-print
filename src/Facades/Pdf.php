<?php

declare(strict_types=1);

namespace PDF\Facades;

use Closure;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;
use PDF\Pdf as PdfBuilder;

/**
 * @method static PdfBuilder make()
 * @method static PdfBuilder view(string $view, array $data = [])
 * @method static PdfBuilder loadTemplate(string $view, array $data = [])
 * @method static PdfBuilder applyTemplateOptions(array $options, array $data = [])
 * @method static PdfBuilder content(Renderable|string $html)
 * @method static PdfBuilder header(Renderable|string $html)
 * @method static PdfBuilder headerView(string $view, array $data = [])
 * @method static PdfBuilder footer(Renderable|string $html)
 * @method static PdfBuilder footerView(string $view, array $data = [])
 * @method static PdfBuilder watermark(Renderable|string $html)
 * @method static PdfBuilder paper(string $paper)
 * @method static PdfBuilder pageWidth(string $width)
 * @method static PdfBuilder pageHeight(string $height)
 * @method static PdfBuilder pageSize(string $width, string $height)
 * @method static PdfBuilder dimensions(string $width, string $height)
 * @method static PdfBuilder a4()
 * @method static PdfBuilder a3()
 * @method static PdfBuilder a5()
 * @method static PdfBuilder letter()
 * @method static PdfBuilder legal()
 * @method static PdfBuilder orientation(string $orientation)
 * @method static PdfBuilder portrait()
 * @method static PdfBuilder landscape()
 * @method static PdfBuilder margin(string $margin)
 * @method static PdfBuilder marginTop(string $margin)
 * @method static PdfBuilder marginBottom(string $margin)
 * @method static PdfBuilder marginLeft(string $margin)
 * @method static PdfBuilder marginRight(string $margin)
 * @method static PdfBuilder headerHeight(string $height)
 * @method static PdfBuilder footerHeight(string $height)
 * @method static PdfBuilder headerSpacing(string $spacing)
 * @method static PdfBuilder footerSpacing(string $spacing)
 * @method static PdfBuilder headerOffset(string $offset)
 * @method static PdfBuilder footerOffset(string $offset)
 * @method static PdfBuilder watermarkOpacity(float $opacity)
 * @method static PdfBuilder watermarkBehind(bool $behind = true)
 * @method static PdfBuilder scale(float $scale)
 * @method static PdfBuilder preferCssPageSize(bool $prefer = true)
 * @method static PdfBuilder pageOffset(int $offset)
 * @method static PdfBuilder totalOffset(int $offset)
 * @method static PdfBuilder title(string $title)
 * @method static ?string getTitle()
 * @method static PdfBuilder author(string $author)
 * @method static PdfBuilder subject(string $subject)
 * @method static PdfBuilder keywords(string $keywords)
 * @method static PdfBuilder baseUrl(string $baseUrl)
 * @method static PdfBuilder quiet(bool $quiet = true)
 * @method static PdfBuilder timeout(int $timeout)
 * @method static PdfBuilder chromePath(string $chromePath)
 * @method static PdfBuilder binaryPath(string $binaryPath)
 * @method static PdfBuilder tempDirectory(string $tempDirectory)
 * @method static PdfBuilder withViewer(bool $withViewer = true)
 * @method static PdfBuilder font(?string $path = null, ?string $family = null, ?string $stack = null)
 * @method static PdfBuilder dir(?string $dir = null)
 * @method static PdfBuilder rtl()
 * @method static PdfBuilder ltr()
 * @method static PdfBuilder theme(string $theme = 'dark')
 * @method static PdfBuilder darkMode(bool $dark = true)
 * @method static PdfBuilder lightMode(bool $light = true)
 * @method static PdfBuilder icon(?string $icon = null)
 * @method static PdfBuilder cssFile(string $path)
 * @method static PdfBuilder cssFiles(array $paths)
 * @method static PdfBuilder withoutCssFiles()
 * @method static array getCssFiles()
 * @method static PdfBuilder when(mixed $value = null, ?callable $callback = null, ?callable $default = null)
 * @method static PdfBuilder unless(mixed $value = null, ?callable $callback = null, ?callable $default = null)
 * @method static string resolveBinaryPath()
 * @method static Response download(?string $filename = null)
 * @method static Response inline(?string $filename = null)
 * @method static Response renderViewer(?string $filename = null)
 * @method static mixed save(string|Closure $destination)
 * @method static mixed toFile(string|Closure $path)
 * @method static string get()
 * @method static array debugHtml()
 * @method static Response dumpHtml(?string $section = null)
 * @method static void ddHtml(?string $section = null)
 *
 * @see PdfBuilder
 */
class Pdf extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'pdf';
    }
}
