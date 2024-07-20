<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

abstract class AbstractDataValue extends AbstractValue
{
    abstract public function toNullableData(): mixed;

    abstract public function toData(): mixed;

    public static function fromClosure(Closure $value): static
    {
        return new static($value);
    }

    public function isNull(): bool
    {
        return null === $this->toNullableData();
    }

    public function toPrimitive(): ?array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        $data = $this->toData();

        if ($data instanceof AbstractData) {
            return $data->toArray();
        }

        return [];
    }

    public function toNullableArray(): ?array
    {
        $data = $this->toNullableData();

        if ($data instanceof AbstractData) {
            return $data->toArray();
        }

        return null;
    }

    public function toNullableArrayForUser(): ?array
    {
        $data = $this->toNullableData();

        if ($data instanceof AbstractData) {
            return $data->toArrayForUser();
        }

        return null;
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

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function toNullableJson(): ?string
    {
        $value = $this->toNullableArray();

        if (null === $value) {
            return null;
        }

        return json_encode($this->toArray());
    }

    public function toResource(): JsonResource
    {
        return $this->toData()->toResource();
    }

    public function toNullableResource(): ?JsonResource
    {
        return $this->toNullableData()?->toResource();
    }
}
