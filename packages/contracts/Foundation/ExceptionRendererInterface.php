<?php

declare(strict_types=1);

namespace Haphp\Contracts\Foundation;

use Throwable;

interface ExceptionRendererInterface
{
    /**
     * Renders the given exception as HTML.
     */
    public function render(Throwable $throwable): string;
}
