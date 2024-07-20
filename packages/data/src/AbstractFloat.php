<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Illuminate\Database\Eloquent\Model;
use Metatech\Support\Money;

abstract class AbstractFloat extends AbstractValue
{
    public static function fromEloquentModel(Model $model): static
    {
        $instance = new static(value: $model->getAttribute(static::getDatabaseTableColumnName()));
        $instance->setEloquentModel($model)
            ->setItem($model);

        return $instance;
    }

    public static function fromArray(array $item): static
    {
        $instance = new static(value: $item[static::getName()] ?? null);
        $instance->setItem($item);

        return $instance;
    }

    public static function fromFloat(float $value): static
    {
        return new static($value);
    }

    public static function fromInteger(int $value): static
    {
        return new static($value);
    }

    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public static function fromNullableFloat(?float $value): static
    {
        return new static($value);
    }

    public function toNullableFloat(): ?float
    {
        $value = $this->toValue();

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    public function toFloat(): float
    {
        return $this->toNullableFloat() ?? 0;
    }

    public function toNullableInteger(): ?int
    {
        $value = $this->toNullableFloat();

        if (null === $value) {
            return null;
        }

        return (int) $value;
    }

    public function toInteger(): int
    {
        return $this->toNullableInteger() ?? 0;
    }

    public function toPrimitive(): ?float
    {
        return $this->toNullableFloat();
    }

    public function isNull(): bool
    {
        return null === $this->toNullableFloat();
    }

    public function toMoney(): string
    {
        return Money::format($this->toFloat());
    }
}
