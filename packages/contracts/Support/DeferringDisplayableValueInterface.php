<?php

namespace Haphp\Contracts\Support;

interface DeferringDisplayableValueInterface
{
    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \Haphp\Contracts\Support\HtmlableInterface|string
     */
    public function resolveDisplayableValue();
}
