<?php

namespace Polymorphine\Container\Tests\Doubles;

use Closure;


class ExampleClass
{
    private $name;
    private $callback;

    public function __construct(Closure $callback, string $name) {
        $this->name = $name;
        $this->callback = $callback;
    }

    public function beNice() {
        return $this->callback->__invoke($this->name);
    }
}
