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

namespace PhpProfiler\Lib\Loop;

final class Loop
{
    public function __construct(
        private LoopMiddlewareInterface $process,
    ) {
    }

    public function invoke(): void
    {
        while (1) {
            if (!$this->process->invoke()) {
                break;
            }
        }
    }
}
