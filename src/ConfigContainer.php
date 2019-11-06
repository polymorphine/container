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


/**
 * Container with multidimensional array values accessed using path notation identifiers.
 * Example: $config['key']['sub-key']['id'] is accessed with $instance->get('key.sub-key.id').
 */
class ConfigContainer implements ContainerInterface
{
    public const SEPARATOR = '.';

    private $config;

    /**
     * $config keys MUST NOT contain path separator, because values
     * stored under these keys will not be accessible.
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
                throw new Exception\RecordNotFoundException(sprintf('Record `%s` not defined', $id));
            }
            $data = &$data[$key];
        }

        return $data;
    }

    public function has($id)
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
