<?php

namespace Maghead\Sharding;

class ShardIDFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testGenerate()
    {
        $ids = ShardIDFactory::generate('s_', 10);
        $this->assertSame([
            0 => 's_0',
            1 => 's_1',
            2 => 's_2',
            3 => 's_3',
            4 => 's_4',
            5 => 's_5',
            6 => 's_6',
            7 => 's_7',
            8 => 's_8'
        ], $ids);
    }

    public function testGenerateByIndexes()
    {
        $ids = ShardIDFactory::generateByIndexes('s_', [
            1000,
            2000,
        ]);
        $this->assertSame([
            0 => 's_1000',
            1 => 's_2000',
        ], $ids);
    }
}
