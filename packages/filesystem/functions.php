<?php

declare(strict_types=1);

namespace Haphp\Filesystem;

if (! function_exists('Haphp\Filesystem\join_paths')) {
    /**
     * Join the given paths together.
     */
    function join_paths(?string $basePath, string ...$paths): string
    {
        foreach ($paths as $index => $path) {
            if (empty($path)) {
                unset($paths[$index]);
            } else {
                $paths[$index] = DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $basePath.implode('', $paths);
    }
}
