<?php
use Maghead\ConfigLoader;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{

    public function testNodeConfigNormalization()
    {
        $c = ConfigLoader::normalizeNodeConfig([
            'dsn' => 'mysql:host=localhost;dbname=testing',
            'user' => 'root',
        ]);
        $this->assertArrayHasKey('host', $c);
        $this->assertArrayHasKey('user', $c);
        $this->assertArrayHasKey('database', $c);
    }


    public function testDsnStringArePreCompiled()
    {
        $config = ConfigLoader::loadFromFile('tests/apps/StoreApp/config_mysql.yml', true);
        $this->assertNotNull($config);
        $nodes = $config['data_source']['nodes'];
        $this->assertNotEmpty($nodes);
        foreach ($nodes as $nodeConfig) {
            $this->assertNodeConfig($nodeConfig, 'node config');
        }
    }

    protected function assertNodeConfig(array $nodeConfig)
    {
        $this->assertNotNull($nodeConfig['driver']);
        if (isset($nodeConfig['read'])) {
            foreach ($nodeConfig['read'] as $r) {
                $this->assertArrayHasKey('dsn', $r, 'should have read config dsn');
                $this->assertNodeConfig($r);
            }
        }
        if (isset($nodeConfig['write'])) {
            foreach ($nodeConfig['write'] as $w) {
                $this->assertArrayHasKey('dsn', $w, 'should have write config dsn');
                $this->assertNodeConfig($w);
            }
        }
        if (!isset($nodeConfig['read']) && !isset($nodeConfig['write'])) {
            $this->assertArrayHasKey('dsn', $nodeConfig, 'should have node level dsn');
        }
    }
}
