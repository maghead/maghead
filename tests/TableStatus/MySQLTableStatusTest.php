<?php
use Maghead\Testing\ModelTestCase;
use Maghead\TableStatus\MySQLTableStatus;
use AuthorBooks\Model\AuthorSchema;

class MySQLTableStatusTest extends ModelTestCase
{
    public $onlyDriver = 'mysql';

    public function getModels()
    {
        return [new AuthorSchema];
    }

    public function testQuerySummary()
    {
        $status = new MySQLTableStatus($this->conn, $this->queryDriver);
        $summary = $status->querySummary(['authors']);
        $this->assertNotEmpty($summary);
    }

    public function testQueryDetails()
    {
        $status = new MySQLTableStatus($this->conn, $this->queryDriver);
        $summary = $status->queryDetails(['authors']);
        $this->assertNotEmpty($summary);
    }
}
