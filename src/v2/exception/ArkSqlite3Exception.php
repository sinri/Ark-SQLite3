<?php

namespace sinri\ark\database\sqlite\v2\exception;

use Exception;
use Throwable;
/**
 * @since 2.0
 * @since 2.1 updated constructor
 */
class ArkSqlite3Exception extends Exception
{
    public function __construct(string $message, int $code,Throwable $throwable=null)
    {
        parent::__construct($message, $code, $throwable);
    }
}