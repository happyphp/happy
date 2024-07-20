<?php

declare(strict_types=1);

namespace Haphp\Container;

use ArrayAccess;
use Closure;
use Exception;
use Haphp\Contracts\Container\BindingResolutionException;
use Haphp\Contracts\Container\CircularDependencyExceptionInterface;
use Haphp\Contracts\Container\ContainerInterface;
use Haphp\Contracts\Container\ContextualBindingBuilderInterface;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;
use TypeError;

class Container implements ArrayAccess, ContainerInterface
{
    /**
     * The contextual binding map.
     *
     * @var array[]
     */
    public array $contextual = [];

    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static ContainerInterface $instance;

    /**
     * An array of the types that have been resolved.
     *
     * @var bool[]
     */
    protected array $resolved = [];

    /**
     * The container's bindings.
     *
     * @var array[]
     */
    protected array $bindings = [];

    /**
     * The container's method bindings.
     *
     * @var Closure[]
     */
    protected array $methodBindings = [];

    /**
     * The container's shared instances.
     *
     * @var object[]
     */
    protected array $instances = [];

    /**
     * The container's scoped instances.
     */
    protected array $scopedInstances = [];

    /**
     * The registered type aliases.
     *
     * @var string[]
     */
    protected array $aliases = [];

    /**
     * The registered aliases keyed by the abstract name.
     *
     * @var array[]
     */
    protected array $abstractAliases = [];

    /**
     * The extension closures for services.
     *
     * @var array[]
     */
    protected array $extenders = [];

    /**
     * All the registered tags.
     *
     * @var array[]
     */
    protected array $tags = [];

    /**
     * The stack of concretions currently being built.
     *
     * @var array[]
     */
    protected array $buildStack = [];

    /**
     * The parameter override stack.
     *
     * @var array[]
     */
    protected array $with = [];

    /**
     * All the registered rebound callbacks.
     *
     * @var array[]
     */
    protected array $reboundCallbacks = [];

    /**
     * All the global before resolving callbacks.
     *
     * @var Closure[]
     */
    protected array $globalBeforeResolvingCallbacks = [];

    /**
     * All the global resolving callbacks.
     *
     * @var Closure[]
     */
    protected array $globalResolvingCallbacks = [];

    /**
     * All the global after resolving callbacks.
     *
     * @var Closure[]
     */
    protected array $globalAfterResolvingCallbacks = [];

    /**
     * All the before resolving callbacks by class type.
     *
     * @var array[]
     */
    protected array $beforeResolvingCallbacks = [];

    /**
     * All the resolving callbacks by class type.
     *
     * @var array[]
     */
    protected array $resolvingCallbacks = [];

    /**
     * All the after resolving callbacks by class type.
     *
     * @var array[]
     */
    protected array $afterResolvingCallbacks = [];

    /**
     * Dynamically access container services.
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     */
    public function __set(string $key, mixed $value): void
    {
        $this[$key] = $value;
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): Container|static
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     */
    public static function setInstance(?ContainerInterface $container = null): ?ContainerInterface
    {
        return static::$instance = $container;
    }

    /**
     * Define a contextual binding.
     */
    public function when(array|string $concrete): ContextualBindingBuilderInterface
    {
        $aliases = [];

        foreach (Util::arrayWrap($concrete) as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    /**
     * Determine if the given abstract type has been bound.
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
               isset($this->instances[$abstract]) ||
               $this->isAlias($abstract);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    /**
     * Determine if the given abstract type has been resolved.
     */
    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->resolved[$abstract]) ||
               isset($this->instances[$abstract]);
    }

