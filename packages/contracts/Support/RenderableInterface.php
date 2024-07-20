<?php

namespace Haphp\Contracts\Support;

interface RenderableInterface
{
    /**
     * Get the evaluated contents of the object.
     */
    public function render(): string;
}
