<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Factory;

use Polymorphine\Container\Factory;
use Polymorphine\Container\Record;
use Closure;


/**
 * Write-only proxy that prevents configuration namespaces
 * gain access to already written values through created
 * Container.
 */
class ContainerRecordEntry
{
    private $name;
    private $factory;

    public function __construct(string $name, Factory $factory) {
        $this->name = $name;
        $this->factory = $factory;
    }

    /**
     * Pushes value to Container's entry Record.
     * Unchanged value will be returned from Container when this
     * record is requested.
     *
     * @param $value
     */
    public function value($value): void {
        $this->factory->value($this->name, $value);
    }

    /**
     * Pushes Closure to Container's entry Record.
     * Value returned from Container will be result of first
     * call to this Closure call and remain the same on
     * subsequent requests.
     *
     * Closure receives Container instance as parameter.
     *
     * @param Closure $closure
     */
    public function lazy(Closure $closure): void {
        $this->factory->lazy($this->name, $closure);
    }

    /**
     * Pushes Record instance directly into Container's entry.
     *
     * @param Record $record
     */
    public function record(Record $record): void {
        $this->factory->record($this->name, $record);
    }
}
