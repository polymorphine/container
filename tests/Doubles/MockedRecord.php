<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Tests\Doubles;

use Polymorphine\Container\Records\Record;
use Psr\Container\ContainerInterface;


class MockedRecord implements Record
{
    public $value;
    public ContainerInterface $passedContainer;

    public function __construct($value = null)
    {
        $this->value = $value;
    }

    public static function new($value = 'test'): self
    {
        return new self($value);
    }

    public function value(ContainerInterface $container)
    {
        $this->passedContainer = $container;
        return is_callable($this->value) ? ($this->value)($container) : $this->value;
    }
}
