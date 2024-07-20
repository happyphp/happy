<?php

declare(strict_types=1);

namespace Haphp\Session;

use Closure;
use Haphp\Contracts\Session\ExistenceAwareInterface;
use Haphp\Contracts\Session\SessionHandlerInterface;
use Haphp\Contracts\Session\SessionInterface;
use Haphp\Http\Request;
use Haphp\Support\Arr;
use Haphp\Support\MessageBag;
use Haphp\Support\Str;
use Haphp\Support\Traits\Macroable;
use Haphp\Support\ViewErrorBag;
use stdClass;

class Store implements SessionInterface
{
    use Macroable;

    /**
     * The session attributes.
     */
    protected array $attributes = [];

    /**
     * Session store started status.
     */
    protected bool $started = false;

    /**
     * Create a new session instance.
     *
     * @return void
     */
    public function __construct(
        protected string $name,
        protected SessionHandlerInterface $handler,
        protected ?string $id = null,
        protected string $serialization = 'php'
    ) {
        $this->setId($id);
    }

    /**
     * Start the session, reading the data from a handler.
     */
    public function start(): bool
    {
        $this->loadSession();

        if (! $this->has('_token')) {
            $this->regenerateToken();
        }

        return $this->started = true;
    }

    /**
     * Save the session data to storage.
     */
    public function save(): void
    {
        $this->ageFlashData();

        $this->prepareErrorBagForSerialization();

        $this->handler->write($this->getId(), $this->prepareForStorage(
            $this->serialization === 'json' ? json_encode($this->attributes) : serialize($this->attributes)
        ));

        $this->started = false;
    }

    /**
     * Age the flash data for the session.
     */
    public function ageFlashData(): void
    {
        $this->forget($this->get('_flash.old', []));

        $this->put('_flash.old', $this->get('_flash.new', []));

        $this->put('_flash.new', []);
    }

    /**
     * Get all the session data.
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Get a subset of the session data.
     */
    public function only(array $keys): array
    {
        return Arr::only($this->attributes, $keys);
    }

    /**
     * Checks if a key exists.
     */
    public function exists(string|array $key): bool
    {
        $placeholder = new stdClass();

        return ! collect(is_array($key) ? $key : func_get_args())->contains(fn ($key) => $this->get($key, $placeholder) === $placeholder);
    }

    /**
     * Determine if the given key is missing from the session data.
     */
    public function missing(array|string $key): bool
    {
        return ! $this->exists($key);
    }

    /**
     * Checks if a key is present and not null.
     */
    public function has(array|string $key): bool
    {
        return ! collect(is_array($key) ? $key : func_get_args())->contains(fn ($key) => $this->get($key) === null);
    }

    /**
     * Get an item from the session.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $key, $default);
    }

    /**
     * Get the value of a given key and then forget it.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        return Arr::pull($this->attributes, $key, $default);
    }

    /**
     * Determine if the session contains old input.
     */
    public function hasOldInput(?string $key = null): bool
    {
        $old = $this->getOldInput($key);

        return $key === null ? count($old) > 0 : $old !== null;
    }

