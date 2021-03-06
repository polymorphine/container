<?php declare(strict_types=1);

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


/**
 * Container with multidimensional array values accessed using path notation identifiers.
 *
 * @example $container = new ConfigContainer($config);
 *          $container->get('key.sub-key.id') === $config['key']['sub-key']['id']; //true
 */
class ConfigContainer implements ContainerInterface
{
    public const SEPARATOR = '.';

    private array $config;

    /**
     * $config keys MUST NOT contain path separator (`.` character) on any level.
     * Values stored under these keys will not be accessible.
     *
     * @param array $config Associative (multidimensional) array of config values
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get($id)
    {
        $data = &$this->config;
        $keys = explode(self::SEPARATOR, $id);
        foreach ($keys as $key) {
            if (!is_array($data) || !array_key_exists($key, $data)) {
                throw Exception\RecordNotFoundException::undefined($id);
            }
            $data = &$data[$key];
        }

        return $data;
    }

    public function has($id): bool
    {
        $data = &$this->config;
        $keys = explode(self::SEPARATOR, $id);
        foreach ($keys as $key) {
            if (!is_array($data) || !array_key_exists($key, $data)) {
                return false;
            }
            $data = &$data[$key];
        }

        return true;
    }
}
