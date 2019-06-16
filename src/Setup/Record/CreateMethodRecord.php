<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup\Record;

use Polymorphine\Container\Setup\Record;
use Polymorphine\Container\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;


class CreateMethodRecord implements Record
{
    private $method;
    private $arguments = [];
    private $product;

    public function __construct(string $method, $arguments)
    {
        $this->method    = $method;
        $this->arguments = $arguments;
    }

    public function value(ContainerInterface $container)
    {
        return $this->product ?: $this->product = $this->create($container);
    }

    private function create(ContainerInterface $container)
    {
        [$method, $factoryName] = explode('@', $this->method) + [null, null];

        if (!$method || !$factoryName) {
            throw new InvalidArgumentException();
        }

        $factory = $container->get($factoryName);

        return $factory->{$method}(...$this->arguments);
    }
}
