<?php declare(strict_types=1);

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;


class TrackedRecordNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    use CallStackMessageMethod;

    public function __construct(string $localId, array $callStack, RecordNotFoundException $previous)
    {
        $message = $this->extendedMessage($localId, $callStack, $previous);
        parent::__construct($message, 0, $previous);
    }

    private function extendedMessage(string $localId, array $callStack, RecordNotFoundException $previous): string
    {
        $message     = $previous->getMessage();
        $unstackedId = $this->unstackedCallId($message, $localId);
        return self::extendMessage($message, $callStack, $unstackedId);
    }

    private function unstackedCallId(string $message, string $localId): ?string
    {
        $idFound = preg_match('#`(?P<id>.+?)`#', $message, $matches);
        if (!$idFound || $matches['id'] === $localId) { return null; }
        return $matches['id'];
    }
}
