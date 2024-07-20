<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Illuminate\Support\Str;

abstract class AbstractUuid extends AbstractString
{
    public static function generate(): static
    {
        return new static(value: Str::uuid()->toString());
    }

    public static function fromNullableUuid(?string $uuid): static
    {
        return new static(value: $uuid);
    }

    public static function fromUuid(string $uuid): static
    {
        return new static(value: $uuid);
    }

    public function toString(): string
    {
        return $this->toNullableString() ?? Str::uuid()->toString();
    }
}
