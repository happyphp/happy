<?php

namespace Haphp\Contracts\Session;

use Symfony\Component\HttpFoundation\Request;

interface SessionHandlerInterface extends \SessionHandlerInterface
{
    /**
     * Set the request instance.
     */
    public function setRequest(Request $request): void;
}
