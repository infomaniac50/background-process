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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Shows the status of a process that is running in the background.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 * @author Derek Chafin <infomaniac50@gmail.com>
 */
class BackgroundProcessStatusCommand extends Command
{
    protected static $defaultName = 'background_process:status';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDefinition(array(
                new InputOption('pidfile', null, InputOption::VALUE_REQUIRED, 'PID file'),
                new InputOption('filter', null, InputOption::VALUE_REQUIRED, 'The value to display (one of port, host, or address)'),
            ));
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);
        $server = new BackgroundProcess();
        if ($filter = $input->getOption('filter')) {
            if ($server->isRunning($input->getOption('pidfile'))) {
                list($host, $port) = explode(':', $address = $server->getAddress($input->getOption('pidfile')));
                if ('address' === $filter) {
                    $output->write($address);
                } elseif ('host' === $filter) {
                    $output->write($host);
                } elseif ('port' === $filter) {
                    $output->write($port);
                } else {
                    throw new InvalidArgumentException(sprintf('"%s" is not a valid filter.', $filter));
                }
            } else {
                return 1;
            }
        } else {
            if ($server->isRunning($input->getOption('pidfile'))) {
                $io->success(sprintf('Web server still listening on http://%s', $server->getAddress($input->getOption('pidfile'))));
            } else {
                $io->warning('No web server is listening.');

                return 1;
            }
        }
    }
}
