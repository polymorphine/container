<?php

/**
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Doubles;

use Psr\Container\ContainerInterface;
use Polymorphine\Container\Record;


class FakeRecord implements Record
{
    public $id;

    public function __construct(string $id) {
        $this->id = $id;
    }

    public function value(ContainerInterface $c) {
        return $this->id;
    }
}
