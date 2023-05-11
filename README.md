# Reli
![Minimum PHP version: 8.1.0](https://img.shields.io/badge/php-8.1.0%2B-blue.svg)
[![Packagist](https://img.shields.io/packagist/v/reliforp/reli-prof.svg)](https://packagist.org/packages/reliforp/reli-prof)
[![Github Actions](https://github.com/reliforp/reli-prof/workflows/build/badge.svg)](https://github.com/reliforp/reli-prof/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/reliforp/reli-prof/badges/quality-score.png?b=0.9.x)](https://scrutinizer-ci.com/g/reliforp/reli-prof/?branch=0.9.x)
[![Coverage Status](https://coveralls.io/repos/github/reliforp/reli-prof/badge.svg?branch=0.9.x)](https://coveralls.io/github/reliforp/reli-prof?branch=0.9.x)
![Psalm coverage](https://shepherd.dev/github/reliforp/reli-prof/coverage.svg?)

Reli is a sampling profiler (or a VM state inspector) written in PHP. It can read information about running PHP script from outside of the process. It's a stand alone CLI tool, so target programs don't need any modifications. The former name of this tool was sj-i/php-profiler. 

## What can I use this for?
- Detecting and visualizing bottlenecks in PHP scripts
  - It provides not only at the function level of profiling but also at line level or opcode level resolution
- Profiling without accumulated overhead even when a lot of fast functions called as this is a sampling profiler (see the links below, tideways, xhprof, and the profiler of xdebug, many profilers have this overhead)
  - [Profiling Overhead and PHP 7](https://tideways.com/profiler/blog/profiling-overhead-and-php-7)
  - [nikic/sample_prof](https://github.com/nikic/sample_prof)
- Investigating the cause of a bug or performance failure
  - Even if a PHP script is in an unexplained unresponsive state, you can use this to find out what it is doing internally.

## How it works
It's implemented by using following techniques:

- Parsing ELF binary of the interpreter
- Reading memory map from /proc/\<pid\>/maps
- Reading memory of outer process by using ptrace(2) and process_vm_readv(2) via FFI
- Analyzing internal data structure in the PHP VM (aka Zend Engine)

If you have a bit of extra CPU resource, the overhead of this software would be negligible.

## Differences to phpspy, when to use reli
Reli is heavily inspired by [adsr/phpspy](https://github.com/adsr/phpspy).

The main difference between the two is that reli is written in almost pure PHP while phpspy is written in C.
In profiling, there are cases you want to customize how and what information to get.
If customizability for PHP developers matters, you can use this software at the cost of performance. (Although, we hope the cost is not too big.)

Additionally, reli can find VM state from ZTS interpreters. For example, in the daemon mode, traces of threads started via [ext-parallel](https://github.com/krakjoe/parallel) are automatically retrieved. Currently this cannot be done with phpspy only.
Reli also provides functionality to only get the address of EG from targets, so you can use actual profiling with phpspy if you want, even when the target is ZTS.

Other features of reli that phpspy does not currently have include:

- Output more accurate line numbers
- Customize output format with PHP templates
- Get running opcodes of the PHP-VM
- Automatic retrieval of the target PHP version from stripped PHP binaries
- Output traces in speedscope format

There is no particular reason why these features cannot be implemented on the phpspy side, so it may be possible to do them on phpspy in the future.

On the other hand, there are a few things that phpspy can do but reli cannot yet.

- Redirecting output of child processes
- Forcing the address of EG
- Data retrieval from sapi_globals
- callgrind support
- Reading variables
- Run more faster with lower overhead.
- etc.

Much of what can be done with phpspy will be done with reli in the future.

## Requirements
### Supported PHP versions
#### Execution
- PHP 8.1+ (NTS / ZTS)
- 64bit Linux x86_64
- FFI extension must be enabled.
- If the target process is ZTS, PCNTL extension must be enabled.

#### Target
- PHP 7.0+ (NTS / ZTS)
- 64bit Linux x86_64

On targeting ZTS, the target process must load libpthread.so, and also you must have unstripped binary of the interpreter and the libpthread.so, to find EG from the TLS.

## Installation
### From Composer
```bash
composer create-project reliforp/reli-prof
cd reli
./reli
```

### From Git
```bash
git clone git@github.com:reliforp/reli-prof.git
cd reli
composer install
./reli
```

## Usage
### Get call traces
```bash
./reli inspector:trace --help
Description:
  periodically get call trace from an outer process or thread

Usage:
  inspector:trace [options] [--] [<cmd> [<args>...]]

Arguments:
  cmd                                        command to execute as a target: either pid (via -p/--pid) or cmd must be specified
  args                                       command line arguments for cmd

Options:
  -p, --pid=PID                              process id
  -d, --depth[=DEPTH]                        max depth
  -s, --sleep-ns[=SLEEP-NS]                  nanoseconds between traces (default: 1000 * 1000 * 10)
  -r, --max-retries[=MAX-RETRIES]            max retries on contiguous errors of read (default: 10)
  -S, --stop-process[=STOP-PROCESS]          stop the target process while reading its trace (default: off)
      --php-regex[=PHP-REGEX]                regex to find the php binary loaded in the target process
      --libpthread-regex[=LIBPTHREAD-REGEX]  regex to find the libpthread.so loaded in the target process
      --php-version[=PHP-VERSION]            php version (auto|v7[0-4]|v8[01]) of the target (default: auto)
      --php-path[=PHP-PATH]                  path to the php binary (only needed in tracing chrooted ZTS target)
      --libpthread-path[=LIBPTHREAD-PATH]    path to the libpthread.so (only needed in tracing chrooted ZTS target)
  -t, --template[=TEMPLATE]                  template name (phpspy|phpspy_with_opcode|json_lines) (default: phpspy)
  -o, --output=OUTPUT                        path to write output from this tool (default: stdout)
  -h, --help                                 Display help for the given command. When no command is given display help for the list command
  -q, --quiet                                Do not output any message
  -V, --version                              Display this application version
      --ansi|--no-ansi                       Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                       Do not ask any interactive question
  -v|vv|vvv, --verbose                       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Daemon mode
```bash
./reli inspector:daemon --help
Description:
  concurrently get call traces from processes whose command-lines match a given regex

Usage:
  inspector:daemon [options]

Options:
  -P, --target-regex=TARGET-REGEX            regex to find target processes which have matching command-line (required)
  -T, --threads[=THREADS]                    number of workers (default: 8)
  -d, --depth[=DEPTH]                        max depth
  -s, --sleep-ns[=SLEEP-NS]                  nanoseconds between traces (default: 1000 * 1000 * 10)
  -r, --max-retries[=MAX-RETRIES]            max retries on contiguous errors of read (default: 10)
  -S, --stop-process[=STOP-PROCESS]          stop the target process while reading its trace (default: off)
      --php-regex[=PHP-REGEX]                regex to find the php binary loaded in the target process
      --libpthread-regex[=LIBPTHREAD-REGEX]  regex to find the libpthread.so loaded in the target process
      --php-version[=PHP-VERSION]            php version (auto|v7[0-4]|v8[01]) of the target (default: auto)
      --php-path[=PHP-PATH]                  path to the php binary (only needed in tracing chrooted ZTS target)
      --libpthread-path[=LIBPTHREAD-PATH]    path to the libpthread.so (only needed in tracing chrooted ZTS target)
  -t, --template[=TEMPLATE]                  template name (phpspy|phpspy_with_opcode|json_lines) (default: phpspy)
  -o, --output=OUTPUT                        path to write output from this tool (default: stdout)
  -h, --help                                 Display help for the given command. When no command is given display help for the list command
  -q, --quiet                                Do not output any message
  -V, --version                              Display this application version
      --ansi|--no-ansi                       Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                       Do not ask any interactive question
  -v|vv|vvv, --verbose                       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### top-like mode
```bash
./reli inspector:top --help
Description:
  show an aggregated view of traces in real time in a form similar to the UNIX top command.

Usage:
  inspector:top [options]

Options:
  -P, --target-regex=TARGET-REGEX            regex to find target processes which have matching command-line (required)
  -T, --threads[=THREADS]                    number of workers (default: 8)
  -d, --depth[=DEPTH]                        max depth
  -s, --sleep-ns[=SLEEP-NS]                  nanoseconds between traces (default: 1000 * 1000 * 10)
  -r, --max-retries[=MAX-RETRIES]            max retries on contiguous errors of read (default: 10)
  -S, --stop-process[=STOP-PROCESS]          stop the target process while reading its trace (default: off)
      --php-regex[=PHP-REGEX]                regex to find the php binary loaded in the target process
      --libpthread-regex[=LIBPTHREAD-REGEX]  regex to find the libpthread.so loaded in the target process
      --php-version[=PHP-VERSION]            php version (auto|v7[0-4]|v8[01]) of the target (default: auto)
      --php-path[=PHP-PATH]                  path to the php binary (only needed in tracing chrooted ZTS target)
      --libpthread-path[=LIBPTHREAD-PATH]    path to the libpthread.so (only needed in tracing chrooted ZTS target)
  -h, --help                                 Display help for the given command. When no command is given display help for the list command
  -q, --quiet                                Do not output any message
  -V, --version                              Display this application version
      --ansi|--no-ansi                       Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                       Do not ask any interactive question
  -v|vv|vvv, --verbose                       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Get the address of EG
```bash
./reli inspector:eg --help
Description:
  get EG address from an outer process or thread

Usage:
  inspector:eg_address [options] [--] [<cmd> [<args>...]]

Arguments:
  cmd                                        command to execute as a target: either pid (via -p/--pid) or cmd must be specified
  args                                       command line arguments for cmd

Options:
  -p, --pid=PID                              process id
      --php-regex[=PHP-REGEX]                regex to find the php binary loaded in the target process
      --libpthread-regex[=LIBPTHREAD-REGEX]  regex to find the libpthread.so loaded in the target process
      --php-version[=PHP-VERSION]            php version (auto|v7[0-4]|v8[01]) of the target (default: auto)
      --php-path[=PHP-PATH]                  path to the php binary (only needed in tracing chrooted ZTS target)
      --libpthread-path[=LIBPTHREAD-PATH]    path to the libpthread.so (only needed in tracing chrooted ZTS target)
  -h, --help                                 Display help for the given command. When no command is given display help for the list command
  -q, --quiet                                Do not output any message
  -V, --version                              Display this application version
      --ansi|--no-ansi                       Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                       Do not ask any interactive question
  -v|vv|vvv, --verbose                       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

## Examples
### Trace a script
```bash
$ ./reli i:trace -- php -r "fgets(STDIN);"
0 fgets <internal>:-1
1 <main> <internal>:-1

0 fgets <internal>:-1
1 <main> <internal>:-1

0 fgets <internal>:-1
1 <main> <internal>:-1

<press q to exit>
...
```

### Attach to a running process
```bash
$ sudo php ./reli i:trace -p 2182685
0 time_nanosleep <internal>:-1
1 Reli\Lib\Loop\LoopMiddleware\NanoSleepMiddleware::invoke /home/sji/work/reli/src/Lib/Loop/LoopMiddleware/NanoSleepMiddleware.php:33
2 Reli\Lib\Loop\LoopMiddleware\KeyboardCancelMiddleware::invoke /home/sji/work/reli/src/Lib/Loop/LoopMiddleware/KeyboardCancelMiddleware.php:39
3 Reli\Lib\Loop\LoopMiddleware\RetryOnExceptionMiddleware::invoke /home/sji/work/reli/src/Lib/Loop/LoopMiddleware/RetryOnExceptionMiddleware.php:37
4 Reli\Lib\Loop\Loop::invoke /home/sji/work/reli/src/Lib/Loop/Loop.php:26
5 Reli\Command\Inspector\GetTraceCommand::execute /home/sji/work/reli/src/Command/Inspector/GetTraceCommand.php:133
6 Symfony\Component\Console\Command\Command::run /home/sji/work/reli/vendor/symfony/console/Command/Command.php:291
7 Symfony\Component\Console\Application::doRunCommand /home/sji/work/reli/vendor/symfony/console/Application.php:979
8 Symfony\Component\Console\Application::doRun /home/sji/work/reli/vendor/symfony/console/Application.php:299
9 Symfony\Component\Console\Application::run /home/sji/work/reli/vendor/symfony/console/Application.php:171
10 <main> /home/sji/work/reli/reli:45

0 time_nanosleep <internal>:-1
1 Reli\Lib\Loop\LoopMiddleware\NanoSleepMiddleware::invoke /home/sji/work/reli/src/Lib/Loop/LoopMiddleware/NanoSleepMiddleware.php:33
2 Reli\Lib\Loop\LoopMiddleware\KeyboardCancelMiddleware::invoke /home/sji/work/reli/src/Lib/Loop/LoopMiddleware/KeyboardCancelMiddleware.php:39
3 Reli\Lib\Loop\LoopMiddleware\RetryOnExceptionMiddleware::invoke /home/sji/work/reli/src/Lib/Loop/LoopMiddleware/RetryOnExceptionMiddleware.php:37
4 Reli\Lib\Loop\Loop::invoke /home/sji/work/reli/src/Lib/Loop/Loop.php:26
5 Reli\Command\Inspector\GetTraceCommand::execute /home/sji/work/reli/src/Command/Inspector/GetTraceCommand.php:133
6 Symfony\Component\Console\Command\Command::run /home/sji/work/reli/vendor/symfony/console/Command/Command.php:291
7 Symfony\Component\Console\Application::doRunCommand /home/sji/work/reli/vendor/symfony/console/Application.php:979
8 Symfony\Component\Console\Application::doRun /home/sji/work/reli/vendor/symfony/console/Application.php:299
9 Symfony\Component\Console\Application::run /home/sji/work/reli/vendor/symfony/console/Application.php:171
10 <main> /home/sji/work/reli/reli:45

<press q to exit>
...
```
The executing process must have the CAP_SYS_PTRACE capability. (Usually run as root is enough.)

### Daemon mode
```bash
$ sudo php ./reli i:daemon -P "^/usr/sbin/httpd"
``` 
The executing process must have the CAP_SYS_PTRACE capability. (Usually run as root is enough.)

### Get the address of EG
```bash
$ sudo php ./reli i:eg -p 2183131
0x555ae7825d80
``` 
The executing process must have the CAP_SYS_PTRACE capability. (Usually run as root is enough.)

### Show currently executing opcodes at traces
If a user wants to profile a really CPU-bound application, then he or she wouldn't only want to know what line is slow, but what opcode is. In such cases, use `--template=phpspy_with_opcode` with `inspector:trace` or `inspector:daemon`.

```bash
$ sudo php ./reli i:trace --template=phpspy_with_opcode -p <pid of the target process or thread>
```

The output would be like the following.

```
0 <VM>::ZEND_ASSIGN <VM>:-1
1 Mandelbrot::iterate /home/sji/work/test/mandelbrot.php:33:ZEND_ASSIGN
2 Mandelbrot::__construct /home/sji/work/test/mandelbrot.php:12:ZEND_DO_FCALL
3 <main> /home/sji/work/test/mandelbrot.php:45:ZEND_DO_FCALL

0 <VM>::ZEND_ASSIGN <VM>:-1
1 Mandelbrot::iterate /home/sji/work/test/mandelbrot.php:30:ZEND_ASSIGN
2 Mandelbrot::__construct /home/sji/work/test/mandelbrot.php:12:ZEND_DO_FCALL
3 <main> /home/sji/work/test/mandelbrot.php:45:ZEND_DO_FCALL
```

The currently executing opcode becomes the first frame of the callstack.
So visualizations of the trace like flamegraph can show the usage of opcodes.

For informational purposes, executing opcodes are also added to each end of the call frames. Except for the first frame, opcodes for function calls such as ZEND_DO_FCALL should appear there.

If JIT is enabled at the target process, this information may be slightly inaccurate.

### Use in a docker container and target a process on host
```bash
$ docker pull reliforp/reli-prof
$ docker run -it --security-opt="apparmor=unconfined" --cap-add=SYS_PTRACE --pid=host reliforp/reli-prof i:trace -p <pid of the target process or thread>
```

### Generate flamegraphs from traces
```bash
$ ./reli i:trace -o traces -- php ./vendor/bin/psalm --no-cache
$ ./reli c:flamegraph <traces >flame.svg
$ google-chrome flame.svg
```

The generated flamegraph below visualizes traces from the execution of the psalm command.

![flame](https://user-images.githubusercontent.com/6488121/153741551-3f0fc730-c748-4908-b8ac-7c3f46a5bdbc.svg)

### Generate the [speedscope](https://github.com/jlfwong/speedscope) format from phpspy compatible traces
```bash
$ sudo php ./reli i:trace -p <pid of the target process or thread> >traces
$ ./reli c:speedscope <traces >profile.speedscope.json
$ speedscope profile.speedscope.json
```

See [#101](https://github.com/reliforp/reli-prof/pull/101).

## Troubleshooting
### I get an error message "php module not found" and can't get a trace!
If your PHP binary uses a non-standard binary name that does not end with `/php`, use the `--php-regex` option to specify the name of the executable (or shared object) that contains the PHP interpreter.

### I don't think the trace is accurate.
The `-S` option will give you better results. Using this option stops the execution of the target process for a moment at every sampling, but the trace obtained will be more accurate. If you don't stop the VMs from running when profiling CPU-heavy programs such as benchmarking programs, you may misjudge the bottleneck, because you will miss more VM states that transition very quickly and are not detected well.

### Trace retrieval from ZTS target does not work on Ubuntu 21.10 or later.
Try to specify `--libpthread-regex="libc.so"` as an option.

### I can't get traces on Amazon Linux 2.
First, try `cat /proc/<pid>/maps` to check the memory map of the target PHP process. If the first module does not indicate the location of the PHP binary and looks like an anonymous region, try to specify `--php-regex="^$"` as an option.

## Goals
We would like to achieve the following 5 goals through this project.

- To be able to closely observe what is happening inside a running PHP script.
- To be a framework for PHP programmers to create a freely customizable PHP profiler.
- To be experimentation for the use of PHP outside of the web, where recent improvements of PHP like JIT and FFI have opened the door.
- Another entry point for PHP programmers to learn about PHP's internal implementation.
- To create a program that is fun to write for me.

## LICENSE
- MIT (mostly)
- tools/flamegraph/flamegraph.pl is copied from https://github.com/brendangregg/FlameGraph and licenced under the CDDL 1.0. See tools/flamegraph/docs/cddl1.txt and the header of the script.
- Some C headers defining internal structures are extracted from php-src. They are licensed under the zend engine license. See src/Lib/PhpInternals/Headers . So here are the words required by the zend engine license.
```
This product includes the Zend Engine, freely available at
     http://www.zend.com
```

## What does the name "Reli" mean?

"Reli" means nothing, though you are free to think of this tool as a something reliable, or religious, or relishable, or whatever other reli-s as you like.

Originally the name of this tool was just "php-profiler".
Due to a licensing problem (#175), this simple good name had to be changed.

So we applied a randomly chosen string manipulation function to the original name. `strrev('php-profiler')` results to `'reliforp-php'`, and it can be read as "reli for p(php)".
Thus the name of this tool is "Reli for PH*" now. And you can also call it just "Reli".

## See also
- [adsr/phpspy](https://github.com/adsr/phpspy)
    - Reli is heavily inspired by phpspy.
