<?php

namespace Shudd3r\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use InvalidArgumentException;


class InvalidStateException extends InvalidArgumentException implements ContainerExceptionInterface
{

}
