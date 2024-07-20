<?php

declare(strict_types=1);

namespace Haphp\Session;

use Haphp\Contracts\Cookie\QueueingCookieInterface as CookieJar;
use Haphp\Contracts\Session\SessionHandlerInterface;
use Haphp\Support\Traits\InteractsWithTime;
use Override;
use Symfony\Component\HttpFoundation\Request;

class CookieSessionHandler implements SessionHandlerInterface
{
    use InteractsWithTime;

    /**
     * The request instance.
     */
    protected Request $request;

    /**
     * Create a new cookie-driven handler instance.
     *
     * @return void
     */
    public function __construct(
        protected CookieJar $cookie,
        protected int $minutes,
        protected bool $expireOnClose = false
    ) {
    }

    #[Override]
    public function close(): bool
    {
        return true;
    }

    #[Override]
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read($id): string|false
    {
        $value = $this->request->cookies->get($id) ?: '';

        if (null !== ($decoded = json_decode($value, true)) && is_array($decoded) &&
            isset($decoded['expires']) && $this->currentTime() <= $decoded['expires']) {
            return $decoded['data'];
        }

        return '';
    }

    public function write($id, $data): bool
    {
        $this->cookie->queue($id, json_encode([
            'data' => $data,
            'expires' => $this->availableAt($this->minutes * 60),
        ]), $this->expireOnClose ? 0 : $this->minutes);

        return true;
    }

    public function destroy($id): bool
    {
        $this->cookie->queue($this->cookie->forget($id));

        return true;
    }

    public function gc(int $max_lifetime): int
    {
        return 0;
    }

    /**
     * Set the request instance.
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }
}
