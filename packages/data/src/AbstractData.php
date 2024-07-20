<?php

declare(strict_types=1);

namespace Haphp\Data;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Metatech\Support\Abstracts\AbstractValue;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;

abstract class AbstractData
{
    private ?Model $eloquentModel = null;

    private ?string $morphAlias = null;

    private bool $existsInDatabase = false;

    abstract public function getPrimaryKey(): AbstractValue;

    public static function fake(array $attributes = [], array $with = [], bool $persist = true): static
    {
        $factory = static::getFactory();

        if (true === $persist) {
            $model = $factory->create($attributes);

            return self::fromEloquentModel($model->load($with))->setExistsInDatabase(true);
        }

        $model = $factory->make($attributes);

        return self::fromEloquentModel($model);
    }

    public static function fakeCollection(array $attributes = [], int $count = 2, array $with = [], bool $persist = true): Collection
    {
        $factory = static::getFactory();

        if (true === $persist) {
            $models = $factory->count($count)->create($attributes);

            return self::collect($models);
        }

        $models = $factory->count($count)->make($attributes);

        return self::collect($models);
    }

    public static function new(): static
    {
        $class = new ReflectionClass(static::class);

        $properties = collect($class->getProperties())
            ->mapWithKeys(function (ReflectionProperty $property): array {
                /** @var ReflectionNamedType $type */
                $type = $property->getType();

                /** @var AbstractValue $name */
                $name = $type->getName();

                return [$property->name => $name::new()];
            })->toArray();

        return new static(...$properties);
    }

    public static function fromArray(array $item): static
    {
        $class = new ReflectionClass(static::class);

        $properties = collect($class->getProperties())
            ->mapWithKeys(function (ReflectionProperty $property) use ($item): array {
                /** @var ReflectionNamedType $type */
                $type = $property->getType();

                /** @var AbstractValue $name */
                $name = $type->getName();

                return [$property->name => $name::fromArray($item)];
            })->toArray();

        return new static(...$properties);
    }

    public static function fromEloquentModel(?Model $model): static
    {
        if (null === $model) {
            return static::new();
        }

        $class = new ReflectionClass(static::class);

        $properties = collect($class->getProperties())
            ->mapWithKeys(function (ReflectionProperty $property) use ($model): array {
                /** @var ReflectionNamedType $type */
                $type = $property->getType();

                /** @var AbstractValue $name */
                $name = $type->getName();

                return [$property->name => $name::fromEloquentModel($model)];
            })->toArray();

        return (new static(...$properties))
            ->setEloquentModel($model)
            ->setMorphAlias($model->getMorphClass());
    }

    public static function fromData(AbstractData $data, string $method = 'fromData', mixed $options = null): static
    {
        $class = new ReflectionClass(static::class);

        $properties = collect($class->getProperties())
            ->mapWithKeys(function (ReflectionProperty $property) use ($data, $method, $options): array {
                /** @var ReflectionNamedType $type */
                $type = $property->getType();

                /** @var AbstractValue $name */
                $name = $type->getName();

                if (false === method_exists($name, $method)) {
                    return [$property->name => $name::new()];
                }

                return [$property->name => $name::$method($data, $options)];
            })->toArray();

        return new static(...$properties);
    }

    public static function collect(Collection|array $items): Collection
    {
        if (is_array($items)) {
            $items = collect($items);
        }

        return $items->map(fn (mixed $item) => static::from($item));
    }

    public static function from($item): static
    {
        if ($item instanceof Model) {
            return static::fromEloquentModel($item);
        }

        if (is_array($item)) {
            return static::fromArray($item);
        }

        if ($item instanceof AbstractData) {
            return static::fromData($item);
        }

        return static::new();
    }

    public static function getFactory(): Factory
    {
        throw new RuntimeException('The getFactory method must be implemented.');
    }

    public function getEloquentModel(): ?Model
    {
        return $this->eloquentModel;
    }

    public function setEloquentModel(Model $eloquentModel): static
    {
        $this->eloquentModel = $eloquentModel;

        return $this;
    }

    public function isNull(): bool
    {
        return $this->getPrimaryKey()->isNull();
    }

    public function isNotNull(): bool
    {
        return false === $this->isNull();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function toArray(): array
    {
        return [];
    }

    public function setExistsInDatabase(bool $existsInDatabase): AbstractData
    {
        $this->existsInDatabase = $existsInDatabase;

        return $this;
    }

    public function existsInDatabase(): bool
    {
        return $this->existsInDatabase;
    }

    public function notExistsInDatabase(): bool
    {
        return false === $this->existsInDatabase();
    }

    public function forDatabase(): array
    {
        return [];
    }

    public function forDatabaseCreate(): array
    {
        return [];
    }

    public function forDatabaseUpdate(): array
    {
        return [];
    }

    public function isDirty(): bool
    {
        return false;
    }

    public function toArrayForUser(): array
    {
        return [];
    }

    public function getMorphAlias(): ?string
    {
        return $this->morphAlias;
    }

    public function setMorphAlias(string $morphAlias): AbstractData
    {
        $this->morphAlias = $morphAlias;

        return $this;
    }

    public function toEncryptedArray(): array
    {
        return [];
    }

    public function forResource(): array
    {
        return [];
    }

    public function toResource(): JsonResource
    {
        throw new RuntimeException('The toResource method must be implemented.');
    }
}
