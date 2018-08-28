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
     * @param int                    $pid
     * @param BackgroundProcessState $value
     *
     * @return void
     */
    public function add(int $pid, BackgroundProcessState $value)
    {
        $sql = <<<SQL
INSERT INTO main.processes (pid, state)
VALUES
    (:pid, :state)
SQL;
        $statement = $this->pidDb->prepare($sql);

        $statement->bindValue('pid', $pid, SQLITE3_INTEGER);
        $statement->bindValue('state', serialize($value), SQLITE3_BLOB);

        if (false !== $result = $statement->execute()) {
            $result->finalize();
        }
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
        /** @var BackgroundProcessState $state */
        $state = $this->pidDb->querySingle("SELECT state FROM main.processes WHERE pid=$pid");

        if (!is_null($state)) {
            $state = unserialize($state);
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

        $this->pidDb->exec($sql);
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
    state BLOB
);
SQL;
        $this->pidDb->exec($sql);
    }
}
