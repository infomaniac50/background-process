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

use Cocur\BackgroundProcess\BackgroundProcessState;
use Cocur\BackgroundProcess\BackgroundProcessStateManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Shows the status of a process that is running in the background.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 * @author Derek Chafin <infomaniac50@gmail.com>
 */
class BackgroundProcessListCommand extends Command
{
    protected static $defaultName = 'background_process:list';

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output);

        $states = $this->manager->all();

        $io->table(
            array("PID", "Command"),
            array_map(
                function ($value) {
                    /** @var BackgroundProcessState $value */

                    return array(
                        $value->getPid(),
                        $value->getCommand(),
                    );
                },
                $states
            )
        );
    }
}
