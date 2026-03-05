<?php

declare(strict_types=1);

namespace Belal\LaraPdf\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class InstallChromeCommand extends Command
{
    protected $signature = 'larapdf:install-chrome 
                            {--force : Force reinstall even if already detected} 
                            {--path= : Specify a custom shared path for installation}';

    protected $description = 'Install or detect Chrome for Puppeteer/LaraPdf';

    public function handle(): int
    {
        $this->info('--- LaraPdf Chrome Manager ---');

        // 1. Check if PDF_CHROME_PATH is already set
        if (config('pdf.chrome_path') && !$this->option('force')) {
            $this->info('✓ Chrome is already configured at: ' . config('pdf.chrome_path'));
            return 0;
        }

        // 2. Try to detect system-wide Chrome to save 600MB
        $systemChrome = $this->detectSystemChrome();
        if ($systemChrome) {
            $this->comment('✨ System Chrome detected at: ' . $systemChrome);
            $this->line('You can save 600MB by using this instead of downloading.');
            $this->line('To use it, add this to your .env:');
            $this->info("PDF_CHROME_PATH={$systemChrome}");

            if (!$this->confirm('Do you still want to download a separate project-specific Chrome?', false)) {
                $this->info('Skipped download. Please update your .env if you wish to use the detected Chrome.');
                return 0;
            }
        }

        // 3. Determine installation path
        $cachePath = $this->option('path') ?: config('pdf.puppeteer_cache_path');
        $env = [];

        if ($cachePath) {
            $this->comment("Target installation path: {$cachePath}");
            $env['PUPPETEER_CACHE_DIR'] = $cachePath;

            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }

            // Check if already installed in this path
            if ($this->isChromeInstalledInPath($cachePath) && !$this->option('force')) {
                $this->info('✓ Chrome is already installed in the specified cache path. Skipping download.');
                $this->line('Use --force to reinstall if needed.');
                return 0;
            }
        }

        // 4. Run installation
        $this->info('Starting download (this may take a while, ~600MB)...');

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

        $this->info("\n" . '✓ Chrome installed successfully!');

        if ($cachePath) {
            $this->line("Important: Ensure your .env has: PDF_PUPPETEER_CACHE_PATH={$cachePath}");
        }

        return 0;
    }

    protected function detectSystemChrome(): ?string
    {
        $binaries = ['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser', '/usr/bin/google-chrome'];

        foreach ($binaries as $binary) {
            $process = new Process(['which', $binary]);
            $process->run();
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        }

        return null;
    }

    protected function isChromeInstalledInPath(string $path): bool
    {
        // Puppeteer creates a 'chrome' directory inside the cache path
        $chromeDir = $path . DIRECTORY_SEPARATOR . 'chrome';
        if (!is_dir($chromeDir)) {
            return false;
        }

        // If there are subdirectories, assume something is installed
        // Refined check: find any file named 'chrome' or 'chrome.exe' recursively
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($chromeDir));
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'chrome' || $file->getFilename() === 'chrome.exe') {
                return true;
            }
        }

        return false;
    }
}
