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

use Psr\Container\ContainerInterface;
use Closure;


interface Factory
{
    /**
     * Creates container with provided records.
     *
     * Immutability of container depends on stored records
     * implementation, because although no new entries can
     * be added, side-effects can change subsequent call
     * outcomes for stored identifiers.
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface;

    /**
     * Stores value under $name identifier.
     *
     * Value will be returned as passed. It means that Closures
     * won't be invoked, but returned as Closures.
     *
     * @param $name
     * @param $value
     */
    public function value($name, $value): void;

    /**
     * Stores Closure under given $name identifier.
     *
     * Value returned by container will be always the outcome
     * of first Closure call, this way only single object can
     * be created and same instance will be returned on each
     * subsequent call.
     *
     * Closure receives Container instance as parameter.
     *
     * @param $name
     * @param Closure $closure
     */
    public function lazy($name, Closure $closure): void;

    /**
     * Stores Record under given $name identifier.
     * Behavior of Container returning given Record's value
     * depends on passed Record's implementation.
     *
     * @param $name
     * @param Record $record
     */
    public function record($name, Record $record): void;
}
