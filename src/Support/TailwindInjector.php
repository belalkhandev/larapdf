<?php

declare(strict_types=1);

namespace Belal\LaraPdf\Support;

class TailwindInjector
{
    public function inject(string $html, string|null $path = null, bool $isHeaderFooter = false): string
    {
        $tailwindHtml = $this->getTailwindHtml($path, $isHeaderFooter);

        if (str_contains($html, '</head>')) {
            return str_replace('</head>', $tailwindHtml . '</head>', $html);
        }

        return $tailwindHtml . $html;
    }

    protected function getTailwindHtml(string|null $path = null, bool $isHeaderFooter = false): string
    {
        if ($path && file_exists($path)) {
            $css = file_get_contents($path);

            // If the local CSS is too large (e.g. > 100KB), we might still hit limits in headers.
            // But usually local compiled CSS is much smaller than the full CDN.
            return "<style>{$css}</style>";
        }

        $cdn = config('pdf.tailwind_cdn', 'https://cdn.tailwindcss.com');

        // Headers/footers cannot easily use the JS-based Play CDN.
        // We will NOT inline the full 2MB+ Tailwind CSS here as it breaks shell limits.
        // Users are encouraged to use local compiled CSS for full Tailwind support in headers.
        if ($isHeaderFooter) {
            return '';
        }

        return "<script src=\"{$cdn}\"></script>";
    }
}
