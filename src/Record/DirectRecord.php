<?php

namespace Shudd3r\Container\Record;

use Psr\Container\ContainerInterface;
use Shudd3r\Container\Record;


class DirectRecord implements Record
{
    private $value;

    public function __construct($value) {
        $this->value = $value;
    }

    public function value(ContainerInterface $c) {
        return $this->value;
    }
}
