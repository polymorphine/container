<?php

namespace Shudd3r\Http\Src\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use InvalidArgumentException;


class InvalidIdException extends InvalidArgumentException implements ContainerExceptionInterface
{

}
