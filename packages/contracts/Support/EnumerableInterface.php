<?php

declare(strict_types=1);

namespace Haphp\Contracts\Support;

use CachingIterator;
use Closure;
use Countable;
use Exception;
use Haphp\Support\Collection;
use Haphp\Support\ItemNotFoundException;
use Haphp\Support\MultipleItemsFoundException;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Traversable;
use UnexpectedValueException;

/**
 * @template TKey of array-key
 *
 * @template-covariant TValue
 *
 * @extends ArrayableInterface<TKey, TValue>
 * @extends IteratorAggregate<TKey, TValue>
 */
interface EnumerableInterface extends ArrayableInterface, Countable, IteratorAggregate, JsonableInterface, JsonSerializable
{
    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString();

    /**
     * Dynamically access collection proxies.
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function __get(string $key);

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @template TMakeKey of array-key
     * @template TMakeValue
     *
     * @param  ArrayableInterface<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>|null  $items
     * @return static<TMakeKey, TMakeValue>
     */
    public static function make(ArrayableInterface|array|null $items = []): static;

    /**
     * Create a new instance by invoking the callback a given amount of times.
     */
    public static function times(int $number, ?callable $callback = null): static;

    /**
     * Create a collection with the given range.
     */
    public static function range(int $from, int $to): static;

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @template TWrapValue
     *
     * @param  iterable<array-key, TWrapValue>  $value
     * @return static<array-key, TWrapValue>
     */
    public static function wrap(array $value): static;

    /**
     * Get the underlying items from the given collection if applicable.
     *
     * @template TUnwrapKey of array-key
     * @template TUnwrapValue
     *
     * @param  array<TUnwrapKey, TUnwrapValue>|static<TUnwrapKey, TUnwrapValue>  $value
     * @return array<TUnwrapKey, TUnwrapValue>
     */
    public static function unwrap(EnumerableInterface|array $value): array;

    /**
     * Create a new instance with no items.
     */
    public static function empty(): static;

    /**
     * Add a method to the list of proxied methods.
     */
    public static function proxy(string $method): void;

    /**
     * Get all items in the enumerable.
     */
    public function all(): array;

    /**
     * Alias for the "avg" method.
     *
     * @param  (callable(TValue): float|int)|string|null  $callback
     */
    public function average(callable|int|string|null $callback = null): float|int|null;

    /**
     * Get the median of a given key.
     *
     * @param  string|array<array-key, string>|null  $key
     */
    public function median(array|string|null $key = null): float|int|null;

    /**
     * Get the mode of a given key.
     *
     * @param  string|array<array-key, string>|null  $key
     * @return array<int, float|int>|null
     */
    public function mode(array|string|null $key = null): ?array;

    /**
     * Collapse the items into a single enumerable.
     *
     * @return static<int, mixed>
     */
    public function collapse(): static;

