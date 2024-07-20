<?php

declare(strict_types=1);

namespace Haphp\Contracts\Container;

use Closure;
use InvalidArgumentException;
use LogicException;

interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * Determine if the given abstract type has been bound.
     */
    public function bound(string $abstract): bool;

    /**
     * Alias a type to a different name.
     *
     * @throws LogicException
     */
    public function alias(string $abstract, string $alias): void;

    /**
     * Assign a set of tags to a given binding.
     *
     * @param  mixed|array  ...$tags
     */
    public function tag(array|string $abstracts, array $tags): void;

    /**
     * Resolve all the bindings for a given tag.
     */
    public function tagged(string $tag): iterable;

    /**
     * Register a binding with the container.
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void;

    /**
     * Bind a callback to resolve with Container::call.
     */
    public function bindMethod(array|string $method, Closure $callback): void;

    /**
     * Register a binding if it hasn't already been registered.
     */
    public function bindIf(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void;

    /**
     * Register a shared binding in the container.
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register a shared binding if it hasn't already been registered.
     */
    public function singletonIf(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register a scoped binding in the container.
     */
    public function scoped(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * Register a scoped binding if it hasn't already been registered.
     */
    public function scopedIf(string $abstract, Closure|string|null $concrete = null): void;

    /**
     * "Extend" an abstract type in the container.
     *
     * @throws InvalidArgumentException
     */
    public function extend(string $abstract, Closure $closure): void;

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(string $abstract, mixed $instance): mixed;

    /**
     * Add a contextual binding to the container.
     */
    public function addContextualBinding(string $concrete, string $abstract, string|Closure $implementation): void;

    /**
     * Define a contextual binding.
     */
    public function when(array|string $concrete): ContextualBindingBuilderInterface;

    /**
     * Get a closure to resolve the given type from the container.
     */
    public function factory(string $abstract): Closure;

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush(): void;

    /**
     * Resolve the given type from the container.
     *
     * @throws BindingResolutionException
     */
    public function make(string $abstract, array $parameters = []): mixed;

    /**
     * Call the given Closure / class@method and inject its dependencies.
     */
    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed;

    /**
     * Determine if the given abstract type has been resolved.
     */
    public function resolved(string $abstract): bool;

    /**
     * Register a new before resolving callback.
     */
    public function beforeResolving(string|Closure $abstract, ?Closure $callback = null): void;

    /**
     * Register a new resolving callback.
     */
    public function resolving(string|Closure $abstract, ?Closure $callback = null): void;

    /**
     * Register a new after resolving callback.
     */
    public function afterResolving(string|Closure $abstract, ?Closure $callback = null): void;
}
