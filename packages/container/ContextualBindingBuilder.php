<?php

namespace Haphp\Container;

use Haphp\Contracts\Container\ContainerInterface;
use Haphp\Contracts\Container\ContextualBindingBuilderInterface;

class ContextualBindingBuilder implements ContextualBindingBuilderInterface
{
    /**
     * The abstract target.
     */
    protected string $needs;

    /**
     * Create a new contextual binding builder.
     *
     * @return void
     */
    public function __construct(
        protected ContainerInterface $container,
        protected string|array $concrete
    ) {
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @return $this
     */
    public function needs(string $abstract): static
    {
        $this->needs = $abstract;

        return $this;
    }

    /**
     * Define the implementation for the contextual binding.
     *
     * @param  \Closure|string|array  $implementation
     * @return void
     */
    public function give($implementation)
    {
        foreach (Util::arrayWrap($this->concrete) as $concrete) {
            $this->container->addContextualBinding($concrete, $this->needs, $implementation);
        }
    }

    /**
     * Define tagged services to be used as the implementation for the contextual binding.
     *
     * @param  string  $tag
     * @return void
     */
    public function giveTagged($tag)
    {
        $this->give(function ($container) use ($tag) {
            $taggedServices = $container->tagged($tag);

            return is_array($taggedServices) ? $taggedServices : iterator_to_array($taggedServices);
        });
    }

    /**
     * Specify the configuration item to bind as a primitive.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return void
     */
    public function giveConfig($key, $default = null)
    {
        $this->give(fn ($container) => $container->get('config')->get($key, $default));
    }
}
