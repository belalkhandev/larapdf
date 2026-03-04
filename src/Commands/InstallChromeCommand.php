<?php

declare(strict_types=1);

namespace Belal\LaraPdf\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InstallChromeCommand extends Command
{
    protected $signature = 'larapdf:install-chrome {--force : Force reinstall}';

    protected $description = 'Install the required Chrome browser for Puppeteer';

    public function handle(): int
    {
        $this->info('Starting Chrome installation for LaraPdf...');

        $cachePath = config('pdf.puppeteer_cache_path');
        $env = [];

        if ($cachePath) {
            $this->comment("Using cache path: {$cachePath}");
            $env['PUPPETEER_CACHE_DIR'] = $cachePath;

            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }
        }

        $command = ['npx', 'puppeteer', 'browsers', 'install', 'chrome'];

        $process = new Process($command, base_path(), $env);
        $process->setTimeout(600); // 10 minutes

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            $this->error('Chrome installation failed!');
            return 1;
        }

        $this->info('Chrome installed successfully!');

        if ($cachePath) {
            $this->line("Make sure your .env has: PDF_PUPPETEER_CACHE_PATH={$cachePath}");
        }

        return 0;
    }
}
