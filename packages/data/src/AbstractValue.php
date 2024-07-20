<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use App\Shared\Table;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

abstract class AbstractValue
{
    protected ?string $forEvent = null;

    protected array $onlyKeys = [];

    protected array $exceptKeys = [];

    private ?Model $eloquentModel = null;

    private mixed $item = null;

    final public function __construct(
        protected readonly mixed $value = null,
    ) {
    }

    abstract public static function getName(): string;

    abstract public static function getDatabaseTableColumnName(): string;

    abstract public static function fromEloquentModel(Model $model);

    abstract public static function fromArray(array $item);

    abstract public function isNull(): bool;

    abstract public function toPrimitive(): mixed;

    public static function getCsvColumnName(): string
    {
        return static::getDatabaseTableColumnName();
    }

    public static function getNameForHuman(): string
    {
        return static::getName() . '_for_human';
    }

    public static function fromData(AbstractData $data): static
    {
        return new static(value: $data);
    }

    public static function new(): static
    {
        return new static();
    }

    public static function from($item): static
    {
        if ($item instanceof Model) {
            return static::new()->fromEloquentModel($item);
        }

        if (is_array($item)) {
            return static::new()->fromArray($item);
        }

        return new static(value: $item);
    }

    public static function getDatabaseTableColumnNameWithTable(Table|string|null $table = null): string
    {
        if (null === $table) {
            return static::getDatabaseTableColumnName();
        }

        if ($table instanceof Table) {
            $table = $table->value;
        }

        return "{$table}." . static::getDatabaseTableColumnName();
    }

    public function getEloquentModel(): ?Model
    {
        return $this->eloquentModel;
    }

    public function setEloquentModel(?Model $eloquentModel): static
    {
        $this->eloquentModel = $eloquentModel;

        return $this;
    }

    public function getItem(): mixed
    {
        return $this->item;
    }

    public function setItem(mixed $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function isNotNull(): bool
    {
        return false === $this->isNull();
    }

    public function forEvent(string $name): self
    {
        $this->forEvent = $name;

        return $this;
    }

    public function only(array $keys): self
    {
        $this->onlyKeys = array_merge($this->onlyKeys, $keys);

        return $this;
    }

    public function except(array $keys): self
    {
        $this->exceptKeys = array_merge($this->exceptKeys, $keys);

        return $this;
    }

    public function getExceptKeys(): array
    {
        return $this->exceptKeys;
    }

    public function getOnlyKeys(): array
    {
        return $this->onlyKeys;
    }

    public function toEncrypted(): string
    {
        return encrypt($this->toPrimitive());
    }

    public function toDecrypted(): string
    {
        return decrypt($this->toPrimitive());
    }

    public function toHashed(): string
    {
        return Hash::make($this->toDecrypted());
    }

    public function toValue(): mixed
    {
        if ($this->value instanceof Closure) {
            return ($this->value)();
        }

        return $this->value;
    }

    public function equalsTo(mixed $value): bool
    {
        return $this->toValue() === $value;
    }
}
