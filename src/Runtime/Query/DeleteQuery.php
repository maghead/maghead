<?php

namespace Maghead\Runtime\Query;

use Magsql\Universal\Query\DeleteQuery as BaseQuery;
use Magsql\ArgumentArray;

use Maghead\Runtime\Repo;

class DeleteQuery extends BaseQuery implements Executable
{
    protected $repo;

    /**
     * @param Repo $repo The repo object is used for executing the query.
     */
    public function __construct(Repo $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Executes the query on the repository
     *
     * @return [bool,PDOStatement] returns from PDOStatement::execute
     */
    public function execute()
    {
        return $this->repo->execute($this);
    }
}
