<?php
namespace Maghead\Query;

use SQLBuilder\Universal\Query\SelectQuery as BaseQuery;
use SQLBuilder\ArgumentArray;

use Maghead\Runtime\BaseRepo;

class SelectQuery
    extends BaseQuery
    implements Fetchable
{
    protected $repo;

    /**
     * @param BaseRepo $repo The repo object is used for executing the query.
     */
    public function __construct(BaseRepo $repo = null)
    {
        $this->repo = $repo;
        parent::__construct();
    }

    public function fetch()
    {
        return $this->repo->fetch($this);
    }

    public function fetchColumn($column = 0)
    {
        return $this->repo->fetchColumn($this, $column);
    }
}
