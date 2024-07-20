<?php

namespace Haphp\Contracts\Support;

interface HtmlableInterface
{
    /**
     * Get content as a string of HTML.
     */
    public function toHtml(): string;
}
