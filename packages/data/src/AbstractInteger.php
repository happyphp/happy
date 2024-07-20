<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractInteger extends AbstractValue
{
    public static function fromEloquentModel(Model $model): static
    {
        $instance = new static(value: $model->getAttributes()[static::getDatabaseTableColumnName()] ?? null);
        $instance->setEloquentModel($model)
            ->setItem($model);

        return $instance;
    }

    public static function fromArray(array $item): static
    {
        return new static(value: $item[static::getName()] ?? null);
    }

    public static function fromInteger(int $value): static
    {
        return new static($value);
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public static function fromNullableInteger(?int $value): static
    {
        return new static($value);
    }

    public function toNullableInteger(): ?int
    {
        $value = $this->toValue();

        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    public function toInteger(): int
    {
        return $this->toNullableInteger() ?? 0;
    }

    public function toPrimitive(): ?int
    {
        return $this->toNullableInteger();
    }

    public function isNull(): bool
    {
        return null === $this->toNullableInteger();
    }
}
