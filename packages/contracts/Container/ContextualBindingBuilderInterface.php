<?php

declare(strict_types=1);

namespace Haphp\Contracts\Container;

use Closure;

interface ContextualBindingBuilderInterface
{
    /**
     * Define the abstract target that depends on the context.
     */
    public function needs(string $abstract): static;

    /**
     * Define the implementation for the contextual binding.
     */
    public function give(array|string|Closure $implementation): void;

    /**
     * Define tagged services to be used as the implementation for the contextual binding.
     */
    public function giveTagged(string $tag): void;

    /**
     * Specify the configuration item to bind as a primitive.
     */
    public function giveConfig(string $key, mixed $default = null): void;
}
