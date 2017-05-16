<?php

namespace Maghead\Runtime\Query;

use SQLBuilder\Universal\Query\UpdateQuery as BaseQuery;
use SQLBuilder\ArgumentArray;

use Maghead\Runtime\Repo;

class UpdateQuery extends BaseQuery implements Executable
{
    protected $repo;

    /**
     * @param Repo $repo The repo object is used for executing the query.
     */
    public function __construct(Repo $repo)
    {
        $this->repo = $repo;
    }

    public function execute()
    {
        return $this->repo->execute($this);
    }
}
