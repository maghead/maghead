<?php

namespace Maghead\Runtime\Query;

use SQLBuilder\Universal\Query\DeleteQuery as BaseQuery;
use SQLBuilder\ArgumentArray;

use Maghead\Runtime\BaseRepo;

class DeleteQuery
    extends BaseQuery
    implements Executable
{
    protected $repo;

    /**
     * @param BaseRepo $repo The repo object is used for executing the query.
     */
    public function __construct(BaseRepo $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Executes the query on the repository
     *
     * @return bool returns from PDOStatement::execute
     */
    public function execute()
    {
        return $this->repo->execute($this);
    }
}
