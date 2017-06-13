<?php

namespace Maghead\TableBuilder;

use Maghead\Testing\ModelTestCase;

use AuthorBooks\Model\AuthorSchema;
use AuthorBooks\Model\BookSchema;

class MysqlBuilderTest extends ModelTestCase
{
    protected $onlyDriver = 'mysql';

    public function models()
    {
        return [new BookSchema];
    }

    public function test()
    {
        $schema = new BookSchema;
        $b = new MysqlBuilder($this->conn->getQueryDriver());
        $sql = $b->buildColumn($schema, $schema->getColumn('updated_at'));
        $this->assertContains('DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $sql);
    }
}
