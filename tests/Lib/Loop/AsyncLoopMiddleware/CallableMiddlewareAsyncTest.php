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

namespace Reli\Lib\Loop\AsyncLoopMiddleware;

use PHPUnit\Framework\TestCase;

class CallableMiddlewareAsyncTest extends TestCase
{
    public function testInvoke(): void
    {
        $callable_middleware = new CallableMiddlewareAsync(
            function () {
                yield 42;
            }
        );
        $generator = $callable_middleware->invoke();
        $result = $generator->current();
        $this->assertSame(42, $result);
        $result = $generator->send(null);
        $this->assertNull($result);
    }
}
