<?php declare(strict_types=1);

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
 * Record using multiple object definitions wrapping each other.
 *
 * NOTICE: Object intended for internal instantiation through Setup
 * methods. Instead directly created instance use more efficient methods
 * of composing objects like factories or procedures encapsulated within
 * callback function.
 *
 * @see CallbackRecord
 *
 * Returned value is cached and returned directly on subsequent calls.
 */
class ComposedInstanceRecord implements Record
{
    private string $className;
    private Record $wrappedRecord;
    private array  $dependencies;
    private object $object;

    /**
     * @param string      $className
     * @param Record      $wrappedRecord
     * @param string|null ...$dependencies
     */
    public function __construct(string $className, Record $wrappedRecord, ?string ...$dependencies)
    {
        $this->className     = $className;
        $this->wrappedRecord = $wrappedRecord;
        $this->dependencies  = $dependencies;
    }

    public function value(ContainerInterface $container): object
    {
        return $this->object ??= $this->create($container);
    }

    private function create(ContainerInterface $container)
    {
        $dependencies = $this->mapDependencies($container);
        return new $this->className(...$dependencies);
    }

    private function mapDependencies(ContainerInterface $container): array
    {
        $dependencies = [];
        foreach ($this->dependencies as $id) {
            $dependencies[] = $id ? $container->get($id) : $this->wrappedRecord->value($container);
        }
        return $dependencies;
    }
}
