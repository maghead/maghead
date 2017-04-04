<?php
use Maghead\ConfigLoader;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{

    public function testDsnStringArePreCompiled()
    {
        $config = ConfigLoader::loadFromFile('tests/apps/StoreApp/config_mysql.yml');
        $this->assertNotNull($config);
        $nodes = $config['data_source']['nodes'];
        $this->assertNotEmpty($nodes);
        foreach ($nodes as $nodeConfig) {
            $this->assertNodeConfig($nodeConfig, 'node config');
        }
    }

    protected function assertNodeConfig(array $nodeConfig)
    {
        if (isset($nodeConfig['read'])) {
            foreach ($nodeConfig['read'] as $r) {
                $this->assertArrayHasKey('dsn', $r, 'should have read config dsn');
            }
        }
        if (isset($nodeConfig['write'])) {
            foreach ($nodeConfig['write'] as $w) {
                $this->assertArrayHasKey('dsn', $w, 'should have write config dsn');
            }
        }
        if (!isset($nodeConfig['read']) && !isset($nodeConfig['write'])) {
            $this->assertArrayHasKey('dsn', $nodeConfig, 'should have node level dsn');
        }
    }
}
