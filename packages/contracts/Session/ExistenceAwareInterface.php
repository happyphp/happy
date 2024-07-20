<?php

declare(strict_types=1);

namespace Haphp\Contracts\Session;

use SessionHandlerInterface;

interface ExistenceAwareInterface
{
    /**
     * Set the existence state for the session.
     */
    public function setExists(bool $value): SessionHandlerInterface;
}
