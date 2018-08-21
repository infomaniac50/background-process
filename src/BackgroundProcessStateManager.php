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
use SQLiteDatabase;
use SQLiteException;
use SQLiteResult;

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
class BackgroundProcessStateManager
{
    private $errorMessage;

    /**
     * @var SQLiteDatabase
     */
    private $pidDb;

    /**
     * @param string|null $pidFile
     */
    public function __construct(string $pidFile = null)
    {
        /** @var SQLiteDatabase $pidDb */
        if (null === $pidDb = $this->getDatabase($pidFile)) {
            throw new \RuntimeException("Could not open PID state file.");
        }

        $this->pidDb = $pidDb;
        $this->init();
    }

    /**
     * @param int                    $pid
     * @param BackgroundProcessState $value
     *
     * @return void
     */
    public function add(int $pid, BackgroundProcessState $value)
    {
        $sql = <<<SQL
INSERT INTO main.processes (pid, command, state)
VALUES
    (%d, %s, %s)
SQL;
        $this->pidDb->queryExec(sprintf($sql, $pid, $value->getCommandLine(), serialize($value)));
    }

    /**
     * @return BackgroundProcessState[]
     */
    public function all()
    {
        /** @var SQLiteResult $result */
        $result = $this->pidDb->query("SELECT * FROM main.processes");

        /** @var BackgroundProcessState[] $state */
        $states = array();

        foreach ($result as $row) {
            $states[] = unserialize($row['state']);
        }

        return $states;
    }

    /**
     * @param int $pid
     *
     * @return bool
     */
    public function exists(int $pid): bool
    {
        /** @var SQLiteResult $result */
        $result = $this->pidDb->query("SELECT * FROM main.processes WHERE pid=$pid");

        return $result->numRows() > 0;
    }

    /**
     * @param int $pid
     *
     * @return BackgroundProcessState|null
     */
    public function get(int $pid):  ? BackgroundProcessState
    {
        /** @var SQLiteResult $result */
        $result = $this->pidDb->query("SELECT * FROM main.processes WHERE pid=$pid");

        /** @var BackgroundProcessState $state */
        $state = null;

        if ($result->numRows() > 0) {
            $row   = $result->fetch();
            $state = unserialize($row['state']);
        }

        return $state;
    }

    /**
     * @param int $pid
     *
     * @return void
     */
    public function remove(int $pid)
    {
        $sql = "DELETE FROM main.processes WHERE pid={$pid}";

        $this->pidDb->queryExec($sql);
    }

    /**
     * @param string|null $pidFile
     *
     * @return SQLiteDatabase|null
     */
    private function getDatabase($pidFile = null)
    {
        $pidFile = $pidFile ?: $this->getDefaultPidFile();

        $pidDb = new SQLiteDatabase($pidFile, 0666, $this->errorMessage);

        return $pidDb;
    }

    /**
     * @return string
     */
    private function getDefaultPidFile()
    {
        return posix_getcwd()."/.pids.db";
    }

    /**
     * @return void
     */
    private function init()
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS main.processes (
    pid INTEGER PRIMARY KEY,
    command TEXT,
    state BLOB
);
SQL;
        if (!$this->pidDb->queryExec($sql, $this->errorMessage)) {
            throw new RuntimeException(
                "Could not setup PID state file.",
                $this->pidDb->lastError(),
                new SQLiteException($this->errorMessage)
            );
        }
    }
}
