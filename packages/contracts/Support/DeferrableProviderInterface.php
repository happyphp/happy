<?php

namespace Haphp\Contracts\Support;

interface DeferrableProviderInterface
{
    /**
     * Get the services provided by the provider.
     */
    public function provides(): array;
}
