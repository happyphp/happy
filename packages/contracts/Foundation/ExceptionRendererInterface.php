<?php

declare(strict_types=1);

namespace Happy\Contracts\Foundation;

use Throwable;

interface ExceptionRendererInterface
{
    /**
     * Renders the given exception as HTML.
     */
    public function render(Throwable $throwable): string;
}
