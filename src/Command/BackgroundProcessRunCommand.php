<?php

/*
 * This file is part of the BackgroundProcess package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Derek Chafin <infomaniac50@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocur\BackgroundProcess\Command;

use Cocur\BackgroundProcess\BackgroundProcess;
use Cocur\BackgroundProcess\BackgroundProcessConfig;
use Cocur\BackgroundProcess\BackgroundProcessStateManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * Runs an executable.
 *
 * @author Michał Pipa <michal.pipa.xsolve@gmail.com>
 * @author Derek Chafin <infomaniac50@gmail.com>
 */
class BackgroundProcessRunCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'background_process:run';

    /**
     * @var string|null $environment
     */
    private $environment;

    /**
     * @var BackgroundProcessStateManager $manager
     */
    private $manager;

    /**
     * @param BackgroundProcessStateManager $manager
     * @param string|null                   $environment
     */
    public function __construct(BackgroundProcessStateManager $manager, string $environment = null)
    {
        $this->environment = $environment;
        $this->manager     = $manager;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument('command_line', InputArgument::IS_ARRAY | InputArgument::REQUIRED)
            ->addOption('signal', 's', InputOption::VALUE_REQUIRED)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        if (null === $command = $input->getArgument('command_line')) {
            if (!$command) {
                $io->error('The command_line argument must be set.');

                return 1;
            }
        }

        $callback      = null;
        $disableOutput = false;
        if ($output->isQuiet()) {
            $disableOutput = true;
        } else {
            $callback = function ($type, $buffer) use ($output) {
                if (Process::ERR === $type && $output instanceof ConsoleOutputInterface) {
                    $output = $output->getErrorOutput();
                }
                $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
            };
        }

        try {
            $backgroundProcess = new BackgroundProcess($input, $output, $this->manager);
            $config            = new BackgroundProcessConfig($command, $input->getOption('signal'));

            $io->comment('Quit the command with CONTROL-C.');

            $exitCode = $backgroundProcess->run($config, $disableOutput, $callback);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        return $exitCode;
    }
}
