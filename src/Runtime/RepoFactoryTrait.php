<?php

namespace Maghead\Runtime;

trait RepoFactoryTrait
{
    /**
     * masterRepo method creates the Repo instance class with the default data source IDs
     *
     * @return \Maghead\Runtime\BaseRepo
     */
    public static function masterRepo()
    {
        $dataSourceManager = static::$dataSourceManager;
        $write = $dataSourceManager->getConnection(static::WRITE_SOURCE_ID);
        $read  = $dataSourceManager->getConnection(static::READ_SOURCE_ID);
        return static::createRepo($write, $read);
    }

    /**
     * Create a repo object with custom write/read connections.
     *
     * @param string|Connection $write
     * @param string|Connection $read
     * @return Maghead\Runtime\BaseRepo
     */
    public static function repo($write = null, $read = null)
    {
        $dataSourceManager = static::$dataSourceManager;
        if (!$read) {
            if ($write) {
                $read = $write;
            }
            return static::masterRepo();
        }
        $writeConn = is_string($write) ? $dataSourceManager->getConnection($write) : $write;
        $readConn = is_string($read) ? $dataSourceManager->getConnection($read) : $read;
        return static::createRepo($writeConn, $readConn);
    }

    /**
     * This will be overrided by child model class.
     *
     * @param \Maghead\Connection $write
     * @param \Maghead\Connection $read
     * @return \Maghead\Runtime\BaseRepo
     */
    public static function createRepo($write, $read)
    {
        return new BaseRepo($write, $read);
    }
}
