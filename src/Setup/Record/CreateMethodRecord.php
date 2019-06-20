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
use Psr\Container\ContainerInterface;


/**
 * Record that creates its value by calling a method on (factory) object.
 *
 * Returned value is cached and returned directly when
 * value() method is called again.
 */
class CreateMethodRecord implements Record
{
    use ExtractArgumentsTrait;

    private $factoryId;
    private $method;
    private $arguments = [];
    private $product;

    /**
     * @param string $method
     * @param string $factoryId
     * @param string ...$arguments
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
        return $factory->{$this->method}(...$this->arguments($this->arguments, $container));
    }
}
