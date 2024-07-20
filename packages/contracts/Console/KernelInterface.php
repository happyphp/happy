<?php

declare(strict_types=1);

namespace Haphp\Contracts\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface KernelInterface
{
    /**
     * Bootstrap the application for artisan commands.
     */
    public function bootstrap(): void;

    /**
     * Handle an incoming console command.
     */
    public function handle(InputInterface $input, ?OutputInterface $output = null): int;

    /**
     * Run an Artisan console command by name.
     */
    public function call(string $command, array $parameters = [], ?OutputInterface $outputBuffer = null): int;

    /**
     * Queue an Artisan console command by name.
     *
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function queue(string $command, array $parameters = []);

    /**
     * Get all the commands registered with the console.
     */
    public function all(): array;

    /**
     * Get the output for the last run command.
     */
    public function output(): string;

    /**
     * Terminate the application.
     */
    public function terminate(InputInterface $input, int $status): void;
}