    /**
     * Alias for the "contains" method.
     *
     * @param  (callable(TValue, TKey): bool)|string  $key
     */
    public function some(callable|string $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Determine if an item exists, using strict comparison.
     *
     * @param  (callable(TValue): bool)|array-key  $key
     * @param  TValue|null  $value
     */
    public function containsStrict(callable|int|string $key, $value = null): bool;

    /**
     * Get the average value of a given key.
     *
     * @param  (callable(TValue): float|int)|string|null  $callback
     */
    public function avg(callable|int|string|null $callback = null): float|int|null;

    /**
     * Determine if an item exists in the enumerable.
     *
     * @param  (callable(TValue, TKey): bool)|string  $key
     */
    public function contains(callable|string $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Determine if an item is not contained in the collection.
     */
    public function doesntContain(mixed $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Cross join with the given lists, returning all possible permutations.
     *
     * @template TCrossJoinKey
     * @template TCrossJoinValue
     *
     * @param  ArrayableInterface<TCrossJoinKey, TCrossJoinValue>|iterable<TCrossJoinKey, TCrossJoinValue>  ...$lists
     * @return static<int, array<int, TValue|TCrossJoinValue>>
     */
    public function crossJoin(...$lists): static;

    /**
     * Dump the collection and end the script.
     *
     * @param  mixed  ...$args
     * @return never
     */
    public function dd(...$args);

    /**
     * Dump the collection.
     *
     * @return $this
     */
    public function dump(): static;

    /**
     * Get the items that are not present in the given items.
     *
     * @param  ArrayableInterface<array-key, TValue>|iterable<array-key, TValue>  $items
     */
    public function diff(ArrayableInterface|array $items): static;

    /**
     * Get the items that are not present in the given items, using the callback.
     *
     * @param  ArrayableInterface<array-key, TValue>|iterable<array-key, TValue>  $items
     * @param  callable(TValue, TValue): int  $callback
     */
    public function diffUsing(ArrayableInterface|array $items, callable $callback): static;

    /**
     * Get the items whose keys and values are not present in the given items.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     */
    public function diffAssoc(ArrayableInterface|array $items): static;

    /**
     * Get the items whose keys and values are not present in the given items, using the callback.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     * @param  callable(TKey, TKey): int  $callback
     */
    public function diffAssocUsing(ArrayableInterface|array $items, callable $callback): static;

    /**
     * Get the items whose keys are not present in the given items.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     */
    public function diffKeys(ArrayableInterface|array $items): static;

    /**
     * Get the items whose keys are not present in the given items, using the callback.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     * @param  callable(TKey, TKey): int  $callback
     */
    public function diffKeysUsing(ArrayableInterface|array $items, callable $callback): static;

    /**
     * Retrieve duplicate items.
     *
     * @param  (callable(TValue): bool)|string|null  $callback
     */
    public function duplicates(callable|string|null $callback = null, bool $strict = false): static;

    /**
     * Retrieve duplicate items using strict comparison.
     *
     * @param  (callable(TValue): bool)|string|null  $callback
     */
    public function duplicatesStrict(callable|string|null $callback = null): static;

    /**
     * Execute a callback over each item.
     *
     * @param  callable(TValue, TKey): mixed  $callback
     * @return $this
     */
    public function each(callable $callback): static;

    /**
     * Execute a callback over each nested chunk of items.
     */
    public function eachSpread(callable $callback): static;

    /**
     * Determine if all items pass the given truth test.
     *
     * @param  (callable(TValue, TKey): bool)|string  $key
     */
    public function every(callable|string $key, mixed $operator = null, mixed $value = null): bool;

    /**
     * Get all items except for those with the specified keys.
     *
     * @param  EnumerableInterface<array-key, TKey>|array<array-key, TKey>  $keys
     */
    public function except(EnumerableInterface|array $keys): static;

    /**
     * Run a filter over each of the items.
     *
     * @param  (callable(TValue): bool)|null  $callback
     */
    public function filter(?callable $callback = null): static;

    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TWhenReturnType as null
     *
     * @param  (callable($this): TWhenReturnType)|null  $callback
     * @param  (callable($this): TWhenReturnType)|null  $default
     * @return $this|TWhenReturnType
     */
    public function when(bool $value, ?callable $callback = null, ?callable $default = null);

    /**
     * Apply the callback if the collection is empty.
     *
     * @template TWhenEmptyReturnType
     *
     * @param  (callable($this): TWhenEmptyReturnType)  $callback
     * @param  (callable($this): TWhenEmptyReturnType)|null  $default
     * @return $this|TWhenEmptyReturnType
     */
    public function whenEmpty(callable $callback, ?callable $default = null);

    /**
     * Apply the callback if the collection is not empty.
     *
     * @template TWhenNotEmptyReturnType
     *
     * @param  callable($this): TWhenNotEmptyReturnType  $callback
     * @param  (callable($this): TWhenNotEmptyReturnType)|null  $default
     * @return $this|TWhenNotEmptyReturnType
     */
    public function whenNotEmpty(callable $callback, ?callable $default = null);

    /**
     * Apply the callback if the given "value" is (or resolves to) truthy.
     *
     * @template TUnlessReturnType
     *
     * @param  (callable($this): TUnlessReturnType)  $callback
     * @param  (callable($this): TUnlessReturnType)|null  $default
     * @return $this|TUnlessReturnType
     */
    public function unless(bool $value, callable $callback, ?callable $default = null);

    /**
     * Apply the callback unless the collection is empty.
     *
     * @template TUnlessEmptyReturnType
     *
     * @param  callable($this): TUnlessEmptyReturnType  $callback
     * @param  (callable($this): TUnlessEmptyReturnType)|null  $default
     * @return $this|TUnlessEmptyReturnType
     */
    public function unlessEmpty(callable $callback, ?callable $default = null);

    /**
     * Apply the callback unless the collection is not empty.
     *
     * @template TUnlessNotEmptyReturnType
     *
     * @param  callable($this): TUnlessNotEmptyReturnType  $callback
     * @param  (callable($this): TUnlessNotEmptyReturnType)|null  $default
     * @return $this|TUnlessNotEmptyReturnType
     */
    public function unlessNotEmpty(callable $callback, ?callable $default = null);

    /**
     * Filter items by the given key value pair.
     */
    public function where(string $key, mixed $operator = null, mixed $value = null): static;

    /**
     * Filter items where the value for the given key is null.
     */
    public function whereNull(?string $key = null): static;

    /**
     * Filter items where the value for the given key is not null.
     */
    public function whereNotNull(?string $key = null): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     */
    public function whereStrict(string $key, mixed $value): static;

    /**
     * Filter items by the given key value pair.
     */
    public function whereIn(string $key, iterable|ArrayableInterface $values, bool $strict = false): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     */
    public function whereInStrict(string $key, iterable|ArrayableInterface $values): static;

    /**
     * Filter items such that the value of the given key is between the given values.
     */
    public function whereBetween(string $key, iterable|ArrayableInterface $values): static;

    /**
     * Filter items such that the value of the given key is not between the given values.
     */
    public function whereNotBetween(string $key, iterable|ArrayableInterface $values): static;

    /**
     * Filter items by the given key value pair.
     */
    public function whereNotIn(string $key, iterable|ArrayableInterface $values, bool $strict = false): static;

    /**
     * Filter items by the given key value pair using strict comparison.
     */
    public function whereNotInStrict(string $key, iterable|ArrayableInterface $values): static;

    /**
     * Filter the items, removing any items that don't match the given type(s).
     *
     * @template TWhereInstanceOf
     *
     * @param  class-string<TWhereInstanceOf>|array<array-key, class-string<TWhereInstanceOf>>  $type
     * @return static<TKey, TWhereInstanceOf>
     */
    public function whereInstanceOf(array|string $type): static;

    /**
     * Get the first item from enumerable passing the given truth test.
     *
     * @template TFirstDefault
     *
     * @param  (callable(TValue,TKey): bool)|null  $callback
     * @param  (Closure(): TFirstDefault)|null  $default
     * @return TValue|TFirstDefault
     */
    public function first(?callable $callback = null, ?Closure $default = null);

    /**
     * Get the first item by the given key value pair.
     *
     * @return TValue|null
     */
    public function firstWhere(string $key, mixed $operator = null, mixed $value = null);

    /**
     * Get a flattened array of the items in the collection.
     */
    public function flatten(float|int $depth = INF): static;

    /**
     * Flip the values with their keys.
     *
     * @return static<TValue, TKey>
     */
    public function flip(): static;

    /**
     * Get an item from the collection by key.
     *
     * @template TGetDefault
     *
     * @param  TKey  $key
     * @param  (Closure(): TGetDefault)|null  $default
     * @return TValue|TGetDefault
     */
    public function get($key, ?Closure $default = null);

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param  (callable(TValue, TKey): array-key)|array|string  $groupBy
     * @return static<array-key, static<array-key, TValue>>
     */
    public function groupBy(callable|array|string $groupBy, bool $preserveKeys = false): static;

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  (callable(TValue, TKey): array-key)|array|string  $keyBy
     * @return static<array-key, TValue>
     */
    public function keyBy(callable|array|string $keyBy): static;

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  TKey|array<array-key, TKey>  $key
     */
    public function has($key): bool;

    /**
     * Determine if any of the keys exist in the collection.
     */
    public function hasAny(mixed $key): bool;

    /**
     * Concatenate values of a given key as a string.
     */
    public function implode(callable|string $value, ?string $glue = null): string;

    /**
     * Intersect the collection with the given items.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     */
    public function intersect(ArrayableInterface|array $items): static;

    /**
     * Intersect the collection with the given items by key.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     */
    public function intersectByKeys(ArrayableInterface|array $items): static;

    /**
     * Determine if the collection is empty or not.
     */
    public function isEmpty(): bool;

    /**
     * Determine if the collection is not empty.
     */
    public function isNotEmpty(): bool;

    /**
     * Determine if the collection contains a single item.
     */
    public function containsOneItem(): bool;

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     */
    public function join(string $glue, string $finalGlue = ''): string;

    /**
     * Get the keys of the collection items.
     *
     * @return static<int, TKey>
     */
    public function keys(): static;

    /**
     * Get the last item from the collection.
     *
     * @template TLastDefault
     *
     * @param  (callable(TValue, TKey): bool)|null  $callback
     * @param  (Closure(): TLastDefault)|null  $default
     * @return TValue|TLastDefault
     */
    public function last(?callable $callback = null, ?Closure $default = null);

    /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TValue, TKey): TMapValue  $callback
     * @return static<TKey, TMapValue>
     */
    public function map(callable $callback): static;

    /**
     * Run a map over each nested chunk of items.
     */
    public function mapSpread(callable $callback): static;

    /**
     * Run a dictionary map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapToDictionaryKey of array-key
     * @template TMapToDictionaryValue
     *
     * @param  callable(TValue, TKey): array<TMapToDictionaryKey, TMapToDictionaryValue>  $callback
     * @return static<TMapToDictionaryKey, array<int, TMapToDictionaryValue>>
     */
    public function mapToDictionary(callable $callback): static;

    /**
     * Run a grouping map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapToGroupsKey of array-key
     * @template TMapToGroupsValue
     *
     * @param  callable(TValue, TKey): array<TMapToGroupsKey, TMapToGroupsValue>  $callback
     * @return static<TMapToGroupsKey, static<int, TMapToGroupsValue>>
     */
    public function mapToGroups(callable $callback): static;

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(TValue, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return static<TMapWithKeysKey, TMapWithKeysValue>
     */
    public function mapWithKeys(callable $callback): static;

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @template TFlatMapKey of array-key
     * @template TFlatMapValue
     *
     * @param  callable(TValue, TKey): (Collection<TFlatMapKey, TFlatMapValue>|array<TFlatMapKey, TFlatMapValue>)  $callback
     * @return static<TFlatMapKey, TFlatMapValue>
     */
    public function flatMap(callable $callback): static;

    /**
     * Map the values into a new class.
     *
     * @template TMapIntoValue
     *
     * @param  class-string<TMapIntoValue>  $class
     * @return static<TKey, TMapIntoValue>
     */
    public function mapInto(string $class): static;

    /**
     * Merge the collection with the given items.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     */
    public function merge(ArrayableInterface|array $items): static;

    /**
     * Recursively merge the collection with the given items.
     *
     * @template TMergeRecursiveValue
     *
     * @param  ArrayableInterface<TKey, TMergeRecursiveValue>|iterable<TKey, TMergeRecursiveValue>  $items
     * @return static<TKey, TValue|TMergeRecursiveValue>
     */
    public function mergeRecursive(ArrayableInterface|array $items): static;

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @template TCombineValue
     *
     * @param  ArrayableInterface<array-key, TCombineValue>|iterable<array-key, TCombineValue>  $values
     * @return static<TValue, TCombineValue>
     */
    public function combine(ArrayableInterface|array $values): static;

    /**
     * Union the collection with the given items.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     */
    public function union(ArrayableInterface|array $items): static;

    /**
     * Get the min value of a given key.
     *
     * @param  (callable(TValue):mixed)|string|null  $callback
     */
    public function min(callable|string|null $callback = null): mixed;

    /**
     * Get the max value of a given key.
     *
     * @param  (callable(TValue):mixed)|string|null  $callback
     */
    public function max(callable|string|null $callback = null): mixed;

    /**
     * Create a new collection consisting of every n-th element.
     */
    public function nth(int $step, int $offset = 0): static;

    /**
     * Get the items with the specified keys.
     *
     * @param  string|EnumerableInterface<array-key, TKey>|array<array-key, TKey>  $keys
     */
    public function only(EnumerableInterface|array|string $keys): static;

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param  (callable(TValue, TKey): bool)|string  $key
     * @return static<int<0, 1>, static<TKey, TValue>>
     */
    public function partition(callable|string $key, mixed $operator = null, mixed $value = null): static;

    /**
     * Push all the given items onto the collection.
     *
     * @param  iterable<array-key, TValue>  $source
     */
    public function concat(array $source): static;

    /**
     * Get one or a specified number of items randomly from the collection.
     *
     * @return static<int, TValue>|TValue
     *
     * @throws InvalidArgumentException
     */
    public function random(?int $number = null);

    /**
     * Reduce the collection to a single value.
     *
     * @template TReduceInitial
     * @template TReduceReturnType
     *
     * @param  callable(TReduceInitial|TReduceReturnType, TValue, TKey): TReduceReturnType  $callback
     * @param  TReduceInitial  $initial
     * @return TReduceReturnType
     */
    public function reduce(callable $callback, $initial = null);

    /**
     * Reduce the collection to multiple aggregate values.
     *
     * @param  mixed  ...$initial
     *
     * @throws UnexpectedValueException
     */
    public function reduceSpread(callable $callback, ...$initial): array;

    /**
     * Replace the collection items with the given items.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     */
    public function replace(ArrayableInterface|array $items): static;

    /**
     * Recursively replace the collection items with the given items.
     *
     * @param  ArrayableInterface<TKey, TValue>|iterable<TKey, TValue>  $items
     */
    public function replaceRecursive(ArrayableInterface|array $items): static;

    /**
     * Reverse items order.
     */
    public function reverse(): static;

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param  callable(TValue,TKey): bool  $value
     * @return TKey|bool
     */
    public function search(callable $value, bool $strict = false);

    /**
     * Shuffle the items in the collection.
     */
    public function shuffle(?int $seed = null): static;

    /**
     * Create chunks representing a "sliding window" view of the items in the collection.
     *
     * @return static<int, static>
     */
    public function sliding(int $size = 2, int $step = 1): static;

    /**
     * Skip the first {$count} items.
     */
    public function skip(int $count): static;

    /**
     * Skip items in the collection until the given condition is met.
     *
     * @param  callable(TValue,TKey): bool  $value
     */
    public function skipUntil(callable $value): static;

    /**
     * Skip items in the collection while the given condition is met.
     *
     * @param  callable(TValue,TKey): bool  $value
     */
    public function skipWhile(callable $value): static;

    /**
     * Get a slice of items from the enumerable.
     */
    public function slice(int $offset, ?int $length = null): static;

    /**
     * Split a collection into a certain number of groups.
     *
     * @return static<int, static>
     */
    public function split(int $numberOfGroups): static;

    /**
     * Get the first item in the collection, but only if exactly one item exists. Otherwise, throw an exception.
     *
     * @param  (callable(TValue, TKey): bool)|string|null  $key
     * @return TValue
     *
     * @throws ItemNotFoundException
     * @throws MultipleItemsFoundException
     */
    public function sole(callable|string|null $key = null, mixed $operator = null, mixed $value = null);

    /**
     * Get the first item in the collection but throw an exception if no matching items exist.
     *
     * @param  (callable(TValue, TKey): bool)|string|null  $key
     * @return TValue
     *
     * @throws ItemNotFoundException
     */
    public function firstOrFail(callable|string|null $key = null, mixed $operator = null, mixed $value = null);

    /**
     * Chunk the collection into chunks of the given size.
     *
     * @return static<int, static>
     */
    public function chunk(int $size): static;

    /**
     * Chunk the collection into chunks with a callback.
     *
     * @param  callable(TValue, TKey, static<int, TValue>): bool  $callback
     * @return static<int, static<int, TValue>>
     */
    public function chunkWhile(callable $callback): static;

    /**
     * Split a collection into a certain number of groups, and fill the first groups completely.
     *
     * @return static<int, static>
     */
    public function splitIn(int $numberOfGroups): static;

    /**
     * Sort through each item with a callback.
     *
     * @param  (callable(TValue, TValue): int)|int|null  $callback
     */
    public function sort(callable|int|null $callback = null): static;

    /**
     * Sort items in descending order.
     */
    public function sortDesc(int $options = SORT_REGULAR): static;

    /**
     * Sort the collection using the given callback.
     *
     * @param  (callable(TValue, TKey): mixed)|string|array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>  $callback
     */
    public function sortBy(array|callable|string $callback, int $options = SORT_REGULAR, bool $descending = false): static;

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param  (callable(TValue, TKey): mixed)|string|array<array-key, (callable(TValue, TValue): mixed)|(callable(TValue, TKey): mixed)|string|array{string, string}>  $callback
     */
    public function sortByDesc(array|callable|string $callback, int $options = SORT_REGULAR): static;

    /**
     * Sort the collection keys.
     */
    public function sortKeys(int $options = SORT_REGULAR, bool $descending = false): static;

    /**
     * Sort the collection keys in descending order.
     */
    public function sortKeysDesc(int $options = SORT_REGULAR): static;

    /**
     * Sort the collection keys using a callback.
     *
     * @param  callable(TKey, TKey): int  $callback
     */
    public function sortKeysUsing(callable $callback): static;

    /**
     * Get the sum of the given values.
     *
     * @param  (callable(TValue): mixed)|string|null  $callback
     */
    public function sum(callable|string|null $callback = null): mixed;

    /**
     * Take the first or last {$limit} items.
     */
    public function take(int $limit): static;

    /**
     * Take items in the collection until the given condition is met.
     *
     * @param  callable(TValue,TKey): bool  $value
     */
    public function takeUntil(callable $value): static;

    /**
     * Take items in the collection while the given condition is met.
     *
     * @param  callable(TValue,TKey): bool  $value
     */
    public function takeWhile(callable $value): static;

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @param  callable(TValue): mixed  $callback
     * @return $this
     */
    public function tap(callable $callback): static;

    /**
     * Pass the enumerable to the given callback and return the result.
     *
     * @template TPipeReturnType
     *
     * @param  callable($this): TPipeReturnType  $callback
     * @return TPipeReturnType
     */
    public function pipe(callable $callback);

    /**
     * Pass the collection into a new class.
     *
     * @template TPipeIntoValue
     *
     * @param  class-string<TPipeIntoValue>  $class
     * @return TPipeIntoValue
     */
    public function pipeInto(string $class);

    /**
     * Pass the collection through a series of callable pipes and return the result.
     *
     * @param  array<callable>  $pipes
     */
    public function pipeThrough(array $pipes): mixed;

    /**
     * Get the values of a given key.
     *
     * @param  string|array<array-key, string>  $value
     * @return static<int, mixed>
     */
    public function pluck(array|string $value, ?string $key = null): static;

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param  (callable(TValue, TKey): bool)|bool  $callback
     */
    public function reject(callable|bool $callback = true): static;

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     */
    public function undot(): static;

    /**
     * Return only unique items from the collection array.
     *
     * @param  (callable(TValue, TKey): mixed)|string|null  $key
     */
    public function unique(callable|string|null $key = null, bool $strict = false): static;

    /**
     * Return only unique items from the collection array using strict comparison.
     *
     * @param  (callable(TValue, TKey): mixed)|string|null  $key
     */
    public function uniqueStrict(callable|string|null $key = null): static;

    /**
     * Reset the keys on the underlying array.
     *
     * @return static<int, TValue>
     */
    public function values(): static;

    /**
     * Pad collection to the specified length with a value.
     *
     * @template TPadValue
     *
     * @param  TPadValue  $value
     * @return static<int, TValue|TPadValue>
     */
    public function pad(int $size, $value): static;

    /**
     * Get the values iterator.
     *
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable;

    /**
     * Count the number of items in the collection.
     */
    public function count(): int;

    /**
     * Count the number of items in the collection by a field or using a callback.
     *
     * @param  (callable(TValue, TKey): array-key)|string|null  $countBy
     * @return static<array-key, int>
     */
    public function countBy(callable|string|null $countBy = null): static;

    /**
     * Zip the collection together with one or more arrays.
     *
     * E.g., new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @template TZipValue
     *
     * @param  ArrayableInterface<array-key, TZipValue>  ...$items
     * @return static<int, static<int, TValue|TZipValue>>
     */
    public function zip(array|ArrayableInterface ...$items): static;

    /**
     * Collect the values into a collection.
     *
     * @return Collection<TKey, TValue>
     */
    public function collect(): Collection;

    /**
     * Get the collection of items as a plain array.
     *
     * @return array<TKey, mixed>
     */
    public function toArray(): array;

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): mixed;

    /**
     * Get the collection of items as JSON.
     */
    public function toJson(int $options = 0): string;

    /**
     * Get a CachingIterator instance.
     */
    public function getCachingIterator(int $flags = CachingIterator::CALL_TOSTRING): CachingIterator;

    /**
     * Indicate that the model's string representation should be escaped when __toString is invoked.
     *
     * @return $this
     */
    public function escapeWhenCastingToString(bool $escape = true): static;
}
