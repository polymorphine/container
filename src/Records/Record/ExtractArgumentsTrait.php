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

use Psr\Container\ContainerInterface;


trait ExtractArgumentsTrait
{
    private function arguments(array $identifiers, ContainerInterface $container): array
    {
        $arguments = [];
        foreach ($identifiers as $id) {
            $arguments[] = $container->get($id);
        }

        return $arguments;
    }
}
