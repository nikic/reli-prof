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

namespace Reli\Lib\Loop\LoopCondition;

use PHPUnit\Framework\TestCase;

class OnlyOnceConditionTest extends TestCase
{
    public function testShouldContinue(): void
    {
        $condition = new OnlyOnceCondition();
        $this->assertTrue($condition->shouldContinue());
        $this->assertFalse($condition->shouldContinue());
    }
}
