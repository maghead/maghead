<?php

namespace Maghead\Runtime;

trait RepoFactoryTrait
{
    /**
     * masterRepo method creates the Repo instance class with the default data source IDs
     *
     * @return BaseRepo
     */
    static public function masterRepo()
    {
        $connManager = static::$connectionManager;
        $write = $connManager->getConnection(static::WRITE_SOURCE_ID);
        $read  = $connManager->getConnection(static::READ_SOURCE_ID);
        return static::createRepo($write, $read);
    }

    /**
     * Create a repo object with custom write/read connections.
     *
     * @param string|Connection $write
     * @param string|Connection $read
     * @return Maghead\Runtime\BaseRepo
     */
    static public function repo($write = null, $read = null)
    {
        $connManager = static::$connectionManager;
        if (!$read) {
            if (!$write) {
                return static::masterRepo();
            } else {
                $read = $write;
            }
        }
        $writeConn = is_string($write) ? $connManager->getConnection($write) : $write;
        $readConn = is_string($read) ? $connManager->getConnection($read) : $read;
        return static::createRepo($writeConn, $readConn);
    }

    /**
     * This will be overrided by child model class.
     *
     * @param Connection $write
     * @param Connection $read
     * @return BaseRepo
     */
    static public function createRepo($write, $read)
    {
        return new BaseRepo($write, $read);
    }
}
