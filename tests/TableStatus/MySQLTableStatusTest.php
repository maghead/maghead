<?php
use Maghead\Testing\ModelTestCase;
use Maghead\TableStatus\MySQLTableStatus;
use AuthorBooks\Model\AuthorSchema;

/**
 * @group table-status
 * @group mysql
 */
class MySQLTableStatusTest extends ModelTestCase
{
    protected $onlyDriver = 'mysql';

    public function getModels()
    {
        return [new AuthorSchema];
    }

    public function testQuerySummary()
    {
        $conn = $this->getDefaultConnection();
        $status = new MySQLTableStatus($conn, $conn->getQueryDriver());
        $summary = $status->querySummary(['authors']);
        $this->assertNotEmpty($summary);
    }

    public function testQueryDetails()
    {
        $conn = $this->getDefaultConnection();
        $status = new MySQLTableStatus($conn, $conn->getQueryDriver());
        $summary = $status->queryDetails(['authors']);
        $this->assertNotEmpty($summary);
    }
}
