<?php

namespace Maghead\Testing;

use Maghead\Result;
use XHProfRuns_Default;

class ModelProfileTestCase extends ModelTestCase
{
    protected $N = 5000;

    protected $startTime;

    protected $endTime;

    public function setUp()
    {
        if (!extension_loaded('xhprof')) {
            return $this->markTestSkipped('profiling requires xhprof extension.');
        }
        if (!isset($_ENV['XHPROF_ROOT'])) {
            return $this->markTestSkipped('XHPROF_ROOT environment variable must be set.');
        }

        if ($N = getenv('N')) {
            $this->N = $N;
        }

        /*
        if (extension_loaded('xhprof') ) {
            // ini_set('xhprof.output_dir','/tmp');
        }
        */
        parent::setUp();
        $this->startTime = microtime(true);
        xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY, [
            'ignored_functions' => [],
        ]);
    }

    public function tearDown()
    {
        if (!extension_loaded('xhprof')) {
            return parent::tearDown();
        }

        $xhprofData = xhprof_disable();
        $this->endTime = microtime(true);

        if (defined('ARRAY_FILTER_USE_KEY')) {
            // ignore all phpunit related keys
            // var_dump($xhprofData);
            $xhprofData = array_filter($xhprofData, function ($key) {
                return !preg_match('/PHPUnit/', $key);
            }, ARRAY_FILTER_USE_KEY);
        }

        include_once $_ENV['XHPROF_ROOT'].'/xhprof_lib/utils/xhprof_lib.php';
        include_once $_ENV['XHPROF_ROOT'].'/xhprof_lib/utils/xhprof_runs.php';

        $namespace = 'Maghead:'.$this->getName();
        $runs = new XHProfRuns_Default();
        $runId = $runs->save_run($xhprofData, $namespace);
        $host = 'localhost';
        if (isset($_ENV['XHPROF_HOST'])) {
            $host = $_ENV['XHPROF_HOST'];
        }

        echo "\n---------------------------------\n";
        printf("Profile test %d times spent %.2f seconds\n", $this->N, ($this->endTime - $this->startTime));
        printf("See profiling result at http://%s/index.php?run=%s&source=%s\n", $host, $runId, $namespace);
        parent::tearDown();
    }
}
