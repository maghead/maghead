<?php
use SQLBuilder\Universal\Query\SelectQuery;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryMapper;
use Maghead\Sharding\QueryMapper\Pthread\PthreadQueryWorker;
use Maghead\ConfigLoader;
use Maghead\Sharding\Manager\ShardManager;
use Maghead\Sharding\Manager\ChunkManager;
use StoreApp\Model\{Store, StoreSchema, StoreRepo};
use StoreApp\Model\{Order, OrderSchema, OrderRepo};
use StoreApp\StoreTestCase;

/**
 * @group sharding
 */
class ChunkManagerTest extends StoreTestCase
{
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
            'sharding' => \StoreApp\Config::sharding(),
            'data_source' => \StoreApp\Config::memory_data_source(),
        ]);
        return $config;
    }
}
