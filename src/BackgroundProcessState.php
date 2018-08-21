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

use Symfony\Component\Process\Process;

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
     * @var BackgroundProcessConfig
     */
    private $config;

    /**
     * @var int
     */
    private $pid;

    /**
     * @param int                     $pid
     * @param BackgroundProcessConfig $config
     */
    public function __construct(int $pid, BackgroundProcessConfig $config)
    {
        $this->pid    = $pid;
        $this->config = $config;
    }

    /**
     * @return BackgroundProcessConfig
     */
    public function getConfig(): BackgroundProcessConfig
    {
        return $this->config;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @param BackgroundProcessConfig $config
     *
     * @return static
     */
    public function setConfig(BackgroundProcessConfig $config)
    {
        $this->config = $config;

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
}
