<?php

namespace sinri\ark\database\sqlite\v2;

use sinri\ark\database\sqlite\v2\exception\ArkSqlite3BindException;
use sinri\ark\database\sqlite\v2\exception\ArkSqlite3Exception;
use sinri\ark\database\sqlite\v2\exception\ArkSqlite3PrepareException;
use sinri\ark\database\sqlite\v2\exception\ArkSqlite3QueryException;
use sinri\ark\database\sqlite\v2\special\SpecialAggregate;
use sinri\ark\database\sqlite\v2\special\SpecialCollation;
use sinri\ark\database\sqlite\v2\special\SpecialFunction;
use SQLite3;
use SQLite3Result;
use SQLite3Stmt;

/**
 * @since 2.0
 */
class ArkSqlite3
{
    private $db;

    /**
     * LibSqlite3 constructor.
     * @param string $filename filename or `:memory:`
     * @param int|null $flags made of SQLITE3_OPEN_READONLY, SQLITE3_OPEN_READWRITE and SQLITE3_OPEN_CREATE
     * @param string|null $encryption_key
     */
    public function __construct(string $filename, int $flags = null, string $encryption_key = null)
    {

        // SQLITE3_OPEN_READONLY: Open the database for reading only.
        // SQLITE3_OPEN_READWRITE: Open the database for reading and writing.
        // SQLITE3_OPEN_CREATE: Create the database if it does not exist.
        if ($flags === null) {
            $flags = SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE;
        }
        $this->db = new SQLite3($filename, $flags, $encryption_key);
        $this->setBusyTimeout(100);// 0.1s
//        $this->enableExceptions(false);
//          @see https://www.sqlite.org/wal.html
//        try {
//            $this->execute('PRAGMA journal_mode = wal;');
//        } catch (ArkSqlite3QueryException $e) {
//
//        }
//        $this->execute('PRAGMA synchronous = NORMAL;');
//        $this->execute('PRAGMA schema.taille_cache = 16000;');
    }

    protected function getSqlite3Instance(): SQLite3
    {
        return $this->db;
    }

    /**
     * Sets a busy handler that will sleep until the database is not locked or the timeout is reached.
     * @param int $milliseconds The milliseconds to sleep. Setting this value to a value less than or equal to zero, will turn off an already set timeout handler.
     * @return bool Returns true on success, or false on failure.
     */
    public function setBusyTimeout(int $milliseconds): bool
    {
        return $this->getSqlite3Instance()->busyTimeout($milliseconds);
    }

    // method backup is supported since 7.4

    /**
     * Returns the number of database rows that were changed (or inserted or deleted) by the most recent SQL statement.
     * @return int Returns an int value corresponding to the number of database rows changed (or inserted or deleted) by the most recent SQL statement.
     */
    public function getAffectedRowCount(): int
    {
        return $this->getSqlite3Instance()->changes();
    }

    /**
     * Closes the database connection
     * @return bool Returns true on success or false on failure.
     */
    public function close(): bool
    {
        return $this->getSqlite3Instance()->close();
    }

    /**
     * Wrapper of method `createAggregate`.
     * Registers a PHP function or user-defined function for use as an SQL aggregate function for use within SQL statements.
     * @param SpecialAggregate $specialAggregate
     * @return bool Returns true upon successful creation of the aggregate, or false on failure.
     */
    public function registerSpecialAggregate(SpecialAggregate $specialAggregate): bool
    {
        return $this->getSqlite3Instance()->createAggregate(
            $specialAggregate->getName(),
            [$specialAggregate, 'stepCallback'],
            [$specialAggregate, 'finalCallback'],
            $specialAggregate->getArgumentCount()
        );
    }

    /**
     * Wrapper of method `createCollation`.
     * Registers a PHP function or user-defined function for use as a collating function within SQL statements.
     * @param SpecialCollation $specialCollation
     * @return bool Returns true on success or false on failure.
     */
    public function registerSpecialCollation(SpecialCollation $specialCollation): bool
    {
        return $this->getSqlite3Instance()->createCollation(
            $specialCollation->getName(),
            [$specialCollation, 'comparator']
        );
    }

    /**
     * Wrapper of method `createFunction`.
     * Registers a PHP function or user-defined function for use as an SQL scalar function for use within SQL statements.
     * @param SpecialFunction $specialFunction
     * @return bool Returns true upon successful creation of the function, false on failure.
     */
    public function registerSpecialFunction(SpecialFunction $specialFunction): bool
    {
        return $this->getSqlite3Instance()->createFunction(
            $specialFunction->getName(),
            [$specialFunction, 'callback'],
            $specialFunction->getArgumentCount(),
            $specialFunction->getFlags()
        );
    }

