<?php
namespace LazyRecord\Migration;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use LazyRecord\Console;
use LazyRecord\Metadata;

class MigrationRunner
{
    public $logger;

    public $dataSourceIds = array();

    public function __construct($dsIds)
    {
        $this->logger = Console::getInstance()->getLogger();
        $this->dataSourceIds = (array) $dsIds;
    }

    public function addDataSource( $dsId ) 
    {
        $this->dataSourceIds[] = $dsId;
    }

    public function load($directory) 
    {
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

    public function updateLastMigrationId($dsId,$id) 
    {
        $metadata = new Metadata($dsId);
        $lastId = $metadata['migration'];
        $metadata['migration'] = $id;
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
     */
    public function runDowngrade()
    {
        foreach( $this->dataSourceIds as $dsId ) {
            $scripts = $this->getDowngradeScripts($dsId);

            // downgrade a migration one at one time.
            if( $script = end($scripts) ) {
                $migration = new $script( $dsId );
                $migration->downgrade();
                $this->updateLastMigrationId($dsId,$script::getId());
            }
        }
    }

    /**
     * Run upgrade scripts
     */
    public function runUpgrade()
    {
        foreach( $this->dataSourceIds as $dsId ) {
            $scripts = $this->getUpgradeScripts($dsId);
            foreach( $scripts as $script ) {
                $this->logger->info("Running migration script $script on $dsId");
                $migration = new $script( $dsId );
                $migration->upgrade();
                $this->updateLastMigrationId($dsId,$script::getId());
            }
        }
    }
}

