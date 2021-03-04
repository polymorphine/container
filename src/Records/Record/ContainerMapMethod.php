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

use Psr\Container\ContainerInterface;


trait ContainerMapMethod
{
    private function containerValues(array $identifiers, ContainerInterface $container): array
    {
        return array_map(function ($id) use ($container) { return $container->get($id); }, $identifiers);
    }
}