    /**
     * Determine if a given type is shared.
     */
    public function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
               (isset($this->bindings[$abstract]['shared']) &&
               true === $this->bindings[$abstract]['shared']);
    }

    /**
     * Determine if a given string is an alias.
     */
    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    /**
     * Register a binding with the container.
     *
     *
     * @throws TypeError
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        // If no concrete type was given, we will simply set the concrete type to the
        // abstract type. After that, the concrete type to be registered as shared
        // without being forced to state their classes in both of the parameters.
        if (null === $concrete) {
            $concrete = $abstract;
        }

        // If the factory is not a Closure, it means it is just a class name which is
        // bound into this container to the abstract type and we will just wrap it
        // up inside its own Closure to give us more convenience when extending.
        if ( ! $concrete instanceof Closure) {
            if ( ! is_string($concrete)) {
                throw new TypeError(self::class . '::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        // If the abstract type was already resolved in this container we'll fire the
        // rebound listener so that any objects which have already gotten resolved
        // can have their copy of the object updated via the listener callbacks.
        if ($this->resolved($abstract)) {
            $this->rebound($abstract);
        }
    }

    /**
     * Determine if the container has a method binding.
     */
    public function hasMethodBinding(string $method): bool
    {
        return isset($this->methodBindings[$method]);
    }

    /**
     * Bind a callback to resolve with Container::call.
     */
    public function bindMethod(array|string $method, Closure $callback): void
    {
        $this->methodBindings[$this->parseBindMethod($method)] = $callback;
    }

    /**
     * Get the method binding for the given method.
     */
    public function callMethodBinding(string $method, mixed $instance): mixed
    {
        return call_user_func($this->methodBindings[$method], $instance, $this);
    }

    /**
     * Add a contextual binding to the container.
     */
    public function addContextualBinding(string $concrete, string $abstract, string|Closure $implementation): void
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    /**
     * Register a binding if it hasn't already been registered.
     */
    public function bindIf(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        if ( ! $this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    /**
     * Register a shared binding in the container.
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register a shared binding if it hasn't already been registered.
     */
    public function singletonIf(string $abstract, Closure|string|null $concrete = null): void
    {
        if ( ! $this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    /**
     * Register a scoped binding in the container.
     */
    public function scoped(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->scopedInstances[] = $abstract;

        $this->singleton($abstract, $concrete);
    }

    /**
     * Register a scoped binding if it hasn't already been registered.
     */
    public function scopedIf(string $abstract, Closure|string|null $concrete = null): void
    {
        if ( ! $this->bound($abstract)) {
            $this->scoped($abstract, $concrete);
        }
    }

    /**
     * "Extend" an abstract type in the container.
     *
     *
     * @throws InvalidArgumentException
     */
    public function extend(string $abstract, Closure $closure): void
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $closure($this->instances[$abstract], $this);

            $this->rebound($abstract);
        } else {
            $this->extenders[$abstract][] = $closure;

            if ($this->resolved($abstract)) {
                $this->rebound($abstract);
            }
        }
    }

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        $this->removeAbstractAlias($abstract);

        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }

        return $instance;
    }

    /**
     * Assign a set of tags to a given binding.
     *
     * @param  array|mixed  ...$tags
     */
    public function tag(array|string $abstracts, $tags): void
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);

        foreach ($tags as $tag) {
            if ( ! isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    /**
     * Resolve all the bindings for a given tag.
     */
    public function tagged(string $tag): RewindableGenerator|array
    {
        if ( ! isset($this->tags[$tag])) {
            return [];
        }

        return new RewindableGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->make($abstract);
            }
        }, count($this->tags[$tag]));
    }

    /**
     * Alias a type to a different name.
     *
     *
     * @throws LogicException
     */
    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    /**
     * Bind a new callback to an abstract's rebind event.
     *
     * @throws BindingResolutionException
     */
    public function rebinding(string $abstract, Closure $callback): mixed
    {
        $this->reboundCallbacks[$abstract = $this->getAlias($abstract)][] = $callback;

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }

        return null;
    }

    /**
     * Refresh an instance on the given target and method.
     *
     * @throws BindingResolutionException
     */
    public function refresh(string $abstract, mixed $target, string $method): mixed
    {
        return $this->rebinding($abstract, function ($app, $instance) use ($target, $method): void {
            $target->{$method}($instance);
        });
    }

    /**
     * Wrap the given closure such that its dependencies will be injected when executed.
     */
    public function wrap(Closure $callback, array $parameters = []): Closure
    {
        return fn () => $this->call($callback, $parameters);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  array<string, mixed>  $parameters
     *
     * @throws InvalidArgumentException|ReflectionException
     */
    public function call(callable|string $callback, array $parameters = [], ?string $defaultMethod = null): mixed
    {
        $pushedToBuildStack = false;

        if (($className = $this->getClassForCallable($callback)) && ! in_array(
            $className,
            $this->buildStack,
            true
        )) {
            $this->buildStack[] = $className;

            $pushedToBuildStack = true;
        }

        $result = BoundMethod::call($this, $callback, $parameters, $defaultMethod);

        if ($pushedToBuildStack) {
            array_pop($this->buildStack);
        }

        return $result;
    }

    /**
     * Get a closure to resolve the given type from the container.
     */
    public function factory(string $abstract): Closure
    {
        return fn () => $this->make($abstract);
    }

    /**
     * An alias function name for make().
     *
     *
     * @throws BindingResolutionException
     */
    public function makeWith(callable|string $abstract, array $parameters = []): mixed
    {
        return $this->make($abstract, $parameters);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string|callable  $abstract
     *
     * @throws BindingResolutionException|CircularDependencyExceptionInterface
     */
    public function make($abstract, array $parameters = []): mixed
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): mixed
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            if ($this->has($id) || $e instanceof CircularDependencyExceptionInterface) {
                throw $e;
            }

            throw new EntryNotFoundException($id, is_int($e->getCode()) ? $e->getCode() : 0, $e);
        }
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     *
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function build(string|Closure $concrete): mixed
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new BindingResolutionException("Target class [{$concrete}] does not exist.", 0, $e);
        }

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if ( ! $reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (null === $constructor) {
            array_pop($this->buildStack);

            return new $concrete();
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (BindingResolutionException $e) {
            array_pop($this->buildStack);

            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Register a new before resolving callback for all types.
     */
    public function beforeResolving(string|Closure $abstract, ?Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && null === $callback) {
            $this->globalBeforeResolvingCallbacks[] = $abstract;
        } else {
            $this->beforeResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new resolving callback.
     */
    public function resolving(string|Closure $abstract, ?Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if (null === $callback && $abstract instanceof Closure) {
            $this->globalResolvingCallbacks[] = $abstract;
        } else {
            $this->resolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Register a new after resolving callback for all types.
     */
    public function afterResolving(string|Closure $abstract, ?Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        if ($abstract instanceof Closure && null === $callback) {
            $this->globalAfterResolvingCallbacks[] = $abstract;
        } else {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    /**
     * Get the container's bindings.
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get the alias for an abstract if available.
     */
    public function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
                    ? $this->getAlias($this->aliases[$abstract])
                    : $abstract;
    }

    /**
     * Remove all the extender callbacks for a given type.
     */
    public function forgetExtenders(string $abstract): void
    {
        unset($this->extenders[$this->getAlias($abstract)]);
    }

    /**
     * Remove a resolved instance from the instance cache.
     */
    public function forgetInstance(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    /**
     * Clear all the instances from the container.
     */
    public function forgetInstances(): void
    {
        $this->instances = [];
    }

    /**
     * Clear all the scoped instances from the container.
     */
    public function forgetScopedInstances(): void
    {
        foreach ($this->scopedInstances as $scoped) {
            unset($this->instances[$scoped]);
        }
    }

    /**
     * Flush the container of all bindings and resolved instances.
     */
    public function flush(): void
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
        $this->scopedInstances = [];
    }

    /**
     * Determine if a given offset exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->bound($offset);
    }

    /**
     * Get the value at a given offset.
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyExceptionInterface
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->make($offset);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function offsetSet($key, $value): void
    {
        $this->bind($key, $value instanceof Closure ? $value : fn () => $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     */
    public function offsetUnset($key): void
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }

    /**
     * Get the Closure to be used when building a type.
     *
     * @param  string  $abstract
     * @param  string  $concrete
     * @return Closure
     */
    protected function getClosure($abstract, $concrete)
    {
        return function ($container, $parameters = []) use ($abstract, $concrete) {
            if ($abstract == $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve(
                $concrete,
                $parameters,
                $raiseEvents = false
            );
        };
    }

    /**
     * Get the method to be bound in class@method format.
     */
    protected function parseBindMethod(array|string $method): string
    {
        if (is_array($method)) {
            return $method[0] . '@' . $method[1];
        }

        return $method;
    }

    /**
     * Remove an alias from the contextual binding alias cache.
     */
    protected function removeAbstractAlias(string $searched): void
    {
        if ( ! isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias == $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    /**
     * Fire the "rebound" callbacks for the given abstract type.
     */
    protected function rebound(string $abstract): void
    {
        $instance = $this->make($abstract);

        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            $callback($this, $instance);
        }
    }

    /**
     * Get the rebound callbacks for a given type.
     */
    protected function getReboundCallbacks(string $abstract): array
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }

    /**
     * Get the class name for the given callback, if one can be determined.
     *
     * @throws ReflectionException
     */
    protected function getClassForCallable(callable|string $callback): false|string
    {
        if (is_callable($callback) &&
            ! ($reflector = new ReflectionFunction($callback(...)))->isAnonymous()) {
            return $reflector->getClosureScopeClass()->name ?? false;
        }

        return false;
    }

    /**
     * Resolve the given type from the container.
     *
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyExceptionInterface|ReflectionException
     */
    protected function resolve(callable|string $abstract, array $parameters = [], bool $raiseEvents = true): mixed
    {
        $abstract = $this->getAlias($abstract);

        // First we'll fire any event handlers which handle the "before" resolving of
        // specific types. This gives some hooks the chance to add various extends
        // calls to change the resolution of objects that they're interested in.
        if ($raiseEvents) {
            $this->fireBeforeResolvingCallbacks($abstract, $parameters);
        }

        $concrete = $this->getContextualConcrete($abstract);

        $needsContextualBuild = ! empty($parameters) || null !== $concrete;

        // If an instance of the type is currently being managed as a singleton we'll
        // just return an existing instance instead of instantiating new instances
        // so the developer can keep using the same objects instance every time.
        if (isset($this->instances[$abstract]) && ! $needsContextualBuild) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        if (null === $concrete) {
            $concrete = $this->getConcrete($abstract);
        }

        // We're ready to instantiate an instance of the concrete type registered for
        // the binding. This will instantiate the types, as well as resolve any of
        // its "nested" dependencies recursively until all have gotten resolved.
        $object = $this->isBuildable($concrete, $abstract)
            ? $this->build($concrete)
            : $this->make($concrete);

        // If we defined any extenders for this type, we'll need to spin through them
        // and apply them to the object being built. This allows for the extension
        // of services, such as changing configuration or decorating the object.
        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        // If the requested type is registered as a singleton we'll want to cache off
        // the instances in "memory" so we can return it later without creating an
        // entirely new instance of an object on each subsequent request for it.
        if ($this->isShared($abstract) && ! $needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        if ($raiseEvents) {
            $this->fireResolvingCallbacks($abstract, $object);
        }

        // Before returning, we will also set the resolved flag to "true" and pop off
        // the parameter overrides for this build. After those two things are done
        // we will be ready to return back the fully constructed class instance.
        $this->resolved[$abstract] = true;

        array_pop($this->with);

        return $object;
    }

    /**
     * Get the concrete type for a given abstract.
     */
    protected function getConcrete(callable|string $abstract): mixed
    {
        // If we don't have a registered resolver or concrete for the type, we'll just
        // assume each type is a concrete name and will attempt to resolve it as is
        // since the container should be able to resolve concretes automatically.
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Get the contextual concrete binding for the given abstract.
     */
    protected function getContextualConcrete(callable|string $abstract): array|Closure|string|null
    {
        if (null !== ($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        // Next we need to see if a contextual binding might be bound under an alias of the
        // given abstract type. So, we will need to check if any aliases exist with this
        // type and then spin through them and check for contextual bindings on these.
        if (empty($this->abstractAliases[$abstract])) {
            return null;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (null !== ($binding = $this->findInContextualBindings($alias))) {
                return $binding;
            }
        }

        return null;
    }

    /**
     * Find the concrete binding for the given abstract in the contextual binding array.
     */
    protected function findInContextualBindings(callable|string $abstract): string|Closure|null
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    /**
     * Determine if the given concrete is buildable.
     */
    protected function isBuildable(mixed $concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    /**
     * Resolve all the dependencies from the ReflectionParameters.
     *
     * @param  ReflectionParameter[]  $dependencies
     *
     * @throws BindingResolutionException
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If the dependency has an override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);

                continue;
            }

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            $result = null === Util::getParameterClassName($dependency)
                            ? $this->resolvePrimitive($dependency)
                            : $this->resolveClass($dependency);

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Determine if the given dependency has a parameter override.
     */
    protected function hasParameterOverride(ReflectionParameter $dependency): bool
    {
        return array_key_exists(
            $dependency->name,
            $this->getLastParameterOverride()
        );
    }

    /**
     * Get a parameter override for a dependency.
     */
    protected function getParameterOverride(ReflectionParameter $dependency): mixed
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    /**
     * Get the last parameter override.
     */
    protected function getLastParameterOverride(): array
    {
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * Resolve a non-class hinted primitive dependency.
     *
     *
     * @throws BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if (null !== ($concrete = $this->getContextualConcrete('$' . $parameter->getName()))) {
            return Util::unwrapIfClosure($concrete, $this);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }

        $this->unresolvablePrimitive($parameter);
    }

    /**
     * Resolve a class based dependency from the container.
     *
     *
     * @throws BindingResolutionException
     * @throws CircularDependencyExceptionInterface
     */
    protected function resolveClass(ReflectionParameter $parameter): mixed
    {
        try {
            return $parameter->isVariadic()
                        ? $this->resolveVariadicClass($parameter)
                        : $this->make(Util::getParameterClassName($parameter));
        }

        // If we can not resolve the class instance, we will check to see if the value
        // is optional, and if it is we will return the optional parameter value as
        // the value of the dependency, similarly to how we do this with scalars.
        catch (BindingResolutionException $e) {
            if ($parameter->isDefaultValueAvailable()) {
                array_pop($this->with);

                return $parameter->getDefaultValue();
            }

            if ($parameter->isVariadic()) {
                array_pop($this->with);

                return [];
            }

            throw $e;
        }
    }

    /**
     * Resolve a class based variadic dependency from the container.
     */
    protected function resolveVariadicClass(ReflectionParameter $parameter): mixed
    {
        $className = Util::getParameterClassName($parameter);

        $abstract = $this->getAlias($className);

        if ( ! is_array($concrete = $this->getContextualConcrete($abstract))) {
            return $this->make($className);
        }

        return array_map(fn ($abstract) => $this->resolve($abstract), $concrete);
    }

    /**
     * Throw an exception that the concrete is not instantiable.
     *
     *
     * @throws BindingResolutionException
     */
    protected function notInstantiable(string $concrete): void
    {
        if ( ! empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [{$concrete}] is not instantiable while building [{$previous}].";
        } else {
            $message = "Target [{$concrete}] is not instantiable.";
        }

        throw new BindingResolutionException($message);
    }

    /**
     * Throw an exception for an unresolvable primitive.
     *
     *
     * @throws BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter): void
    {
        $message = "Unresolvable dependency resolving [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    /**
     * Fire all the before resolving callbacks.
     */
    protected function fireBeforeResolvingCallbacks(string $abstract, array $parameters = []): void
    {
        $this->fireBeforeCallbackArray($abstract, $parameters, $this->globalBeforeResolvingCallbacks);

        foreach ($this->beforeResolvingCallbacks as $type => $callbacks) {
            if ($type === $abstract || is_subclass_of($abstract, $type)) {
                $this->fireBeforeCallbackArray($abstract, $parameters, $callbacks);
            }
        }
    }

    /**
     * Fire an array of callbacks with an object.
     */
    protected function fireBeforeCallbackArray(string $abstract, array $parameters, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($abstract, $parameters, $this);
        }
    }

    /**
     * Fire all the resolving callbacks.
     */
    protected function fireResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->globalResolvingCallbacks);

        $this->fireCallbackArray(
            $object,
            $this->getCallbacksForType($abstract, $object, $this->resolvingCallbacks)
        );

        $this->fireAfterResolvingCallbacks($abstract, $object);
    }

    /**
     * Fire all the after resolving callbacks.
     */
    protected function fireAfterResolvingCallbacks(string $abstract, mixed $object): void
    {
        $this->fireCallbackArray($object, $this->globalAfterResolvingCallbacks);

        $this->fireCallbackArray(
            $object,
            $this->getCallbacksForType($abstract, $object, $this->afterResolvingCallbacks)
        );
    }

    /**
     * Get all callbacks for a given type.
     */
    protected function getCallbacksForType(string $abstract, object $object, array $callbacksPerType): array
    {
        $results = [];

        foreach ($callbacksPerType as $type => $callbacks) {
            if ($type === $abstract || $object instanceof $type) {
                $results = array_merge($results, $callbacks);
            }
        }

        return $results;
    }

    /**
     * Fire an array of callbacks with an object.
     */
    protected function fireCallbackArray(mixed $object, array $callbacks): void
    {
        foreach ($callbacks as $callback) {
            $callback($object, $this);
        }
    }

    /**
     * Get the extender callbacks for a given type.
     */
    protected function getExtenders(string $abstract): array
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    /**
     * Drop all the stale instances and aliases.
     */
    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }
}
