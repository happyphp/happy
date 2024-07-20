<?php

declare(strict_types=1);

namespace Haphp\Contracts\Foundation;

use Haphp\Contracts\Container\ContainerInterface;
use Haphp\Support\AbstractServiceProvider;
use RuntimeException;

interface ApplicationInterface extends ContainerInterface
{
    /**
     * Get the version number of the application.
     */
    public function version(): string;

    /**
     * Get the base path of the Laravel installation.
     */
    public function basePath(string $path = ''): string;

    /**
     * Get the path to the bootstrap directory.
     */
    public function bootstrapPath(string $path = ''): string;

    /**
     * Get the path to the application configuration files.
     */
    public function configPath(string $path = ''): string;

    /**
     * Get the path to the database directory.
     */
    public function databasePath(string $path = ''): string;

    /**
     * Get the path to the language files.
     */
    public function langPath(string $path = ''): string;

    /**
     * Get the path to the public directory.
     */
    public function publicPath(string $path = ''): string;

    /**
     * Get the path to the resources' directory.
     */
    public function resourcePath(string $path = ''): string;

    /**
     * Get the path to the storage directory.
     */
    public function storagePath(string $path = ''): string;

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  ...$environments
     */
    public function environment(...$environments): bool|string;

    /**
     * Determine if the application is running in the console.
     */
    public function runningInConsole(): bool;

    /**
     * Determine if the application is running unit tests.
     */
    public function runningUnitTests(): bool;

    /**
     * Determine if the application is running with debug mode enabled.
     */
    public function hasDebugModeEnabled(): bool;

    /**
     * Get an instance of the maintenance mode manager implementation.
     */
    public function maintenanceMode(): MaintenanceModeInterface;

    /**
     * Determine if the application is currently down for maintenance.
     */
    public function isDownForMaintenance(): bool;

    /**
     * Register all the configured providers.
     */
    public function registerConfiguredProviders(): void;

    /**
     * Register a service provider with the application.
     */
    public function register(AbstractServiceProvider|string $provider, bool $force = false): AbstractServiceProvider;

    /**
     * Register a deferred provider and service.
     */
    public function registerDeferredProvider(string $provider, ?string $service = null): void;

    /**
     * Resolve a service provider instance from the class name.
     */
    public function resolveProvider(string $provider): AbstractServiceProvider;

    /**
     * Boot the application's service providers.
     */
    public function boot(): void;

    /**
     * Register a new boot listener.
     */
    public function booting(callable $callback): void;

    /**
     * Register a new "booted" listener.
     */
    public function booted(callable $callback): void;

    /**
     * Run the given array of bootstrap classes.
     */
    public function bootstrapWith(array $bootstrappers): void;

    /**
     * Get the current application locale.
     */
    public function getLocale(): string;

    /**
     * Get the application namespace.
     *
     * @throws RuntimeException
     */
    public function getNamespace(): string;

    /**
     * Get the registered service provider instances if any exist.
     */
    public function getProviders(AbstractServiceProvider|string $provider): array;

    /**
     * Determine if the application has been bootstrapped before.
     */
    public function hasBeenBootstrapped(): bool;

    /**
     * Load and boot all the remaining deferred providers.
     */
    public function loadDeferredProviders(): void;

    /**
     * Set the current application locale.
     */
    public function setLocale(string $locale): void;

    /**
     * Determine if middleware has been disabled for the application.
     */
    public function shouldSkipMiddleware(): bool;

    /**
     * Register a terminating callback with the application.
     */
    public function terminating(callable|string $callback): ApplicationInterface;

    /**
     * Terminate the application.
     */
    public function terminate(): void;
}
