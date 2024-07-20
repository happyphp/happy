<?php

namespace Haphp\Contracts\Cookie;

interface QueueingCookieInterface extends CookieInterface
{
    /**
     * Queue a cookie to send with the next response.
     *
     * @param  mixed  ...$parameters
     */
    public function queue(...$parameters): void;

    /**
     * Remove a cookie from the queue.
     */
    public function unqueue(string $name, ?string $path = null): void;

    /**
     * Get the cookies which have been queued for the next request.
     */
    public function getQueuedCookies(): array;
}
