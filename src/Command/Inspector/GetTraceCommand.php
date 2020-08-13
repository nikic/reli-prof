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

namespace PhpProfiler\Command\Inspector;

use PhpProfiler\Inspector\Settings\GetTraceSettings\GetTraceSettingsFromConsoleInput;
use PhpProfiler\Inspector\Settings\InspectorSettingsException;
use PhpProfiler\Inspector\Settings\TargetPhpSettings\TargetPhpSettingsFromConsoleInput;
use PhpProfiler\Inspector\Settings\TargetProcessSettings\TargetProcessSettingsFromConsoleInput;
use PhpProfiler\Inspector\Settings\TraceLoopSettings\TraceLoopSettingsFromConsoleInput;
use PhpProfiler\Inspector\TraceLoopProvider;
use PhpProfiler\Lib\Elf\Parser\ElfParserException;
use PhpProfiler\Lib\Elf\Process\ProcessSymbolReaderException;
use PhpProfiler\Lib\Elf\Tls\TlsFinderException;
use PhpProfiler\Lib\PhpProcessReader\PhpGlobalsFinder;
use PhpProfiler\Lib\Process\MemoryReader\MemoryReaderException;
use PhpProfiler\Lib\PhpProcessReader\PhpMemoryReader\ExecutorGlobalsReader;
use PhpProfiler\Lib\Process\ProcessStopper\ProcessStopper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GetTraceCommand extends Command
{
    private PhpGlobalsFinder $php_globals_finder;
    private ExecutorGlobalsReader $executor_globals_reader;
    private TraceLoopProvider $loop_provider;
    private GetTraceSettingsFromConsoleInput $get_trace_settings_from_console_input;
    private TargetPhpSettingsFromConsoleInput $target_php_settings_from_console_input;
    private TargetProcessSettingsFromConsoleInput $target_process_settings_from_console_input;
    private TraceLoopSettingsFromConsoleInput $trace_loop_settings_from_console_input;
    private ProcessStopper $process_stopper;

    public function __construct(
        PhpGlobalsFinder $php_globals_finder,
        ExecutorGlobalsReader $executor_globals_reader,
        TraceLoopProvider $loop_provider,
        GetTraceSettingsFromConsoleInput $get_trace_settings_from_console_input,
        TargetPhpSettingsFromConsoleInput $target_php_settings_from_console_input,
        TargetProcessSettingsFromConsoleInput $target_process_settings_from_console_input,
        TraceLoopSettingsFromConsoleInput $trace_loop_settings_from_console_input,
        ProcessStopper $process_stopper
    ) {
        $this->php_globals_finder = $php_globals_finder;
        $this->executor_globals_reader = $executor_globals_reader;
        $this->loop_provider = $loop_provider;
        $this->get_trace_settings_from_console_input = $get_trace_settings_from_console_input;
        $this->target_php_settings_from_console_input = $target_php_settings_from_console_input;
        $this->target_process_settings_from_console_input = $target_process_settings_from_console_input;
        $this->trace_loop_settings_from_console_input = $trace_loop_settings_from_console_input;
        $this->process_stopper = $process_stopper;
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName('inspector:trace')
            ->setDescription('periodically get call trace from an outer process or thread')
        ;
        $this->target_process_settings_from_console_input->setOptions($this);
        $this->get_trace_settings_from_console_input->setOptions($this);
        $this->trace_loop_settings_from_console_input->setOptions($this);
        $this->target_php_settings_from_console_input->setOptions($this);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws MemoryReaderException
     * @throws ProcessSymbolReaderException
     * @throws ElfParserException
     * @throws TlsFinderException
     * @throws InspectorSettingsException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $get_trace_settings = $this->get_trace_settings_from_console_input->createSettings($input);
        $target_php_settings = $this->target_php_settings_from_console_input->createSettings($input);
        $target_process_settings = $this->target_process_settings_from_console_input->createSettings($input);
        $loop_settings = $this->trace_loop_settings_from_console_input->createSettings($input);

        $eg_address = $this->php_globals_finder->findExecutorGlobals($target_process_settings, $target_php_settings);

        $this->loop_provider->getMainLoop(
            function () use (
                $get_trace_settings,
                $target_process_settings,
                $target_php_settings,
                $eg_address,
                $output
            ): bool {
                $this->process_stopper->stop($target_process_settings->pid);
                $call_trace = $this->executor_globals_reader->readCallTrace(
                    $target_process_settings->pid,
                    $target_php_settings->php_version,
                    $eg_address,
                    $get_trace_settings->depth
                );
                $this->process_stopper->resume($target_process_settings->pid);
                $output->writeln(join(PHP_EOL, $call_trace) . PHP_EOL);
                return true;
            },
            $loop_settings
        )->invoke();

        return 0;
    }
}
