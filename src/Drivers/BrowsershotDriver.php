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

        $browsershot->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox']);

        return $browsershot;
    }
}
