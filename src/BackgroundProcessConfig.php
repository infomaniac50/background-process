<?php

/*
 * This file is part of the BackgroundProcess package.
 *
 * (c) Derek Chafin <infomaniac50@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocur\BackgroundProcess;

/**
 * @author Derek Chafin <infomaniac50@gmail.com>
 */
class BackgroundProcessConfig
{
    /**
     * The command to execute
     *
     * @var string
     */
    private $command;

    /**
     * @var int|null
     */
    private $signal;

    /**
     * @param string   $command The command to execute
     * @param int|null $signal
     */
    public function __construct($command, $signal = null)
    {
        $this->command = $command;
        $this->signal  = $signal;
    }

    /**
     * Get the command to execute
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return int|null
     */
    public function getSignal()
    {
        return $this->signal;
    }

    /**
     * @param string $command The command to execute
     *
     * @return static
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @param int|null $signal
     *
     * @return static
     */
    public function setSignal($signal)
    {
        $this->signal = $signal;

        return $this;
    }
}
