<?php
namespace LazyRecord\Command\DataSourceCommand;
use CLIFramework\Command;
use LazyRecord\Command\BaseCommand;
use LazyRecord\ConfigLoader;
use LazyRecord\DSN\DSNParser;
use SQLBuilder\Driver\PDODriverFactory;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Query\CreateDatabaseQuery;
use PDO;

class SetDefaultCommand extends BaseCommand
{
    public function brief() 
    {
        return 'set default data source for PDO connections.';
    }

    public function arguments($args)
    {
        $args->add('default-datasource');
    }

    public function execute($defaultDataSource)
    {
        // force loading data source
        $configLoader = $this->getConfigLoader(true);

        $idList = $configLoader->getDataSourceIdList();



        $dsId = $this->getCurrentDataSourceId();
        $ds = $configLoader->getDataSource($dsId);

        $dsnParser = new DSNParser;
        $dsn = $dsnParser->parse($ds['dsn']);

        $dbName = $dsn->getAttribute('dbname');

        $dsn->removeAttribute('dbname');

        $this->logger->debug("Connection DSN: " . $dsn);

        $pdo = new PDO($dsn, @$ds['user'], @$ds['pass'], @$ds['connection_options']);

        $q = new CreateDatabaseQuery($dbName);
        if (isset($ds['charset'])) {
            $q->characterSet($ds['charset']);
        } else {
            $q->characterSet('utf8');
        }

        $queryDriver = PDODriverFactory::create($pdo);
        $sql = $q->toSql($queryDriver, new ArgumentArray);
        $this->logger->info($sql);

        if ($pdo->query($sql) === false) {
            list($statusCode, $errorCode, $message) = $pdo->errorInfo();
            $this->logger->error("$statusCode:$errorCode $message");
            return false;
        }
        $this->logger->info('Database created successfully.');
    }

}



