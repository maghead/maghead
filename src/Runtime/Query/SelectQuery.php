<?php

namespace Maghead\Runtime\Query;

use Magsql\Universal\Query\SelectQuery as BaseQuery;
use Magsql\ArgumentArray;

use Maghead\Runtime\Repo;

class SelectQuery extends BaseQuery implements Fetchable
{
    protected $repo;

    /**
     * @param Repo $repo The repo object is used for executing the query.
     */
    public function __construct(Repo $repo)
    {
        $this->repo = $repo;
        parent::__construct();
    }

    public function fetch()
    {
        return $this->repo->fetchCollection($this);
    }

    public function fetchColumn($column = 0)
    {
        return $this->repo->fetchColumn($this, $column);
    }
}
