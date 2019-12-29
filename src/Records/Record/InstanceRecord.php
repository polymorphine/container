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
 * Record that creates and returns object of given class name
 * created with Container entries as its constructor parameters.
 *
 * Returned value is cached and returned directly on subsequent calls.
 */
class InstanceRecord implements Record
{
    use ContainerMapMethod;

    private $className;
    private $dependencies;
    private $object;

    /**
     * @param string $className       Class to instantiate
     * @param string ...$dependencies Container identifiers for constructor parameters
     */
    public function __construct(string $className, string ...$dependencies)
    {
        $this->className    = $className;
        $this->dependencies = $dependencies;
    }

    public function value(ContainerInterface $container)
    {
        return $this->object ?: $this->object = $this->create($container);
    }

    private function create(ContainerInterface $container)
    {
        return new $this->className(...$this->containerValues($this->dependencies, $container));
    }
}
