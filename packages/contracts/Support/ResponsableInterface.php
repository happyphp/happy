<?php

namespace Haphp\Contracts\Support;

use Haphp\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface ResponsableInterface
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse(Request $request): Response;
}
