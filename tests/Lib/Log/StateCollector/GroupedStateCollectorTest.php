<?php

/**
 * This file is part of the reliforp/reli-prof package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Reli\Lib\Log\StateCollector;

use PHPUnit\Framework\TestCase;

class GroupedStateCollectorTest extends TestCase
{
    public function testCollect()
    {
        $collector = new GroupedStateCollector(
            new class implements StateCollector {
                public function collect(): array
                {
                    return ['a' => 1];
                }
            },
            new class implements StateCollector {
                public function collect(): array
                {
                    return ['b' => 2];
                }
            }
        );
        $this->assertSame(
            [
                'a' => 1,
                'b' => 2,
            ],
            $collector->collect()
        );
    }
}