    /**
     * Controls whether the SQLite3 instance will throw exceptions or warnings on error.
     * Parameter `enable`:
     *  When true, the SQLite3 instance, and SQLite3Stmt and SQLite3Result instances derived from it, will throw exceptions on error.
     *  When false, the SQLite3 instance, and SQLite3Stmt and SQLite3Result instances derived from it, will raise warnings on error.
     * For either mode, the error code and message, if any, will be available via SQLite3::lastErrorCode() and SQLite3::lastErrorMsg() respectively.
     * @param bool $enable
     * @return bool Returns the old value; true if exceptions were enabled, false otherwise.
     */
    public function enableExceptions(bool $enable = false): bool
    {
        return $this->getSqlite3Instance()->enableExceptions($enable);
    }

    /**
     * Returns a string that has been properly escaped for safe inclusion in an SQL statement.
     * Warning: This function is not (yet) binary safe!
     * To properly handle BLOB fields which may contain NUL characters, use SQLite3Stmt::bindParam() instead.
     * @param string $x
     * @return string
     */
    public static function getEscapedString(string $x): string
    {
        return SQLite3::escapeString($x);
    }

    /**
     * Executes a result-less query against a given database.
     * Note: SQLite3 may need to create temporary files during the execution of queries,
     *  so the respective directories may have to be writable.
     * @param string $query The SQL query to execute (typically an INSERT, UPDATE, or DELETE query).
     * @throws ArkSqlite3QueryException
     */
    public function execute(string $query)
    {
        $done = $this->getSqlite3Instance()->exec($query);
        if ($done === false) {
            throw new ArkSqlite3QueryException("Failed to query: " . $query, $this->getLastErrorCode(), $this->getLastError());
        }
    }

    /**
     * Executes a result-less query against a given database.
     * @param string $query The SQL query to execute (typically an INSERT, UPDATE, or DELETE query).
     * @return int affected row count
     * @throws ArkSqlite3QueryException
     */
    public function executeForAffectedRowCount(string $query): int
    {
        $this->execute($query);
        return $this->getAffectedRowCount();
    }

    /**
     * @return array key->value: versionString->str versionNumber->int
     */
    public static function getVersion(): array
    {
        return SQLite3::version();
    }

    public function getLastError(): ArkSqlite3Exception
    {
        return new ArkSqlite3Exception($this->getLastErrorMsg(), $this->getLastErrorCode());
    }

    public function getLastErrorCode(): int
    {
        return $this->getSqlite3Instance()->lastErrorCode();
    }

    public function getLastErrorMsg(): string
    {
        return $this->getSqlite3Instance()->lastErrorMsg();
    }

    /**
     * Returns the row ID of the most recent INSERT into the database.
     * @return int Returns the row ID of the most recent INSERT into the database; or 0.
     * If no successful INSERTs into rowid tables have ever occurred on this database connection, then SQLite3::lastInsertRowID() returns 0.
     */
    public function getLastInsertRowID(): int
    {
        return $this->getSqlite3Instance()->lastInsertRowID();
    }

    /**
     * Prepares an SQL statement for execution and returns an SQLite3Stmt object.
     * @param string $template The SQL query to prepare.
     * @return SQLite3Stmt
     * @throws ArkSqlite3PrepareException
     */
    public function prepare(string $template): SQLite3Stmt
    {
        $statement = $this->getSqlite3Instance()->prepare($template);
        if ($statement === false) {
            throw new ArkSqlite3PrepareException("Failed to prepare " . $template, $this->getLastErrorCode(), $this->getLastError());
        }
        return $statement;
    }

    /**
     * @param string $query The SQL query to execute.
     * @return SQLite3Result
     * @throws ArkSqlite3QueryException
     */
    public function query(string $query): SQLite3Result
    {
        $result = $this->getSqlite3Instance()->query($query);
        if ($result === false) {
            throw new ArkSqlite3QueryException("Failed to query " . $query, $this->getLastErrorCode(), $this->getLastError());
        }
        return $result;
    }

    /**
     * @param string $query The SQL query to execute.
     * @return array
     * @throws ArkSqlite3QueryException
     */
    public function querySingleRow(string $query): array
    {
        $result = $this->getSqlite3Instance()->querySingle($query, true);
        if ($result === false) {
            throw new ArkSqlite3QueryException("Failed to query " . $query, $this->getLastErrorCode(), $this->getLastError());
        } else {
            return $result;
        }
    }

    /**
     * @param string $query The SQL query to execute.
     * @return scalar|null
     * @throws ArkSqlite3QueryException
     */
    public function querySingleField(string $query)
    {
        $result = $this->getSqlite3Instance()->querySingle($query, false);
        if ($result === false) {
            throw new ArkSqlite3QueryException("Failed to query " . $query, $this->getLastErrorCode(), $this->getLastError());
        } else {
            return $result;
        }
    }

