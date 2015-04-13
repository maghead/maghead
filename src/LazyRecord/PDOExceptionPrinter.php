<?php
namespace LazyRecord;
use PDOException;
use LazyRecord\ServiceContainer;
use CLIFramework\Logger;

class PDOExceptionPrinter
{
    static public function show(PDOException $e, $sqlQuery = NULL, array $arguments = NULL, Logger $logger = null) 
    {
        $c = ServiceContainer::getInstance();
        $logger = $logger ?: $c['logger'];

        $logger->error('Exception: ' . get_class($e));
        $logger->error("Error Message: " . $e->getMessage());

        if ($sqlQuery) {
            $logger->error("Query: " . $sqlQuery);
        } else {
            $logger->error("Query: Not Supplied.");
        }
        if ($arguments) {
            $logger->error("Arguments: " . var_export($arguments, true));
        }
        if ($e->errorInfo) {
            $logger->error("Error Info: " . var_export($e->errorInfo, true));
        }
        $logger->error("File: {$e->getFile()} @ {$e->getLine()}");
    }
}



