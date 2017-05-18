<?php
use Maghead\Sharding\QueryMapper\Gearman\GearmanQueryJob;
use Magsql\Universal\Query\SelectQuery;


class GearmanQueryJobTest extends PHPUnit\Framework\TestCase
{
    public function testJobSerialization()
    {
        $query = new SelectQuery;
        $query->select(['SUM(amount)' => 'amount']);
        $query->from('orders');
        $query->where()
            ->equal('org_id', 20)
            ->in('store_id', [1,2,3])
            ;
        $job = new GearmanQueryJob('group1', $query);
        $str = serialize($job);
        $job2 = unserialize($str);
        $this->assertEquals($job->shardId, $job2->shardId);
        $this->assertEquals($job->query, $job2->query);
    }
}
