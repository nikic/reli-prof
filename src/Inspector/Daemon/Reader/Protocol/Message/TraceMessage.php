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

namespace Reli\Inspector\Daemon\Reader\Protocol\Message;

use Reli\Lib\PhpProcessReader\CallTrace;

final class TraceMessage
{
    public function __construct(
        public CallTrace $trace,
    ) {
    }
}
