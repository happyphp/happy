<?php

namespace Happy\Contracts\Http;

use Happy\Contracts\Foundation\ApplicationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface KernelInterface
{
    /**
     * Bootstrap the application for HTTP requests.
     */
    public function bootstrap(): void;

    /**
     * Handle an incoming HTTP request.
     */
    public function handle(Request $request): Response;

    /**
     * Perform any final actions for the request lifecycle.
     */
    public function terminate(Request $request, Response $response): void;

    /**
     * Get the Happy application instance.
     */
    public function getApplication(): ApplicationInterface;
}
