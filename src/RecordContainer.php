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
 * ContainerInterface implementation using Records instance
 * with various strategies to deliver values.
 */
class RecordContainer implements ContainerInterface
{
    protected Records $records;

    /**
     * @param Records $records
     */
    public function __construct(Records $records)
    {
        $this->records = $records;
    }

    public function get($id)
    {
        return $this->records->get($id, $this);
    }

    public function has($id): bool
    {
        return $this->records->has($id);
    }
}
