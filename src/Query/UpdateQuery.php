<?php

namespace Maghead\Query;

use SQLBuilder\Universal\Query\UpdateQuery as BaseQuery;
use SQLBuilder\ArgumentArray;

use Maghead\Runtime\BaseRepo;

class UpdateQuery
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

    public function execute()
    {
        return $this->repo->execute($this);
    }
}
