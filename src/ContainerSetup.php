<?php

/*
 * This file is part of Polymorphine/Container package.
 *
 * (c) Shudd3r <q3.shudder@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Polymorphine\Container;

use Psr\Container\ContainerInterface;


class ContainerSetup implements RecordCollection, Factory
{
    use TokenFormatValidation;

    private $records;

    /**
     * ContainerFactory constructor.
     *
     * @param Record[] $records
     *
     * @throws Exception\InvalidArgumentException | Exception\InvalidIdException
     */
    public function __construct(array $records = [])
    {
        $this->validateRecords($records);
        $this->records = $records;
    }

    public function container(): ContainerInterface
    {
        return new Container($this->records);
    }

    public function isDefined(string $name): bool
    {
        return isset($this->records[$name]);
    }

    public function push(string $name, Record $record): void
    {
        $this->validateIdFormat($name);
        $this->preventOverwrite($name);
        $this->records[$name] = $record;
    }

    public function pull(string $name): Record
    {
        if (!isset($this->records[$name])) {
            throw new Exception\RecordNotFoundException(sprintf('Record with `%s` id not found', $name));
        }

        $record = $this->records[$name];
        unset($this->records[$name]);

        return $record;
    }

    private function validateRecords(array $records): void
    {
        foreach ($records as $id => $record) {
            $this->validateIdFormat($id);
            if (!$record instanceof Record) {
                throw new Exception\InvalidArgumentException('Expected associative array of Record instances');
            }
        }
    }

    private function preventOverwrite(string $id): void
    {
        if (isset($this->records[$id])) {
            throw new Exception\InvalidIdException(sprintf('Cannot overwrite defined `%s` Record', $id));
        }
    }
}
