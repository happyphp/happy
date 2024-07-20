<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractBoolean extends AbstractValue
{
    abstract public function toBoolean(): bool;

    public static function fromEloquentModel(Model $model): static
    {
        return new static(value: $model->getAttributes()[static::getDatabaseTableColumnName()] ?? null);
    }

    public static function fromArray(array $item): static
    {
        return new static(value: $item[static::getName()] ?? null);
    }

    public static function fromBoolean(bool $value): static
    {
        return new static($value);
    }

    public static function fromString(string $value): static
    {
        return new static(value: $value);
    }

    public static function true(): static
    {
        return new static(value: true);
    }

    public static function false(): static
    {
        return new static(value: false);
    }

    public function toNullableBoolean(): ?bool
    {
        $value = $this->toValue();

        if (is_bool($value)) {
            return $value;
        }

        if ('1' === $value || 1 === $value || 'true' === $value || 'yes' === $value || 'on' === $value) {
            return true;
        }

        if ('0' === $value || 0 === $value || 'false' === $value || 'no' === $value || 'off' === $value) {
            return false;
        }

        return null;
    }

    public function toPrimitive(): ?bool
    {
        return $this->toNullableBoolean();
    }

    public function isNull(): bool
    {
        return null === $this->toNullableBoolean();
    }

    public function isTrue(): bool
    {
        return true === $this->toBoolean();
    }

    public function isFalse(): bool
    {
        return false === $this->toBoolean();
    }
}
