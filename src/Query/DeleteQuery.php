<?php
namespace Maghead\Query;

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
    public function __construct(BaseRepo $repo = null)
    {
        $this->repo = $repo;
        parent::__construct();
    }

    public function execute()
    {
        return $this->repo->execute($this);
    }
}
