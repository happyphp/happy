<?php

declare(strict_types=1);

namespace Metatech\Support\Abstracts;

use Carbon\CarbonImmutable;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

abstract class AbstractDate extends AbstractValue
{
    public ?string $timezone = null;

    public static function fromCarbon(Carbon $date): static
    {
        return new static($date);
    }

    public static function fromNullableCarbon(?Carbon $date): static
    {
        return new static($date);
    }

    public static function fromString(string $date): static
    {
        try {
            return new static(Carbon::parse($date));
        } catch (Exception) {
            return new static();
        }
    }

    public static function fromTimestamp(float|int|string $timestamp): static
    {
        return new static(Carbon::createFromTimestamp($timestamp));
    }

    public static function fromInteger(int $date): static
    {
        return static::fromString((string) $date);
    }

    public static function fromNullableInteger(?int $date): static
    {
        if (null === $date) {
            return new static();
        }

        return static::fromString((string) $date);
    }

    public static function fromStringOrInteger(string|int $date): static
    {
        if (empty($date)) {
            return new static();
        }

        if (is_string($date)) {
            return static::fromString($date);
        }

        return static::fromInteger($date);
    }

    public static function fromEloquentModel(Model $model): static
    {
        $date = $model->getAttributes()[static::getDatabaseTableColumnName()] ?? null;

        if ($date instanceof Carbon || $date instanceof CarbonImmutable) {
            return new static(value: $date);
        }

        if (is_string($date) || $date instanceof DateTime) {
            return new static(value: Carbon::parse($date));
        }

        return new static();
    }

    public static function fromArray(array $item): static
    {
        $date = $item[static::getName()] ?? $item[static::getName() . '_format_a'] ?? $item[static::getName() . '_format_b'] ?? $item[static::getName() . '_format_c'] ?? $item[static::getName() . '_format_d'] ?? null;

        if ($date instanceof Carbon || $date instanceof CarbonImmutable) {
            return new static(value: $date);
        }

        if (is_string($date) || $date instanceof DateTime) {
            return new static(value: Carbon::parse($date));
        }

        return new static();
    }

    public static function now(DateTimeZone|null|string $tz = null): static
    {
        return new static(value: now($tz));
    }

    public function toNullableCarbon(): Carbon|CarbonImmutable|null
    {
        $value = $this->toValue();

        if ($value instanceof Carbon || $value instanceof CarbonImmutable) {
            return $value->setTimezone($this->getTimezone());
        }

        if (is_string($value)) {
            return Carbon::parse($value)->setTimezone($this->getTimezone());
        }

        return $value;
    }

    public function toCarbon(): Carbon|CarbonImmutable
    {
        return $this->toNullableCarbon() ?? Carbon::now();
    }

    public function toPrimitive(): ?string
    {
        return $this->toNullableCarbon()?->toAtomString();
    }

    public function isNull(): bool
    {
        return null === $this->toNullableCarbon();
    }

    public function getTimezone(): string
    {
        if (null !== $this->timezone) {
            return $this->timezone;
        }

        return config('app.timezone');
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function forDatabase(): static
    {
        $this->timezone = config('app.timezone');

        return $this;
    }

    public function forUser(): static
    {
        if (true === auth()->check()) {
            $this->timezone = auth()->user()->timezone;
        }

        return $this;
    }

    public function toDateString(): string
    {
        return $this->toCarbon()->toDateString();
    }

    public function toNullableDateTimeString(): ?string
    {
        return $this->toNullableCarbon()?->toDateTimeString();
    }

    public function toNullableDateString(): ?string
    {
        return $this->toNullableCarbon()?->toDateString();
    }

    public function toDateTimeString(): string
    {
        return $this->toCarbon()->toDateTimeString();
    }

    public function format(string $format): string
    {
        return $this->toCarbon()->format($format);
    }

    public function toNullableDateHourMinuteString(): string
    {
        return $this->toNullableCarbon()->format('Y-m-d H:i');
    }

    public function toDateHourMinuteString(): string
    {
        return $this->toCarbon()->format('Y-m-d H:i');
    }
}
