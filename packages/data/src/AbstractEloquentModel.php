<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractEloquentModel extends AbstractValue
{
    abstract public function toEloquentModel();

    abstract public function toNullableEloquentModel();

    public static function fromEloquentModel(Model $model): static
    {
        return new static(value: $model);
    }

    public static function fromArray(array $item): static
    {
        return new static();
    }

    public function isNull(): bool
    {
        return null === $this->value;
    }

    public function toPrimitive(): array
    {
        return [];
    }
}
