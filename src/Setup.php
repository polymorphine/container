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


/**
 * Builder type class provides methods for collecting entries for container
 * and creating its instance based on composition of provided values.
 */
class Setup
{
    private $build;

    /**
     * @param null|Build $build
     */
    public function __construct(Build $build = null)
    {
        $this->build = $build ?: new Setup\Build();
    }

    /**
     * Creates Setup with predefined Record and ContainerInterface entries
     * assuming correctness of provided data.
     *
     * Constraints for keys of array parameters:
     * - Keys will become Container identifiers so they must be unique strings in
     *   both arrays combined
     * - $containers array keys cannot contain separator (default: `.` character)
     * - keys of $records array cannot start with any of $containers separated
     *   prefix. For example: ['foo.bar' => record] and ['foo' => container]
     *
     * @param Records\Record[]     $records    Flat associative with string identifier keys
     * @param ContainerInterface[] $containers Flat associative with string identifier prefix keys
     *
     * @return static
     */
    public static function production(array $records = [], array $containers = []): self
    {
        return new self(new Setup\Build($records, $containers));
    }

    /**
     * Creates Setup with predefined Record and ContainerInterface entries with
     * additional (compared to production() method) identifier collision checks
     * that will create Container in DEBUG mode detecting circular references
     * and adding call stack paths to thrown exceptions.
     *
     * @see Setup::production()
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
