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

namespace Reli\Inspector\Daemon\Searcher\Controller;

use Amp\Promise;
use Reli\Inspector\Daemon\Searcher\Protocol\Message\TargetPhpSettingsMessage;
use Reli\Inspector\Daemon\Searcher\Protocol\PhpSearcherControllerProtocolInterface;
use Reli\Inspector\Settings\TargetPhpSettings\TargetPhpSettings;
use Reli\Lib\Amphp\ContextInterface;

final class PhpSearcherController implements PhpSearcherControllerInterface
{
    /** @param ContextInterface<PhpSearcherControllerProtocolInterface> $context */
    public function __construct(
        private ContextInterface $context
    ) {
    }

    public function start(): Promise
    {
        return $this->context->start();
    }

    public function sendTarget(
        string $regex,
        TargetPhpSettings $target_php_settings,
        int $pid,
    ): Promise {
        return $this->context->getProtocol()
            ->sendTargetRegex(
                new TargetPhpSettingsMessage(
                    $regex,
                    $target_php_settings,
                    $pid
                )
            )
        ;
    }

    public function receivePidList(): Promise
    {
        return $this->context->getProtocol()->receiveUpdateTargetProcess();
    }
}
