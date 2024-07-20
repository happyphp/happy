<?php

namespace Haphp\Contracts\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class CircularDependencyExceptionInterface extends Exception implements ContainerExceptionInterface
{
    //
}
