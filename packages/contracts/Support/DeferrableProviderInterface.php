<?php

namespace Happy\Contracts\Support;

interface DeferrableProviderInterface
{
    /**
     * Get the services provided by the provider.
     */
    public function provides(): array;
}
