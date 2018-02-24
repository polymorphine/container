<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container;

use Closure;


/**
 * Write-only proxy that prevents configuration namespaces
 * gain access to already written values through created
 * Container.
 */
class RecordEntry
{
    private $name;
    private $factory;

    public function __construct(string $name, Factory $factory) {
        $this->name = $name;
        $this->factory = $factory;
    }

    /**
     * Pushes DirectRecord with given value into Container's records.
     * The same value will be returned from Container when this
     * record is requested.
     *
     * @see Record\DirectRecord
     *
     * @param $value
     */
    public function value($value): void {
        $this->record(new Record\DirectRecord($value));
    }

    /**
     * Pushes LazyRecord with given Closure into Container's records.
     * Value returned from this record will be result of first
     * call to provided Closure call and will remain the same on
     * subsequent requests.
     *
     * Returned object state might change.
     * Closure receives Container instance as parameter.
     *
     * @see Record\LazyRecord
     *
     * @param Closure $closure
     */
    public function lazy(Closure $closure): void {
        $this->record(new Record\LazyRecord($closure));
    }

    /**
     * Pushes given Record instance directly into Container's records.
     *
     * @see Record
     *
     * @param Record $record
     */
    public function record(Record $record): void {
        $this->factory->record($this->name, $record);
    }
}
