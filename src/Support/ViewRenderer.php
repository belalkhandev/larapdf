<?php

declare(strict_types=1);

namespace Belal\LaraPdf\Support;

use Belal\LaraPdf\Exceptions\ViewNotFoundException;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Throwable;

class ViewRenderer
{
    public function __construct(
        protected ViewFactory $view
    ) {}

    public function render(string $view, array $data = []): string
    {
        try {
            return $this->view->make($view, $data)->render();
        } catch (Throwable $e) {
            throw new ViewNotFoundException("View [{$view}] not found or could not be rendered: {$e->getMessage()}", 0, $e);
        }
    }
}
