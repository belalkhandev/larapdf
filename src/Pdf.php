<?php

declare(strict_types=1);

namespace Belal\LaraPdf;

use Belal\LaraPdf\Drivers\BrowsershotDriver;
use Belal\LaraPdf\Support\FontManager;
use Belal\LaraPdf\Support\TailwindInjector;
use Belal\LaraPdf\Support\ViewRenderer;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\Response;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Pdf
{
    protected string $html = '';
    protected string $paperSize;
    protected string $orientation;
    protected array $margins;
    protected int $timeout;
    protected ?string $headerHtml = null;
    protected ?string $footerHtml = null;
    protected bool $withTailwind = false;
    protected ?string $localTailwindPath = null;
    protected bool $waitNetworkIdle = false;

    public function __construct(
        protected BrowsershotDriver $driver,
        protected ViewRenderer $viewRenderer,
        protected FontManager $fontManager,
        protected TailwindInjector $tailwindInjector,
        protected FilesystemFactory $filesystem
    ) {
        $this->paperSize = config('pdf.default_paper', 'A4');
        $this->orientation = config('pdf.default_orientation', 'portrait');
        $this->margins = config('pdf.default_margins', [10, 10, 10, 10]);
        $this->timeout = config('pdf.timeout', 60);
    }

    public function loadView(string $view, array $data = []): self
    {
        $this->html = $this->viewRenderer->render($view, $data);
        return $this;
    }

    public function loadHtml(string $html): self
    {
        $this->html = $html;
        return $this;
    }

    public function paperSize(string $size): self
    {
        $this->paperSize = $size;
        return $this;
    }

    public function orientation(string $orientation): self
    {
        $this->orientation = $orientation;
        return $this;
    }

    public function margins(int $top, int $right, int $bottom, int $left): self
    {
        $this->margins = [$top, $right, $bottom, $left];
        return $this;
    }

    public function headerView(string $view, array $data = []): self
    {
        $this->headerHtml = $this->viewRenderer->render($view, $data);
        return $this;
    }

    public function footerView(string $view, array $data = []): self
    {
        $this->footerHtml = $this->viewRenderer->render($view, $data);
        return $this;
    }

    public function font(string $name, string $path): self
    {
        $this->fontManager->register($name, $path);
        return $this;
    }

    public function googleFont(string $family): self
    {
        $this->fontManager->registerGoogleFont($family);
        return $this;
    }

    public function withTailwind(?string $path = null): self
    {
        $this->withTailwind = true;
        $this->localTailwindPath = $path;
        return $this;
    }

    public function waitUntilNetworkIdle(): self
    {
        $this->waitNetworkIdle = true;
        return $this;
    }

    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function nodeBinary(string $path): self
    {
        $this->driver->setNodeBinary($path);
        return $this;
    }

    public function npmBinary(string $path): self
    {
        $this->driver->setNpmBinary($path);
        return $this;
    }

    public function chromePath(string $path): self
    {
        $this->driver->setChromePath($path);
        return $this;
    }

    public function puppeteerCachePath(string $path): self
    {
        $this->driver->setPuppeteerCachePath($path);
        return $this;
    }


    public function download(string $filename = 'document.pdf'): Response
    {
        return new Response($this->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function stream(string $filename = 'document.pdf'): Response
    {
        return new Response($this->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function saveToDisk(string $path, ?string $disk = null): bool
    {
        $disk = $disk ?: config('pdf.disk', 'local');
        return $this->filesystem->disk($disk)->put($path, $this->output());
    }

    public function base64(): string
    {
        return base64_encode($this->output());
    }

    public function inline(): string
    {
        return $this->output();
    }

    public function queue(string $path, ?string $disk = null): void
    {
        $disk = $disk ?: config('pdf.disk', 'local');
        Jobs\GeneratePdfJob::dispatch($this->getSerializedState(), $path, $disk);
    }

    public function toHtml(): string
    {
        return $this->prepareHtml();
    }

    public function preview(): \Illuminate\Http\Response
    {
        return response($this->toHtml());
    }

    protected function output(): string
    {
        $html = $this->prepareHtml();

        // Automatically extract <header> and <footer> tags if they are not already set via methods
        if (!$this->headerHtml || !$this->footerHtml) {
            $this->extractHeaderFooterFromHtml($html);
        }

        $browsershot = Browsershot::html($html);

        $this->driver->configure($browsershot);

        if ($this->headerHtml) {
            $browsershot->showBrowserHeaderAndFooter()
                ->headerHtml($this->prepareHeaderFooterHtml($this->headerHtml));

            if ($this->margins[0] === config('pdf.default_margins.0', 10)) {
                $this->margins[0] = 35;
            }
        }

        if ($this->footerHtml) {
            $browsershot->showBrowserHeaderAndFooter()
                ->footerHtml($this->prepareHeaderFooterHtml($this->footerHtml));

            if ($this->margins[2] === config('pdf.default_margins.2', 10)) {
                $this->margins[2] = 35;
            }
        }

        $browsershot->format($this->paperSize)
            ->orientation($this->orientation)
            ->margins($this->margins[0], $this->margins[1], $this->margins[2], $this->margins[3])
            ->timeout($this->timeout);

        if ($this->waitNetworkIdle) {
            $browsershot->waitUntilNetworkIdle();
        }

        try {
            return $browsershot->pdf();
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $e) {
            $this->handleProcessFailure($e);
            throw $e;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Could not find Chrome')) {
                throw new \Belal\LaraPdf\Exceptions\PdfException(
                    "Chrome not found. Please run 'php artisan larapdf:install-chrome' or configure PDF_CHROME_PATH in your .env file.\nOriginal error: " . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
            throw $e;
        }
    }

    protected function handleProcessFailure($e): void
    {
        $output = $e->getProcess()->getErrorOutput();

        if (str_contains($output, 'Could not find Chrome')) {
            throw new \Belal\LaraPdf\Exceptions\PdfException(
                "Chrome not found. LaraPdf needs Chrome to render PDFs. \n" .
                    "Steps to fix:\n" .
                    "1. Run 'php artisan larapdf:install-chrome' to install a local version.\n" .
                    "2. Or specify your system Chrome path in .env: PDF_CHROME_PATH=/path/to/chrome\n" .
                    "3. Ensure the cache directory is writable.\n" .
                    "Context: /var/www/workspace/laravel-pdf/packages/belal/larapdf/README.md#troubleshooting",
                (int)$e->getCode(),
                $e
            );
        }
    }


    protected function extractHeaderFooterFromHtml(string &$html): void
    {
        // Extract <header>
        if (!$this->headerHtml && preg_match('/<header[^>]*>(.*?)<\/header>/is', $html, $matches)) {
            $this->headerHtml = $matches[0];
            $html = preg_replace('/<header[^>]*>.*?<\/header>/is', '', $html, 1);
        }

        // Extract <footer>
        if (!$this->footerHtml && preg_match('/<footer[^>]*>(.*?)<\/footer>/is', $html, $matches)) {
            $this->footerHtml = $matches[0];
            $html = preg_replace('/<footer[^>]*>.*?<\/footer>/is', '', $html, 1);
        }

        // Strip conflicting @page margin: 0 rules which cause overlapping when using headers/footers
        $html = preg_replace('/@page\s*{[^}]*margin\s*:\s*0[^}]*}/i', '', $html);
    }

    protected function prepareHtml(): string
    {
        $html = $this->html;

        if ($this->withTailwind) {
            $html = $this->tailwindInjector->inject($html, $this->localTailwindPath);
        }

        $googleFontsHtml = $this->fontManager->getGoogleFontsHtml();
        $fontDeclarations = $this->fontManager->getFontFaceDeclarations();

        if ($googleFontsHtml || $fontDeclarations) {
            $style = $googleFontsHtml . ($fontDeclarations ? "<style>{$fontDeclarations}</style>" : "");

            if (str_contains($html, '</head>')) {
                $html = str_replace('</head>', $style . '</head>', $html);
            } else {
                $html = $style . $html;
            }
        }

        return $html;
    }

    protected function prepareHeaderFooterHtml(string $html): string
    {
        // Enforce basic layout for Puppeteer headers/footers
        // We use absolute positioning and ensure visibility.
        // Puppeteer headers/footers have no access to external CSS files reliably.
        $style = '
            <style>
                * { -webkit-print-color-adjust: exact; box-sizing: border-box; }
                html, body { 
                    margin: 0 !important; 
                    padding: 0 !important; 
                    width: 100% !important;
                    -webkit-font-smoothing: antialiased;
                }
                body { 
                    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial;
                    font-size: 11px;
                }
                header, footer { 
                    width: 100% !important; 
                    padding: 0 10mm;
                    display: flex !important;
                    justify-content: space-between !important;
                    align-items: center !important;
                }
                .flex { display: flex !important; }
                .justify-between { justify-content: space-between !important; }
                .items-center { align-items: center !important; }
                .text-right { text-align: right !important; }
                .text-left { text-align: left !important; }
                .text-center { text-align: center !important; }
                .font-bold { font-weight: bold !important; }
                .text-xl { font-size: 1.25rem !important; }
                .text-4xl { font-size: 2.25rem !important; }
                .text-gray-500 { color: #6b7280 !important; }
                .text-gray-600 { color: #4b5563 !important; }
                .text-green-600 { color: #16a34a !important; }
                .w-full { width: 100% !important; }
                .border-b { border-bottom: 1px solid #e5e7eb !important; }
                .pb-4 { padding-bottom: 1rem !important; }
                .mb-8 { margin-bottom: 2rem !important; }
                .p-8 { padding: 2rem !important; }
                header div, footer div { box-sizing: border-box; }
            </style>
        ';

        // Strip <html>, <head>, <body> tags if they exists to avoid nested structures in Puppeteer templates
        $html = preg_replace('/<(?:!DOCTYPE|html|head|body|meta|title)[^>]*>/i', '', $html);
        $html = str_replace(['</html>', '</head>', '</body>'], '', $html);

        return $this->fontManager->getGoogleFontsHtml() . $style . $html;
    }

    protected function getSerializedState(): array
    {
        return [
            'html' => $this->html,
            'paperSize' => $this->paperSize,
            'orientation' => $this->orientation,
            'margins' => $this->margins,
            'timeout' => $this->timeout,
            'headerHtml' => $this->headerHtml,
            'footerHtml' => $this->footerHtml,
            'withTailwind' => $this->withTailwind,
            'localTailwindPath' => $this->localTailwindPath,
            'waitNetworkIdle' => $this->waitNetworkIdle,
        ];
    }

    public function applySerializedState(array $state): self
    {
        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }
}
