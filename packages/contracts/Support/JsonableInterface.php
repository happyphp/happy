<?php

namespace Haphp\Contracts\Support;

interface JsonableInterface
{
    /**
     * Convert the object to its JSON representation.
     */
    public function toJson(int $options = 0): string;
}
