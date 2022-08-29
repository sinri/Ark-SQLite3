<?php

use sinri\ark\core\ArkLogger;
use sinri\ark\database\sqlite\v2\ArkSqlite3;

require_once __DIR__.'/../vendor/autoload.php';

$logger = new ArkLogger(__DIR__ . '/../log', 'sqlite');
$db_file = __DIR__ . '/../log/test-sqlite3.db';
$db = new ArkSqlite3($db_file);

$created = $db->safeExecute("CREATE TABLE COMPANY(
   ID INTEGER PRIMARY KEY   AUTOINCREMENT,
   NAME           TEXT      NOT NULL,
   AGE            INT       NOT NULL,
   ADDRESS        CHAR(50),
   SALARY         REAL
);");
$logger->info('created table', [$created]);

$id = $db->safeInsert("INSERT INTO COMPANY (NAME,AGE,ADDRESS,SALARY)
VALUES ( 'Paul', 32, 'California', 20000.00 );");
$logger->info('inserted one', [$id]);
$id = $db->safeInsert("INSERT INTO COMPANY (NAME,AGE,ADDRESS,SALARY)
VALUES ('Allen', 25, 'Texas', 15000.00 );");
$logger->info('inserted another', [$id]);

$all = $db->safeQuery("SELECT id,name FROM company");
$logger->info('select all', [$all]);

$dropped = $db->safeExecute("DROP TABLE COMPANY");
$logger->info('dropped table', [$dropped]);

unlink($db_file);