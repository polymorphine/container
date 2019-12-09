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

use Polymorphine\Container\Setup\Build;
use Polymorphine\Container\Setup\Entry;
use Polymorphine\Container\Setup\Exception;
use Psr\Container\ContainerInterface;


class Setup
{
    private $build;

    public function __construct(Build $build = null)
    {
        $this->build = $build ?: new Setup\Build();
    }

    /**
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     *
     * @return static
     */
    public static function basic(array $records = [], array $containers = []): self
    {
        return new self(new Setup\Build($records, $containers));
    }

    /**
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     *
     * @return static
     */
    public static function validated(array $records = [], array $containers = []): self
    {
        return new self(new Setup\Build\ValidatedBuild($records, $containers));
    }

    /**
     * Returns immutable Container instance with provided data.
     *
     * Adding new entries to this setup is still possible, but created
     * container will not be affected and this method will create new
     * container instance with those added entries.
     *
     * @return ContainerInterface
     */
    public function container(): ContainerInterface
    {
        return $this->build->container();
    }

    /**
     * Returns Entry object adding new data to container configuration
     * for given identifier.
     *
     * @param string $id
     *
     * @throws Exception\IntegrityConstraintException
     *
     * @return Setup\Entry
     */
    public function add(string $id): Entry
    {
        if ($this->build->has($id)) {
            throw Exception\IntegrityConstraintException::alreadyDefined($id);
        }
        return new Entry($id, $this->build);
    }

    /**
     * Returns Entry object replacing data in container configuration
     * for given identifier.
     *
     * @param string $id
     *
     * @throws Exception\IntegrityConstraintException
     *
     * @return Setup\Entry
     */
    public function replace(string $id): Entry
    {
        if (!$this->build->has($id)) {
            throw Exception\IntegrityConstraintException::undefined($id);
        }
        return new Entry($id, $this->build);
    }

    /**
     * Returns Wrapper object able to decorate existing Record and replacing
     * it with composition of InstanceRecords using given id as a reference
     * to one of their dependencies (reference to itself).
     *
     * If given id is not defined or wrapping record doesn't use its reference
     * as one of dependencies IntegrityConstraintException will be thrown.
     *
     * Composition is finished with Wrapper::compose() call that will
     * replace initial entry with ComposedInstanceRecord.
     *
     * @see \Polymorphine\Container\Records\Record\InstanceRecord
     *
     * @param string $id
     *
     * @throws Exception\IntegrityConstraintException
     *
     * @return Setup\Entry\Wrapper
     */
    public function decorate(string $id): Entry\Wrapper
    {
        return $this->build->decorator($id);
    }
}
