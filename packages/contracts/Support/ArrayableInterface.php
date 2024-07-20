<?php

namespace Haphp\Contracts\Support;

/**
 * @template TKey of array-key
 * @template TValue
 */
interface ArrayableInterface
{
    /**
     * Get the instance as an array.
     *
     * @return array<TKey, TValue>
     */
    public function toArray(): array;
}
