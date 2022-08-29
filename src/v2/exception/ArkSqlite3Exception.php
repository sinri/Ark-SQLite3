<?php

namespace sinri\ark\database\sqlite\v2\exception;

use Exception;
use Throwable;
/**
 * @since 2.0
 */
class ArkSqlite3Exception extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}