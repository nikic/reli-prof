<?php

/**
 * This file is part of the sj-i/php-profiler package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use PhpProfiler\Inspector\Output\TraceFormatter\Compat\CompatCallTraceFormatter;
use PhpProfiler\Lib\PhpProcessReader\CallTrace;

/** @var CallTrace $call_trace */

echo CompatCallTraceFormatter::getInstance()->format($call_trace) , PHP_EOL;
