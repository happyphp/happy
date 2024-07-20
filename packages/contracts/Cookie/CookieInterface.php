<?php

namespace Haphp\Contracts\Cookie;

use Symfony\Component\HttpFoundation\Cookie;

interface CookieInterface
{
    /**
     * Create a new cookie instance.
     */
    public function make(
        string $name,
        string $value,
        int $minutes = 0,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        bool $httpOnly = true,
        bool $raw = false,
        ?string $sameSite = null
    ): Cookie;

    /**
     * Create a cookie that lasts "forever" (five years).
     */
    public function forever(
        string $name,
        string $value,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        bool $httpOnly = true,
        bool $raw = false,
        ?string $sameSite = null
    ): Cookie;

    /**
     * Expire the given cookie.
     */
    public function forget(string $name, ?string $path = null, ?string $domain = null): Cookie;
}
