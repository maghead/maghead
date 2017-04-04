<?php

namespace Maghead\Sharding\QueryMapper\Pthread;

use Worker;
use PDO;

class PthreadQueryWorker extends Worker
{
    protected $dsn;

    protected $username;

    protected $password;

    protected $connectOptions;

    public function __construct(string $dsn, $username = null, $password = null, $connectOptions = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;

        // When in pthread environment, the Array comes from outside will be
        // Volitle object, we need to cast the object into array.
        $this->connectOptions = (array) $connectOptions;
    }

    public function connect()
    {
        if (count($this->connectOptions)) {
            return new PDO($this->dsn, $this->username, $this->password, $this->connectOptions);
        } else {
            return new PDO($this->dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
            ]);
        }
    }
}
