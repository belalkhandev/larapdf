<?php

declare(strict_types=1);

namespace Belal\LaraPdf\Jobs;

use Belal\LaraPdf\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeneratePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $state,
        protected string $path,
        protected ?string $disk = null
    ) {}

    public function handle(): void
    {
        $pdf = app('larapdf');
        $pdf->applySerializedState($this->state);
        $pdf->saveToDisk($this->path, $this->disk);
    }
}
