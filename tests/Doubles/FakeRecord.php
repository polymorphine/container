<?php

namespace Shudd3r\Container\Tests\Doubles;

use Psr\Container\ContainerInterface;
use Shudd3r\Container\Record;


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
