<?php

use Vda\Multiprocess\ForkException;
use Vda\Multiprocess\Storage\ShmopStorage;

class ShmopStorageTestClass extends PHPUnit_Framework_TestCase
{
    public function testPutGetDelete()
    {
        $ss = new ShmopStorage();
        $this->assertTrue($ss->put(getmypid(), 'test'));
        $this->assertEquals('test', $ss->get(getmypid()));
        $this->assertTrue($ss->remove(getmypid()));
    }

    public function testCrossProcessShmopStorage()
    {
        $pe = new \Vda\Multiprocess\ParallelExecutor(null, null, new ShmopStorage());

        $res = $pe->map(
            function ($value) {
                return $value * 2;
            },
            range(1, 100),
            3
        );

        $this->assertEquals(100, count($res));
        $prev = 0;
        foreach ($res as $value) {
            $this->assertEquals($prev + 2, $value);
            $prev = $value;
        }
    }

    public function testExceptionInCallbackAndKeySafety()
    {
        $pe = new \Vda\Multiprocess\ParallelExecutor(null, null, new ShmopStorage());
        $res = $pe->map(
            function($i) {
                if ($i == 5) {
                    throw new Exception('Test');
                }
                return true;
            },
            range(1, 10),
            4
        );
        $this->assertEquals(10, count($res));
        foreach ($res as $k => $v) {
            if ($k == 5 - 1) {
                $this->assertInstanceOf(ForkException::class, $v);
            } else {
                $this->assertTrue($v);
            }
        }
    }
}
