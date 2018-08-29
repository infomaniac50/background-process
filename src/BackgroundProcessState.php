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

/**
 * BackgroundProcessState.
 *
 * Stores state background processes.
 *
 * @author    Derek Chafin <infomaniac50@gmail.com>
 *
 * @copyright 2018 Derek Chafin
 *
 * @license   http://opensource.org/licenses/MIT The MIT License
 */
class BackgroundProcessState
{
    /**
     * The command to execute
     *
     * @var string
     */
    private $command;

    /**
     * @var int
     */
    private $pid;

    /**
     * @var int|null
     */
    private $signal;

    /**
     * @param int      $pid
     * @param string   $command The command to execute
     * @param int|null $signal
     */
    public function __construct(int $pid, $command, $signal = null)
    {
        $this->pid     = $pid;
        $this->command = $command;
        $this->signal  = $signal;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @return int|null
     */
    public function getSignal():  ? int
    {
        return $this->signal;
    }

    /**
     * @param string $command
     *
     * @return static
     */
    public function setCommand(string $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @param int $pid
     *
     * @return static
     */
    public function setPid(int $pid)
    {
        $this->pid = $pid;

        return $this;
    }

    /**
     * @param int|null $signal
     *
     * @return static
     */
    public function setSignal(int $signal = null)
    {
        $this->signal = $signal;

        return $this;
    }
}
