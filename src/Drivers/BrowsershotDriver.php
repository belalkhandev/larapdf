<?php

declare(strict_types=1);

namespace Belal\LaraPdf\Drivers;

use Belal\LaraPdf\Exceptions\PdfException;
use Spatie\Browsershot\Browsershot;

class BrowsershotDriver
{
    public function __construct(
        protected array $config
    ) {}

    public function configure(Browsershot $browsershot): Browsershot
    {
        $nodeBinary = $this->config['node_binary'] ?? 'node';
        $npmBinary = $this->config['npm_binary'] ?? 'npm';

        if ($nodeBinary !== 'node') {
            $browsershot->setNodeBinary($nodeBinary);
        }

        if ($npmBinary !== 'npm') {
            $browsershot->setNpmBinary($npmBinary);
        }

        if ($chromePath = $this->config['chrome_path'] ?? null) {
            $browsershot->setChromePath($chromePath);
        }

        if ($cachePath = $this->config['puppeteer_cache_path'] ?? null) {
            if (!is_dir($cachePath)) {
                @mkdir($cachePath, 0755, true);
            }
            // Set environment variable for Browsershot's node process
            putenv("PUPPETEER_CACHE_DIR={$cachePath}");
            $browsershot->setOption('env', array_merge($_ENV, ['PUPPETEER_CACHE_DIR' => $cachePath]));
        }


        $browsershot->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox']);

        return $browsershot;
    }

    public function setNodeBinary(string $path): self
    {
        $this->config['node_binary'] = $path;
        return $this;
    }

    public function setNpmBinary(string $path): self
    {
        $this->config['npm_binary'] = $path;
        return $this;
    }

    public function setChromePath(string $path): self
    {
        $this->config['chrome_path'] = $path;
        return $this;
    }

    public function setPuppeteerCachePath(string $path): self
    {
        $this->config['puppeteer_cache_path'] = $path;
        return $this;
    }
}
