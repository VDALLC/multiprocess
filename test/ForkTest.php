<?php
use Vda\Multiprocess\ParallelExecutor;

class ForkTestClass extends PHPUnit_Framework_TestCase
{
    public function testAutoloadAndDefaultConstructor()
    {
        $pc = new ParallelExecutor();
        $this->assertInstanceOf(ParallelExecutor::class, $pc);
    }

    /**
     * Test process 9 values it 3 threads
     */
    public function test9in3Process()
    {
        $pc = new ParallelExecutor();

        // warm up for more accurate time measurement
        $pc->map(function() { return null; }, range(1, 9), 3);

        $start = microtime(true);
        $res = $pc->map(
            function() {
                usleep(100e3);
            },
            range(1, 9),
            3
        );
        $this->assertEquals(3, round((microtime(true) - $start) * 10));
        $this->assertEquals(9, count($res));
    }

    /**
     * Test process 8 values it 6 threads
     */
    public function test8in6Process()
    {
        $pc = new ParallelExecutor();

        // warm up for more accurate time measurement
        $pc->map(function() { return null; }, range(1, 9), 3);

        $start = microtime(true);
        $res = $pc->map(
            function() {
                usleep(100e3);
            },
            range(1, 8),
            6
        );
        $this->assertEquals(2, round((microtime(true) - $start) * 10));
        $this->assertEquals(8, count($res));
    }

    public function testMapReturnsNullWithoutStorage()
    {
        $pc = new ParallelExecutor();
        $res = $pc->map(
            function($x) {
                return $x;
            },
            range(1, 20),
            5
        );
        $this->assertEquals(20, count($res));
        foreach ($res as $null) {
            $this->assertNull($null);
        }
    }
}
