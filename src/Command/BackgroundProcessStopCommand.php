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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Stops a background process.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 * @author Derek Chafin <infomaniac50@gmail.com>
 */
class BackgroundProcessStopCommand extends Command
{
    protected static $defaultName = 'background_process:stop';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument('pid', InputArgument::REQUIRED)
            ->addOption('signal', 's', InputOption::VALUE_REQUIRED)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        if (null === $pid = $input->getArgument('pid')) {
            if (!$pid) {
                $io->error('The pid argument must be set.');

                return 1;
            }
        }

        try {
            $backgroundProcess = new BackgroundProcess($input, $output);

            $backgroundProcess->stop($input->getArgument("pid"));
            $io->success('Stopped the background command.');
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }
    }
}
