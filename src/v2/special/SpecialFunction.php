<?php

namespace sinri\ark\database\sqlite\v2\special;
/**
 * @since 2.0
 */
interface SpecialFunction
{
    /**
     * Name of the SQL function to be created or redefined.
     * @return string
     */
    public function getName(): string;

    /**
     * The name of a PHP function or user-defined function to apply as a callback,
     *  defining the behavior of the SQL function.
     * @param mixed $value The first argument passed to the SQL function.
     * @param mixed ...$values Further arguments passed to the SQL function.
     * @return mixed
     */
    public function callback($value, ...$values);

    /**
     * The number of arguments that the SQL function takes.
     * If this parameter is -1, then the SQL function may take any number of arguments.
     * @return int
     */
    public function getArgumentCount(): int;

    /**
     * A bitwise conjunction of flags.
     * Currently, only `SQLITE3_DETERMINISTIC` is supported,
     *  which specifies that the function always returns the same result given the same inputs within a single SQL statement.
     * @return int
     * Note: AVAILABLE since PHP 7.1.4
     */
    public function getFlags(): int;
}