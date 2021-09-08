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

namespace PhpProfiler\Lib\Amphp;

use Amp\Parallel\Context\Context as AmphpContext;
use Amp\Promise;

/** @template-covariant T of MessageProtocolInterface */
final class Context implements ContextInterface
{
    /** @param T $protocol_interface */
    public function __construct(
        private AmphpContext $amphp_context,
        private object $protocol_interface
    ) {
    }

    /** @return Promise<null> */
    public function start(): Promise
    {
        return $this->amphp_context->start();
    }

    public function isRunning(): bool
    {
        return $this->amphp_context->isRunning();
    }

    /** @return T */
    public function getProtocol(): object
    {
        return $this->protocol_interface;
    }
}
