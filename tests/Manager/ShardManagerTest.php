<?php
use Maghead\Testing\ModelTestCase;
use Maghead\Manager\ShardManager;
use Maghead\ConfigLoader;

class ShardManagerTest extends ModelTestCase
{
    protected $defaultDataSource = 'node1';

    protected $requiredDataSources = ['node1', 'node1_2', 'node2', 'node2_2'];

    public function getModels()
    {
        return [new \StoreApp\Model\StoreSchema];
    }

    protected function loadConfig()
    {
        $config = ConfigLoader::loadFromArray([
            'cli' => ['bootstrap' => 'vendor/autoload.php'],
            'schema' => [
                'auto_id' => true,
                'base_model' => '\\Maghead\\Runtime\\BaseModel',
                'base_collection' => '\\Maghead\\Runtime\\BaseCollection',
                'paths' => ['tests'],
            ],
            'sharding' => [
                'mappings' => [
                    // shard by hash
                    'M_store_id' => [
                        'tables' => ['orders'], // This is something that we will define in the schema.
                        'key' => 'store_id',
                        'hash' => [
                            'target1' => 'group1',
                            'target2' => 'group2',
                        ],
                    ],
                ],
                // Shards pick servers from nodes config, HA groups
                'groups' => [
                    'group1' => [
                        'write' => [
                          'node1_2' => ['weight' => 0.1],
                        ],
                        'read' => [
                          'node1'   =>  ['weight' => 0.1],
                          'node1_2' => ['weight' => 0.1],
                        ],
                    ],
                    'group2' => [
                        'write' => [
                          'node2_2' => ['weight' => 0.1],
                        ],
                        'read' => [
                          'node2'   =>  ['weight' => 0.1],
                          'node2_2' => ['weight' => 0.1],
                        ],
                    ],
                ],
            ],
            // data source is defined for different data source connection.
            'data_source' => [
                'master' => 'node1',
                'nodes' => [
                    'node1' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node1_2' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node2' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                    'node2_2' => [
                        'dsn' => 'sqlite::memory:',
                        'query_options' => ['quote_table' => true],
                        'driver' => 'sqlite',
                        'connection_options' => [],
                    ],
                ],
            ],
        ]);
        return $config;
    }

    public function testGetMappingById()
    {
        $shardManager = new ShardManager($this->config, $this->connManager);
        $mapping = $shardManager->getMapping('M_store_id');
        $this->assertNotEmpty($mapping);
    }


    public function testCreateShardDispatcher()
    {
        $shardManager = new ShardManager($this->config, $this->connManager);
        $dispatcher = $shardManager->createShardDispatcher('M_store_id', 'StoreApp\\Model\\StoreRepo');
        $this->assertNotNull($dispatcher);
        return $dispatcher;
    }

    /**
     * @depends testCreateShardDispatcher
     */
    public function testDispatchRead($dispatcher)
    {
        $repo = $dispatcher->dispatchRead('3d221024-eafd-11e6-a53b-3c15c2cb5a5a');
        $this->assertInstanceOf('Maghead\\Runtime\\BaseRepo', $repo);
    }

    /**
     * @depends testCreateShardDispatcher
     */
    public function testDispatchWrite($dispatcher)
    {
        $repo = $dispatcher->dispatchWrite('3d221024-eafd-11e6-a53b-3c15c2cb5a5a');
        $this->assertInstanceOf('Maghead\\Runtime\\BaseRepo', $repo);
        $this->assertInstanceOf('StoreApp\\Model\\StoreRepo', $repo);
        return $repo;
    }


    /**
     * @depends testDispatchWrite
     */
    public function testWriteRepo($repo)
    {
        $ret = $repo->create([ 'name' => 'My Store', 'code' => 'MS001' ]);
        $this->assertResultSuccess($ret);
    }



}
