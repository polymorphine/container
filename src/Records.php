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


interface Records
{
    /**
     * Checks if Record is stored at given identifier.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool;

    /**
     * Returns Record stored at given identifier.
     *
     * @param string             $id
     * @param ContainerInterface $container
     *
     * @throws Exception\RecordNotFoundException
     *
     * @return mixed
     */
    public function get(string $id, ContainerInterface $container);
}
