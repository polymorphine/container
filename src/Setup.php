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
    public static function production(array $records = [], array $containers = []): self
    {
        return new self(new Setup\Build($records, $containers));
    }

    /**
     * Creates Setup with additional identifier collision checks, and
     * Container created with such Setup will also detect circular
     * references and add call stack paths to thrown exceptions.
     *
     * @param Records\Record[]     $records
     * @param ContainerInterface[] $containers
     *
     * @return static
     */
    public static function development(array $records = [], array $containers = []): self
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
     * Returns Entry object adding new container configuration data
     * for given identifier. For already defined identifiers Exception
     * will be thrown.
     *
     * @param string $id
     *
     * @throws Exception\OverwriteRuleException
     *
     * @return Setup\Entry
     */
    public function set(string $id): Entry
    {
        if ($this->build->has($id)) {
            throw Exception\OverwriteRuleException::alreadyDefined($id);
        }
        return new Entry($id, $this->build);
    }
}
