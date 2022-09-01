<?php

namespace sinri\ark\database\sqlite\v2\exception;
/**
 * @since 2.0
 * @since 2.1 updated constructor
 */
class ArkSqlite3BindException extends ArkSqlite3Exception
{
    /**
     * @var string
     * @since 2.1
     */
    private $sql;
    /**
     * @var string
     * @since 2.1
     */
    private $key;
    /**
     * @var mixed
     * @since 2.1
     */
    private $value;
    public function __construct(string $message, int $code, ArkSqlite3Exception $previous,string $sql="",string $key=null,$value=null)
    {
        parent::__construct($message, $code, $previous);
        $this->sql=$sql;
        $this->key=$key;
        $this->value=$value;
    }

    /**
     * @return string
     * @since 2.1
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return string|null
     * @since 2.1
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     * @since 2.1
     */
    public function getValue()
    {
        return $this->value;
    }
}