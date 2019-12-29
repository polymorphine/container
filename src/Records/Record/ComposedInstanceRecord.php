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
    private $recursiveId;
    private $baseRecord;
    private $wrappers;
    private $cached;

    public function __construct(string $id, Record $baseRecord, array $wrappers)
    {
        $this->recursiveId = $id;
        $this->baseRecord  = $baseRecord;
        $this->wrappers    = $wrappers;
    }

    public function value(ContainerInterface $container)
    {
        if ($this->cached) { return $this->cached; }
        $current = $this->baseRecord->value($container);
        foreach ($this->wrappers as [$className, $dependencies]) {
            $current = new $className(...$this->mapContainerRecords($current, $dependencies, $container));
        }

        return $this->cached = $current;
    }

    private function mapContainerRecords($current, array $identifiers, ContainerInterface $container): array
    {
        $dependencies = [];
        foreach ($identifiers as $id) {
            $dependencies[] = $id === $this->recursiveId ? $current : $container->get($id);
        }
        return $dependencies;
    }
}
