<?php

namespace sinri\ark\database\sqlite\v2\exception;
use Throwable;

/**
 * @since 2.0
 * @since 2.1 updated constructor
 */
class ArkSqlite3PrepareException extends ArkSqlite3Exception
{
    /**
     * @var string
     * @since 2.1
     */
    private $sql;
    public function __construct(string $message, int $code, ArkSqlite3Exception $previous,string $sql="")
    {
        parent::__construct($message, $code, $previous);
        $this->sql=$sql;
    }

    /**
     * @return string
     * @since 2.1
     */
    public function getSql(): string
    {
        return $this->sql;
    }
}