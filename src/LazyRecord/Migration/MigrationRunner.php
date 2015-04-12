<?php
namespace LazyRecord\Migration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use LazyRecord\Console;
use LazyRecord\Metadata;
use LazyRecord\Schema\Comparator;
use LazyRecord\TableParser\TableParser;
use LazyRecord\ConnectionManager;

class MigrationRunner
{
    public $logger;

    public $dataSourceIds = array();

    public function __construct($dsIds)
    {
        $this->logger = Console::getInstance()->getLogger();

        // XXX: get data source id list from config loader
        $this->dataSourceIds = (array) $dsIds;
    }

    public function addDataSource( $dsId ) 
    {
        $this->dataSourceIds[] = $dsId;
    }

    public function load($directory) 
    {
        if( ! file_exists($directory) )
            return array();
        $loaded = array();
        $iterator = new RecursiveIteratorIterator( 
            new RecursiveDirectoryIterator($directory) , RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach( $iterator as $path ) {
            if($path->isFile() && $path->getExtension() === 'php' ) {
                $code = file_get_contents($path);
                if( preg_match('#Migration#',$code) ) {
                    require_once($path);
                    $loaded[] = $path;
                }
            }
        }
        return $loaded;
    }

    public function getLastMigrationId($dsId)
    {
        $meta = new Metadata($dsId);
        return $meta['migration'] ?: 0;
    }

    public function resetMigrationId($dsId) 
    {
        $metadata = new Metadata($dsId);
        $metadata['migration'] = 0;
    }

    public function updateLastMigrationId($dsId, $id) 
    {
        $metadata = new Metadata($dsId);
        $lastId = $metadata['migration'];
        $metadata['migration'] = $id;
        $this->logger->info("Updating migration version to $id.");
    }

    public function getMigrationScripts() 
    {
        $classes = get_declared_classes();
        $classes = array_filter($classes, function($class) { 
            return is_a($class,'LazyRecord\\Migration\\Migration',true) 
                && $class != 'LazyRecord\\Migration\\Migration';
        });


        // sort class with timestamp suffix
        usort($classes,function($a,$b) { 
            if( preg_match('#_(\d+)$#',$a,$regsA) && preg_match('#_(\d+)$#',$b,$regsB) ) {
                list($aId,$bId) = array($regsA[1],$regsB[1]);
                if( $aId == $bId )
                    return 0;
                return $aId < $bId ? -1 : 1;
            }
            return 0;
        });
        return $classes;
    }

    public function getUpgradeScripts($dsId)
    {
        $lastMigrationId = $this->getLastMigrationId($dsId);
        $scripts = $this->getMigrationScripts();
        return array_filter($scripts,function($class) use ($lastMigrationId) {
            $id = $class::getId();
            return $id > $lastMigrationId;
        });
    }

    public function getDowngradeScripts($dsId)
    {
        $scripts = $this->getMigrationScripts();
        $lastMigrationId = $this->getLastMigrationId($dsId);
        return array_filter($scripts,function($class) use ($lastMigrationId) {
            $id = $class::getId();
            return $id <= $lastMigrationId;
        });
    }


    /**
     * Run downgrade scripts
     *
     */
    public function runDowngrade(array $scripts = NULL, $steps = 1)
    {
        foreach( $this->dataSourceIds as $dsId ) {
            if (!$scripts) {
                $scripts = $this->getDowngradeScripts($dsId);
            }
            $this->logger->info("I just found " . count($scripts) . ' migration scripts to run downgrade!');
            while($steps--) {
                // downgrade a migration one at one time.
                if ($script = array_pop($scripts) ) {
                    $this->logger->info("Running downgrade migration script $script on data source $dsId");
                    $migration = new $script( $dsId );
                    $migration->downgrade();

                    if ($nextScript = end($scripts)) {
                        $this->updateLastMigrationId($dsId, $nextScript::getId());
                    }
                }
            }
        }
    }

    /**
     * Run upgrade scripts
     */
    public function runUpgrade(array $scripts = NULL)
    {
        foreach ($this->dataSourceIds as $dsId) {
            $connectionManager = ConnectionManager::getInstance();
            $driver = $connectionManager->getQueryDriver($dsId);
            $connection = $connectionManager->getConnection($dsId);

            if (!$scripts) {
                $scripts = $this->getUpgradeScripts($dsId);
                if (count($scripts) == 0) {
                    $this->logger->info("No migration script found.");
                    return;
                }
            }

            $this->logger->info("I just found " . count($scripts) . ' migration scripts to run upgrade!');

            try {
                $connection->beginTransaction();
                foreach ($scripts as $script) {
                    $this->logger->info("Running upgrade migration script $script on data source $dsId");
                    $migration = new $script($dsId);
                    $migration->upgrade();
                    $this->updateLastMigrationId($dsId,$script::getId());
                }
                $connection->commit();
            } catch (Exception $e) {
                $this->logger->error('Exception was thrown: ' . $e->getMessage());
                $this->logger->warn('Rolling back ...');
                $connection->rollback();
                $this->logger->warn('Recovered, escaping...');
                break;
            }
        }
    }

    public function runUpgradeFromSchemaDiff($schemas)
    {
        foreach( $this->dataSourceIds as $dsId ) {
            $script       = new Migration($dsId);
            $parser       = TableParser::create($script->driver, $script->connection);
            $tableSchemas = $parser->getTableSchemas();
            $comparator = new Comparator;
            // schema from runtime
            foreach ($schemas as $b) {
                $t = $b->getTable();
                $foundTable = isset($tableSchemas[$t]);
                if ($foundTable) {
                    $a = $tableSchemas[ $t ]; // schema object, extracted from database.
                    $diffs = $comparator->compare( $a , $b );

                    // generate alter table statement.
                    foreach ($diffs as $diff) {
                        if ($diff->flag == '+') 
                        {
                            // filter out useless columns
                            $columnArgs = array();
                            foreach( $diff->column->toArray() as $key => $value ) 
                            {
                                if (is_object($value) ||  is_array($value) ) {
                                    continue;
                                }

                                // Supported attribute
                                if (in_array($key, ['type','primary','name','unique','default','notNull','null','autoIncrement'])) 
                                {
                                    $columnArgs[ $key ] = $value;
                                }
                            }
                            $script->addColumn( $t , $columnArgs );
                        }
                        else if ($diff->flag == '-') 
                        {
                            $script->dropColumn($t, $diff->name);
                        }
                        else if ($diff->flag == '=~')
                        {
                            throw new LogicException('Unimplemented');
                        }
                        else if ($diff->flag == '=') 
                        {
                            $this->logger->warn("** column flag = is not supported yet.");
                        }
                        else 
                        {
                            $this->logger->warn("** unsupported flag.");
                        }
                    }
                } 
                else {
                    // generate create table statement.
                    // use sqlbuilder to build schema sql
                    $script->importSchema( $b );
                }
            }
        }
    }

}

