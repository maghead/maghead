<?php
use SQLBuilder\Universal\Query\SelectQuery;
use Maghead\Testing\ModelTestCase;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryMapper;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryWorker;
use Maghead\ConfigLoader;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};

/**
 * @group sharding
 */
class ChunkManagerTest extends ModelTestCase
{
    protected $defaultDataSource = 'node1';

    protected $requiredDataSources = ['node1', 'node1_2', 'node2', 'node2_2'];

    public function getModels()
    {
        return [new \StoreApp\Model\StoreSchema];
    }

    public function testChunkInit()
    {
        $shardManager = new ShardManager($this->config, $this->connManager);

        $mapping = $shardManager->getShardMapping('M_store_id');
        $this->assertNotEmpty($mapping);

        $chunkManager = new ChunkManager($this->config, $this->connManager);
        $chunks = $chunkManager->initChunks($mapping, 32);

        foreach ($chunks as $chunkId => $chunk) {
            $this->assertStringMatchesFormat('chunk_%i', $chunkId);
            $this->assertNotNull($chunk['shard']);
            $this->assertStringMatchesFormat('sqlite:chunk_%i.sqlite',$chunk['dsn']);
        }

        $chunkManager->removeChunks($mapping);
    }




    public function testChunkExpand()
    {

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
                    'M_store_id' => \StoreApp\Model\StoreShardMapping::config(),
                ],
                // Shards pick servers from nodes config, HA groups
                'shards' => [
                    's1' => [
                        'write' => [
                          'node1_2' => ['weight' => 0.1],
                        ],
                        'read' => [
                          'node1'   =>  ['weight' => 0.1],
                          'node1_2' => ['weight' => 0.1],
                        ],
                    ],
                    's2' => [
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
}