    /**
     * Get the requested item from the flashed input array.
     */
    public function getOldInput(?string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->get('_old_input', []), $key, $default);
    }

    /**
     * Replace the given session attributes entirely.
     */
    public function replace(array $attributes): void
    {
        $this->put($attributes);
    }

    /**
     * Put a key / value pair or array of key / value pairs in the session.
     */
    public function put(array|string $key, mixed $value = null): void
    {
        if (! is_array($key)) {
            $key = [$key => $value];
        }

        foreach ($key as $arrayKey => $arrayValue) {
            Arr::set($this->attributes, $arrayKey, $arrayValue);
        }
    }

    /**
     * Get an item from the session, or store the default value.
     */
    public function remember(string $key, Closure $callback): mixed
    {
        if (null !== ($value = $this->get($key))) {
            return $value;
        }

        return tap($callback(), function ($value) use ($key): void {
            $this->put($key, $value);
        });
    }

    /**
     * Push a value onto a session array.
     */
    public function push(string $key, mixed $value): void
    {
        $array = $this->get($key, []);

        $array[] = $value;

        $this->put($key, $array);
    }

    /**
     * Increment the value of an item in the session.
     */
    public function increment(string $key, int $amount = 1): mixed
    {
        $this->put($key, $value = $this->get($key, 0) + $amount);

        return $value;
    }

    /**
     * Decrement the value of an item in the session.
     */
    public function decrement(string $key, int $amount = 1): int
    {
        return $this->increment($key, $amount * -1);
    }

    /**
     * Flash a key / value pair to the session.
     *
     * @param  true|mixed  $value
     */
    public function flash(string $key, mixed $value = true): void
    {
        $this->put($key, $value);

        $this->push('_flash.new', $key);

        $this->removeFromOldFlashData([$key]);
    }

    /**
     * Flash a key / value pair to the session for immediate use.
     */
    public function now(string $key, mixed $value): void
    {
        $this->put($key, $value);

        $this->push('_flash.old', $key);
    }

    /**
     * Re-flash a subset of the current flash data.
     */
    public function keep(mixed $keys = null): void
    {
        $this->mergeNewFlashes($keys = is_array($keys) ? $keys : func_get_args());

        $this->removeFromOldFlashData($keys);
    }

    /**
     * Flash an input array to the session.
     */
    public function flashInput(array $value): void
    {
        $this->flash('_old_input', $value);
    }

    /**
     * Remove an item from the session, returning its value.
     */
    public function remove(string $key): mixed
    {
        return Arr::pull($this->attributes, $key);
    }

    /**
     * Remove one or many items from the session.
     */
    public function forget(array|string $keys): void
    {
        Arr::forget($this->attributes, $keys);
    }

    /**
     * Remove all the items from the session.
     */
    public function flush(): void
    {
        $this->attributes = [];
    }

    /**
     * Flush the session data and regenerate the ID.
     */
    public function invalidate(): bool
    {
        $this->flush();

        return $this->migrate(true);
    }

    /**
     * Generate a new session identifier.
     */
    public function regenerate(bool $destroy = false): bool
    {
        return tap($this->migrate($destroy), function (): void {
            $this->regenerateToken();
        });
    }

    /**
     * Generate a new session ID for the session.
     */
    public function migrate(bool $destroy = false): bool
    {
        if ($destroy) {
            $this->handler->destroy($this->getId());
        }

        $this->setExists(false);

        $this->setId($this->generateSessionId());

        return true;
    }

    /**
     * Determine if the session has been started.
     */
    public function isStarted(): bool
    {
        return $this->started;
    }

    /**
     * Get the name of the session.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the session.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the current session ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the session ID.
     *
     * @param  string|null  $id
     */
    public function setId($id): void
    {
        $this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
    }

    /**
     * Determine if this is a valid session ID.
     */
    public function isValidId(?string $id): bool
    {
        return is_string($id) && ctype_alnum($id) && mb_strlen($id) === 40;
    }

    /**
     * Set the existence of the session on the handler if applicable.
     */
    public function setExists(bool $value): void
    {
        if ($this->handler instanceof ExistenceAwareInterface) {
            $this->handler->setExists($value);
        }
    }

    /**
     * Get the CSRF token value.
     */
    public function token(): string
    {
        return $this->get('_token');
    }

    /**
     * Regenerate the CSRF token value.
     */
    public function regenerateToken(): void
    {
        $this->put('_token', Str::random(40));
    }

    /**
     * Get the previous URL from the session.
     */
    public function previousUrl(): ?string
    {
        return $this->get('_previous.url');
    }

    /**
     * Set the "previous" URL in the session.
     */
    public function setPreviousUrl(string $url): void
    {
        $this->put('_previous.url', $url);
    }

    /**
     * Specify that the user has confirmed their password.
     */
    public function passwordConfirmed(): void
    {
        $this->put('auth.password_confirmed_at', time());
    }

    /**
     * Get the underlying session handler implementation.
     */
    public function getHandler(): SessionHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Set the underlying session handler implementation.
     */
    public function setHandler(SessionHandlerInterface $handler): SessionHandlerInterface
    {
        return $this->handler = $handler;
    }

    /**
     * Determine if the session handler needs a request.
     */
    public function handlerNeedsRequest(): bool
    {
        return $this->handler instanceof CookieSessionHandler;
    }

    /**
     * Set the request on the handler instance.
     */
    public function setRequestOnHandler(Request $request): void
    {
        if ($this->handlerNeedsRequest()) {
            $this->handler->setRequest($request);
        }
    }

    /**
     * Load the session data from the handler.
     */
    protected function loadSession(): void
    {
        $this->attributes = array_merge($this->attributes, $this->readFromHandler());

        $this->marshalErrorBag();
    }

    /**
     * Read the session data from the handler.
     */
    protected function readFromHandler(): array
    {
        if ($data = $this->handler->read($this->getId())) {
            if ($this->serialization === 'json') {
                $data = json_decode($this->prepareForUnserialize($data), true);
            } else {
                $data = @unserialize($this->prepareForUnserialize($data));
            }

            if ($data !== false && is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    /**
     * Prepare the raw string data from the session for unserialization.
     */
    protected function prepareForUnserialize(string $data): string
    {
        return $data;
    }

    /**
     * Marshal the ViewErrorBag when using JSON serialization for sessions.
     */
    protected function marshalErrorBag(): void
    {
        if ($this->serialization !== 'json' || $this->missing('errors')) {
            return;
        }

        $errorBag = new ViewErrorBag();

        foreach ($this->get('errors') as $key => $value) {
            $messageBag = new MessageBag($value['messages']);

            $errorBag->put($key, $messageBag->setFormat($value['format']));
        }

        $this->put('errors', $errorBag);
    }

    /**
     * Prepare the ViewErrorBag instance for JSON serialization.
     */
    protected function prepareErrorBagForSerialization(): void
    {
        if ($this->serialization !== 'json' || $this->missing('errors')) {
            return;
        }

        $errors = [];

        foreach ($this->attributes['errors']->getBags() as $key => $value) {
            $errors[$key] = [
                'format' => $value->getFormat(),
                'messages' => $value->getMessages(),
            ];
        }

        $this->attributes['errors'] = $errors;
    }

    /**
     * Prepare the serialized session data for storage.
     */
    protected function prepareForStorage(string $data): string
    {
        return $data;
    }

    /**
     * Merge new flash keys into the new flash array.
     */
    protected function mergeNewFlashes(array $keys): void
    {
        $values = array_unique(array_merge($this->get('_flash.new', []), $keys));

        $this->put('_flash.new', $values);
    }

    /**
     * Remove the given keys from the old flash data.
     */
    protected function removeFromOldFlashData(array $keys): void
    {
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    /**
     * Get a new, random session ID.
     */
    protected function generateSessionId(): string
    {
        return Str::random(40);
    }
}
