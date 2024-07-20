<?php

declare(strict_types=1);

namespace Haphp\Container;

use Closure;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * @internal
 */
class Util
{
    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * From Arr::wrap() in Haphp\Support.
     */
    public static function arrayWrap(mixed $value): array
    {
        if (null === $value) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    /**
     * Return the default value of the given value.
     *
     * From global value() helper in Haphp\Support.
     *
     * @param  mixed  ...$args
     */
    public static function unwrapIfClosure(mixed $value, ...$args): mixed
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * From Reflector::getParameterClassName() in Haphp\Support.
     */
    public static function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if ( ! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (null !== ($class = $parameter->getDeclaringClass())) {
            if ('self' === $name) {
                return $class->getName();
            }

            if ('parent' === $name && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }
}
