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
use SQLite3;
use SQLite3Result;

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
    /**
     * @var SQLite3
     */
    private $pidDb;

    /**
     * @param string|null $pidFile
     */
    public function __construct(string $pidFile = null)
    {
        /** @var SQLite3 $pidDb */
        if (null === $pidDb = $this->getDatabase($pidFile)) {
            throw new \RuntimeException("Could not open PID state file.");
        }

        $this->pidDb = $pidDb;
        $this->init();
    }

    /**
     * @param int    $pid
     * @param string $command
     * @param int    $signal
     *
     * @return bool a boolean value indicating if a record was added.
     */
    public function add(int $pid, $command, $signal)
    {
        $sql = <<<SQL
INSERT INTO main.processes (pid, comand, signal)
VALUES
    (:pid, :command, :signal)
SQL;
        $statement = $this->pidDb->prepare($sql);

        $statement->bindValue('pid', $pid, SQLITE3_INTEGER);
        $statement->bindValue('command', $command, SQLITE3_TEXT);
        $statement->bindValue('signal', $signal, SQLITE3_INTEGER);

        if (false !== $result = $statement->execute()) {
            $result->finalize();

            return $this->pidDb->changes() > 0;
        }

        return false;
    }

    /**
     * @return BackgroundProcessState[]
     */
    public function all()
    {
        /** @var SQLite3Result $result */
        $result = $this->pidDb->query("SELECT * FROM main.processes");

        /** @var BackgroundProcessState[] $state */
        $states = array();

        if ($result instanceof SQLite3Result) {
            foreach ($result as $row) {
                $states[] = unserialize($row['state']);
            }
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
        $result = $this->pidDb->querySingle("SELECT COUNT(pid) FROM main.processes WHERE pid=$pid");

        return !is_null($result) && $result > 0;
    }

    /**
     * @param int $pid
     *
     * @return BackgroundProcessState|null
     */
    public function get(int $pid):  ? BackgroundProcessState
    {
        $result = $this->pidDb->querySingle("SELECT pid, command, signal FROM main.processes WHERE pid=$pid", true);

        if (false === $result || count($results) === 0) {
            return null;
        }

        return new BackgroundProcessState(...$result);
    }

    /**
     * @param int $pid
     *
     * @return bool a boolean value indicating if a record was removed.
     */
    public function remove(int $pid)
    {
        $sql = "DELETE FROM main.processes WHERE pid = :pid";

        $statement = $this->pidDb->prepare($sql);

        $statement->bindValue(':pid', $pid, SQLITE3_INTEGER);

        if (false !== $result = $statement->execute()) {
            $result->finalize();

            return $this->pidDb->changes() > 0;
        }

        return false;
    }

    /**
     * @param string|null $pidFile
     *
     * @return SQLite3
     */
    private function getDatabase($pidFile = null)
    {
        $pidFile = $pidFile ?: $this->getDefaultPidFile();

        $pidDb = new SQLite3($pidFile);

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
        $this->pidDb->enableExceptions(true);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS main.processes (
    pid INTEGER PRIMARY KEY,
    command TEXT,
    signal INTEGER
);
SQL;
        $this->pidDb->exec($sql);
    }
}
