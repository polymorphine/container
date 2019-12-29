<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Records\Record;

use Polymorphine\Container\Records\Record;
use Psr\Container\ContainerInterface;


/**
 * Record that creates returned value by calling method name
 * and container entries both as method's parameters and factory
 * object the call is made on.
 *
 * Returned value is cached and returned directly on subsequent calls.
 */
class ProductRecord implements Record
{
    use ContainerMapMethod;

    private $factoryId;
    private $method;
    private $arguments = [];
    private $product;

    /**
     * @param string $factoryId    Container identifier for factory object
     * @param string $method       Factory method name
     * @param string ...$arguments Container identifiers for method parameters
     */
    public function __construct(string $factoryId, string $method, string ...$arguments)
    {
        $this->factoryId = $factoryId;
        $this->method    = $method;
        $this->arguments = $arguments;
    }

    public function value(ContainerInterface $container)
    {
        return $this->product ?: $this->product = $this->create($container);
    }

    private function create(ContainerInterface $container)
    {
        $factory = $container->get($this->factoryId);
        return $factory->{$this->method}(...$this->containerValues($this->arguments, $container));
    }
}
