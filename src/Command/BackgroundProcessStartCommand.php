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
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Runs an executable in a background process.
 *
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 * @author Derek Chafin <infomaniac50@gmail.com>
 */
class BackgroundProcessStartCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'background_process:start';

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

        if (!\extension_loaded('pcntl')) {
            $io->error(array(
                'This command needs the pcntl extension to run.',
                'You can either install it or use the "background_process:run" command instead.',
            ));

            if ($io->confirm('Do you want to execute <info>background_process:run</info> immediately?', false)) {
                return $this->getApplication()->find('background_process:run')->run($input, $output);
            }

            return 1;
        }

        // replace event dispatcher with an empty one to prevent console.terminate from firing
        // as container could have changed between start and stop
        $this->getApplication()->setDispatcher(new EventDispatcher());

        if (null === $command = $input->getArgument('command_line')) {
            if (!$command) {
                $io->error('The command_line argument must be set.');

                return 1;
            }
        }

        try {
            $backgroundProcess = new BackgroundProcess($input, $output, $this->manager);
            $config            = new BackgroundProcessConfig($command, $input->getOption('signal'));

            if (BackgroundProcess::STARTED === $backgroundProcess->start($config)) {
                $io->success('Command started');
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }
    }
}