    /**
     * Execute auto bind query.
     * Note: the result returned should be finalized, statement outputed should be closed.
     * @param string $template The SQL query to execute (typically an INSERT, UPDATE, or DELETE query).
     * @param array $bindValueMap [":key"=>"value",...]
     * @param callable|null $callback function(SQLite3Result $result,SQLite3Stmt $statement):mixed
     * @return mixed decided by callback or null
     * @throws ArkSqlite3BindException
     * @throws ArkSqlite3PrepareException
     * @throws ArkSqlite3QueryException
     */
    protected function safeExecuteImpl(string $template, array $bindValueMap = [], callable $callback = null)
    {
        $statement = $this->prepare($template);
        foreach ($bindValueMap as $key => $value) {
            $done = $statement->bindValue($key, $value);
            if (!$done) {
                throw new ArkSqlite3BindException("Failed to bind value for placeholder [$key]", $this->getLastErrorCode(), $this->getLastError());
            }
        }
        $result = $statement->execute();
        if ($result === false) {
            throw new ArkSqlite3QueryException("Failed to query " . $template);
        }
        if ($callback === null) {
            $x = null;
        } else {
            $x = call_user_func_array($callback, [$result, $statement]);
        }
        $result->finalize();
        $statement->close();
        return $x;
    }

    /**
     * @param string $template
     * @param array $bindValueMap
     * @return mixed|null
     * @throws ArkSqlite3BindException
     * @throws ArkSqlite3PrepareException
     * @throws ArkSqlite3QueryException
     */
    public function safeExecute(string $template, array $bindValueMap = [])
    {
        return $this->safeExecuteImpl($template, $bindValueMap, function (SQLite3Result $result, SQLite3Stmt $statement) {
            return null;
        });

    }

    /**
     * Execute auto bind UPDATE/DELETE.
     * @param string $template The SQL query to execute (typically an UPDATE, or DELETE query).
     * @param array $bindValueMap [":key"=>"value",...]
     * @return int affected row count
     * @throws ArkSqlite3BindException
     * @throws ArkSqlite3PrepareException
     * @throws ArkSqlite3QueryException
     */
    public function safeModify(string $template, array $bindValueMap = []): int
    {
        return $this->safeExecuteImpl($template, $bindValueMap, function (SQLite3Result $result, SQLite3Stmt $statement) {
            return $this->getAffectedRowCount();
        });
    }

    /**
     * Execute auto bind INSERT.
     * Note: the result returned should be finalized.
     * @param string $template The SQL query to execute (typically an INSERT query).
     * @param array $bindValueMap [":key"=>"value",...]
     * @return int last inserted id
     * @throws ArkSqlite3BindException
     * @throws ArkSqlite3PrepareException
     * @throws ArkSqlite3QueryException
     */
    public function safeInsert(string $template, array $bindValueMap = []): int
    {
        return $this->safeExecuteImpl($template, $bindValueMap, function (SQLite3Result $result, SQLite3Stmt $statement) {
            return $this->getLastInsertRowID();
        });
    }

    /**
     * @param string $template
     * @param array $bindValueMap
     * @return array
     * @throws ArkSqlite3BindException
     * @throws ArkSqlite3PrepareException
     * @throws ArkSqlite3QueryException
     */
    public function safeQuery(string $template, array $bindValueMap = []): array
    {
        return $this->safeExecuteImpl($template, $bindValueMap, function (SQLite3Result $result, SQLite3Stmt $statement) {
            $rows = [];
            while (true) {
                $row = $result->fetchArray();
                if ($row === false) break;
                $rows[] = $row;
            }
            return $rows;
        });
    }

    /**
     * @param string $template
     * @param array $bindValueMap
     * @return mixed|null
     * @throws ArkSqlite3BindException
     * @throws ArkSqlite3PrepareException
     * @throws ArkSqlite3QueryException
     */
    public function safeQueryForRow(string $template, array $bindValueMap = [])
    {
        return $this->safeExecuteImpl($template, $bindValueMap, function (SQLite3Result $result, SQLite3Stmt $statement) {
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row === false) {
                return null;
            }
            return $row;
        });
    }

    /**
     * @param string $template
     * @param array $bindValueMap
     * @param int|string $whichColumn
     * @return array|null
     * @throws ArkSqlite3BindException
     * @throws ArkSqlite3PrepareException
     * @throws ArkSqlite3QueryException
     */
    public function safeQueryForColumn(string $template, array $bindValueMap = [], $whichColumn = 0): array
    {
        return $this->safeExecuteImpl($template, $bindValueMap, function (SQLite3Result $result, SQLite3Stmt $statement) use ($whichColumn) {
            $column = [];
            while (true) {
                $row = $result->fetchArray();
                if ($row === false) break;
                $column[] = $row[$whichColumn];
            }
            return $column;
        });
    }

    /**
     * @param string $template
     * @param array $bindValueMap
     * @param int|string $whichColumn
     * @return mixed|null
     * @throws ArkSqlite3BindException
     * @throws ArkSqlite3PrepareException
     * @throws ArkSqlite3QueryException
     */
    public function safeQueryForField(string $template, array $bindValueMap = [],$whichColumn = 0)
    {
        return $this->safeExecuteImpl($template, $bindValueMap, function (SQLite3Result $result, SQLite3Stmt $statement) use ($whichColumn) {
            $row = $result->fetchArray();
            if ($row === false) {
                return null;
            }
            return $row[$whichColumn];
        });
    }
}