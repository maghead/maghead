<?php

use PHPUnit\Framework\TestCase;
use Maghead\Runtime\Collection;
use Mockery as m;
use Universal\Event\EventDispatcher;

class CollectionDispatchTest extends TestCase
{
    protected function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function test_collection_fetch_all_will_receive_event()
    {
        $events = EventDispatcher::getInstance();
        $events->bind('maghead.query', function($sql, $arguments) {
            $this->assertSame($sql, 'SELECT m.* FROM `foo_table` AS m');
        });

        $collection = (new DispatchCollection(
            $repo = m::mock('Maghead\Runtime\Repo'),
            null
        ))->where('1 = 1');

        $repo->shouldReceive('getReadConnection')->twice()->andReturn(
            $connection = m::mock('Maghead\Runtime\Connection')
        );

        $connection->shouldReceive('getQueryDriver')->twice()->andReturn(
            $driver = m::mock('Magsql\Driver\BaseDriver')
        );

        $driver->shouldReceive('quoteTable')->once()->with('foo_table')->andReturn('`foo_table`');

        $connection->shouldReceive('prepare')->once()->with('SELECT m.* FROM `foo_table` AS m')->andReturn(
            $stmt = m::mock('PDOStatement')
        );

        $stmt->shouldReceive('setFetchMode')->once()->with(PDO::FETCH_CLASS, 'fooModel', [$repo]);
        $stmt->shouldReceive('execute');
        $stmt->shouldReceive('fetchAll')->once()->with(PDO::FETCH_CLASS, 'fooModel', [$repo])->andReturn(true);

        $collection->items();
    }
}

class DispatchCollection extends Collection
{
    const MODEL_CLASS = 'fooModel';

    const TABLE = 'foo_table';

    public static function createRepo($write, $read)
    {

    }

    public static function getSchema()
    {

    }
}
