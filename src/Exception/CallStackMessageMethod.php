<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Exception;


trait CallStackMessageMethod
{
    private static function extendMessage(string $message, array $callStack, string $lastCall = '...'): string
    {
        $stack = implode('->', array_keys($callStack)) . ($lastCall ? '->' . $lastCall : '');
        return "$message [call stack: $stack]";
    }
}
