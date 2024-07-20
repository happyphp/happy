<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;

abstract class AbstractCollection extends AbstractValue
{
    public static function fromEloquentModel(Model $model): static
    {
        $value = $model->getAttributes()[static::getDatabaseTableColumnName()] ?? null;

        if (is_string($value) && ! empty($value)) {
            $properties = json_decode($value, true);

            if (is_array($properties) && count($properties) > 0) {
                return new static(collect($properties));
            }
        }

        if (is_array($value) && count($value) > 0) {
            return new static(collect($value));
        }

        if ($value instanceof Collection) {
            return new static($value);
        }

        return self::new();
    }

    public static function fromArray(array $item): static
    {
        $value = $item[static::getName()] ?? null;

        if (is_array($value)) {
            return new static(collect($value));
        }

        if ($value instanceof Collection) {
            return new static($value);
        }

        return self::new();
    }

    public static function fromCollection(Collection $collection): static
    {
        return new static($collection);
    }

    public function toNullableCollection(): ?Collection
    {
        $value = $this->toValue();

        if ($value instanceof Collection && $value->isNotEmpty()) {
            return $value;
        }

        if (is_array($value) && count($value) > 0) {
            return collect($value);
        }

        return null;
    }

    public function toCollection(): Collection
    {
        return $this->toNullableCollection() ?? collect();
    }

    public function toArray(): array
    {
        return $this->toCollection()
            ->map(function (mixed $item) {
                if ((is_string($item) || is_object($item)) && method_exists($item, 'toArray')) {
                    return $item->toArray();
                }

                return $item;
            })
            ->toArray();
    }

    public function toNullableArray(): ?array
    {
        $array = $this->toArray();

        if (empty($array)) {
            return null;
        }

        return $array;
    }

    public function toConditionalArray(Request $request): MissingValue|array
    {
        $except = $request->query('except', []);

        if (in_array(static::getName(), $except)) {
            return new MissingValue();
        }

        $only = $request->query('only');

        if (is_array($only)) {
            if (in_array(static::getName(), $only)) {
                return $this->toArray();
            }

            return new MissingValue();
        }

        return $this->toArray();
    }

    public function toConditionalNullableArray(Request $request): MissingValue|array|null
    {
        $except = $request->query('except', []);

        if (in_array(static::getName(), $except)) {
            return new MissingValue();
        }

        $only = $request->query('only');

        if (is_array($only)) {
            if (in_array(static::getName(), $only)) {
                return $this->toArray();
            }

            return new MissingValue();
        }

        return $this->toNullableArray();
    }

    public function toJson($options = 0): string
    {
        return $this->toCollection()->toJson($options);
    }

    public function toPrimitive(): ?array
    {
        return $this->toNullableCollection()?->toArray();
    }

    public function isNull(): bool
    {
        return null === $this->toNullableCollection();
    }
}
