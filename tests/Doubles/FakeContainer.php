<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Doubles;

use Polymorphine\Container\Exception;
use Psr\Container\ContainerInterface;


class FakeContainer implements ContainerInterface
{
    public $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public static function new(array $data = []): self
    {
        return new self($data);
    }

    public function get($id)
    {
        if (!array_key_exists($id, $this->data)) {
            throw new Exception\RecordNotFoundException("MockedContainer: missing `$id` entry");
        }
        return $this->data[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->data);
    }
}
