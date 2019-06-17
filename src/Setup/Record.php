<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Setup;

use Psr\Container\ContainerInterface;


/**
 * Abstract strategy for retrieving values from RecordContainer
 */
interface Record
{
    /**
     * Unwraps value requested from container.
     *
     * Container instance is passed as parameter because value
     * may derive from other Container entries.
     *
     * @param ContainerInterface $container
     *
     * @return mixed unwrapped record value
     */
    public function value(ContainerInterface $container);
}
