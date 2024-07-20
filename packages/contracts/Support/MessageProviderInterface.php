<?php

namespace Haphp\Contracts\Support;

interface MessageProviderInterface
{
    /**
     * Get the messages for the instance.
     */
    public function getMessageBag(): MessageBagInterface;
}
