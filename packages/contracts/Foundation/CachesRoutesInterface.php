<?php

namespace Haphp\Contracts\Foundation;

interface CachesRoutesInterface
{
    /**
     * Determine if the application routes are cached.
     */
    public function routesAreCached(): bool;

    /**
     * Get the path to the routes cache file.
     */
    public function getCachedRoutesPath(): string;
}
