<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class AbstractString extends AbstractValue
{
    public static function fromEloquentModel(Model $model): static
    {
        $value = $model->getAttributes()[static::getDatabaseTableColumnName()] ?? null;

        $instance = new static(value: empty($value) ? null : $value);
        $instance->setEloquentModel($model);

        return $instance;
    }

    public static function fromArray(array $item): static
    {
        return new static(value: $item[static::getName()] ?? $item[static::getCsvColumnName()] ?? null);
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public static function fake(): static
    {
        return new static(value: fake()->sentence);
    }

    public static function fromNullableString(?string $value): static
    {
        return new static($value);
    }

    public function toNullableString(): ?string
    {
        $value = $this->toValue();

        if (empty($value)) {
            return null;
        }

        return $value;
    }

    public function toString(): string
    {
        return $this->toNullableString() ?? '';
    }

    public function toPrimitive(): ?string
    {
        return $this->toNullableString();
    }

    public function isNull(): bool
    {
        return null === $this->toNullableString();
    }

    public function toSlug(): string
    {
        return Str::slug($this->toString());
    }
}
