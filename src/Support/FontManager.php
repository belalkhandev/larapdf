<?php

declare(strict_types=1);

namespace Belal\LaraPdf\Support;

class FontManager
{
    protected array $fonts = [];
    protected array $googleFonts = [];

    public function register(string $name, string $path): void
    {
        $this->fonts[$name] = $path;
    }

    public function registerGoogleFont(string $family): void
    {
        if (!in_array($family, $this->googleFonts, true)) {
            $this->googleFonts[] = $family;
        }
    }

    public function getFontFaceDeclarations(): string
    {
        $declarations = '';

        foreach ($this->fonts as $name => $path) {
            if (!file_exists($path)) {
                continue;
            }

            $rawContent = file_get_contents($path);
            if ($rawContent === false) {
                continue;
            }

            $content = base64_encode($rawContent);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $format = $this->getFormat($extension);

            $declarations .= "
                @font-face {
                    font-family: '{$name}';
                    src: url(data:font/{$extension};charset=utf-8;base64,{$content}) format('{$format}');
                }
            ";
        }

        return $declarations;
    }

    public function getGoogleFontsHtml(): string
    {
        if (empty($this->googleFonts)) {
            return '';
        }

        $families = implode('&family=', array_map('urlencode', $this->googleFonts));
        $url = "https://fonts.googleapis.com/css2?family={$families}&display=swap";

        try {
            // We fetch the CSS and inline it to avoid external requests inside Puppeteer isolated contexts
            // which often causes "Protocol error (Page.printToPDF): Printing failed"
            // Use a Chrome User-Agent to get the modern woff2 formats
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => "Connection: close\r\nUser-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n"
                ]
            ];
            $context = stream_context_create($opts);
            set_error_handler(static fn () => true);
            try {
                $css = file_get_contents($url, false, $context);
            } finally {
                restore_error_handler();
            }

            if ($css) {
                return "<style>{$css}</style>";
            }
        } catch (\Exception $e) {
            // Fallback to link tag if fetching fails
            return "<link href=\"{$url}\" rel=\"stylesheet\">";
        }

        return "<link href=\"{$url}\" rel=\"stylesheet\">";
    }

    protected function getFormat(string $extension): string
    {
        return match ($extension) {
            'ttf' => 'truetype',
            'woff' => 'woff',
            'woff2' => 'woff2',
            'otf' => 'opentype',
            default => $extension,
        };
    }
}
