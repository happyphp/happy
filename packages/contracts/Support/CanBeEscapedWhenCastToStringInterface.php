<?php

namespace Haphp\Contracts\Support;

interface CanBeEscapedWhenCastToStringInterface
{
    /**
     * Indicate that the object's string representation should be escaped when __toString is invoked.
     *
     * @return $this
     */
    public function escapeWhenCastingToString(bool $escape = true): static;
}
