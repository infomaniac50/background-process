<?php
/*
 * This file is part of the BackgroundProcess package.
 *
 * (c) Florian Eckerstorfer
 * (c) Derek Chafin <infomaniac50@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cocur\BackgroundProcess;

use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * BackgroundProcess.
 *
 * Runs a process in the background.
 *
 * @author    Florian Eckerstorfer <florian@eckerstorfer.co>
 * @author    Derek Chafin <infomaniac50@gmail.com>
 *
 * @copyright 2013-2015 Florian Eckerstorfer
 * @copyright 2018 Derek Chafin
 *
 * @license   http://opensource.org/licenses/MIT The MIT License
 *
 * @see      https://florian.ec/articles/running-background-processes-in-php/ Running background processes in PHP
 */
class BackgroundProcess
{
    /**
     * @var int
     */
    const STARTED = 0;

    /**
     * @var int
     */
    const STOPPED = 1;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    /**
     * @param BackgroundProcessConfig $config
     * @param bool                    $disableOutput
     * @param callable|null           $callback
     */
    public function run(BackgroundProcessConfig $config, $disableOutput = true, callable $callback = null)
    {
        $process = $this->createServerProcess($config);
        if ($disableOutput) {
            $process->disableOutput();
            $callback = null;
        } else {
            try {
                $process->setTty(true);
                $callback = null;
            } catch (RuntimeException $e) {
            }
        }

        $process->run($callback);

        if (!$process->isSuccessful()) {
            $error = 'Server terminated unexpectedly.';
            if ($process->isOutputDisabled()) {
                $error .= ' Run the command again with -v option for more details.';
            }

            throw new \RuntimeException($error);
        }
    }

    /**
     * @param BackgroundProcessConfig $config
     * @param null                    $pidFile
     *
     * @return int
     */
    public function start(BackgroundProcessConfig $config, $pidFile = null)
    {
        $pid = pcntl_fork();

        if ($pid < 0) {
            throw new \RuntimeException('Unable to start the server process.');
        }

        if ($pid > 0) {
            return self::STARTED;
        }

        if (posix_setsid() < 0) {
            throw new \RuntimeException('Unable to set the child process as session leader.');
        }

        $manager = new BackgroundProcessStateManager($pidFile);

        $process = $this->createServerProcess($config);
        $process->disableOutput();
        $process->start();

        if (!$process->isRunning($config)) {
            throw new \RuntimeException('Unable to start the server process.');
        }

        $state = new BackgroundProcessState($process->getPid(), $config);
        $manager->add($process->getPid(), $state);

        // stop the web server when the lock file is removed
        while ($process->isRunning()) {
            if (!array_key_exists($process->getPid(), $manager)) {
                $process->stop(10, $config->getSignal());
            }

            sleep(1);
        }

        return self::STOPPED;
    }

    /**
     * @param int         $pid
     * @param string|null $pidFile
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public function stop(int $pid, $pidFile = null)
    {
        $manager = new BackgroundProcessStateManager($pidFile);

        if (!$manager->exists($pid)) {
            throw new \RuntimeException(sprintf('The process with PID %d does not exist.', $pid));
        }

        $manager->remove($pid);
    }

    /**
     * @param BackgroundProcessConfig $config
     *
     * @return Process The process
     */
    private function createServerProcess(BackgroundProcessConfig $config)
    {
        $argv = $config->getCommand();
        $argc = array_shift($argv);

        $finder = new ExecutableFinder();
        if (false === $binary = $finder->find($argc)) {
            throw new \RuntimeException('Unable to find the binary.');
        }

        $process = new Process(array_merge(array($binary), $argv));
        $process->setWorkingDirectory(posix_getcwd());
        $process->setTimeout(null);

        if (\in_array('APP_ENV', explode(',', getenv('SYMFONY_DOTENV_VARS')))) {
            $process->setEnv(array('APP_ENV' => false));
            $process->inheritEnvironmentVariables();
        }

        return $process;
    }
}
