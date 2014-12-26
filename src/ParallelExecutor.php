<?php
namespace Vda\Multiprocess;

use Exception;
use Psr\Log\LoggerInterface;
use Vda\Multiprocess\Storage\IStorage;

class ParallelExecutor
{
    protected $disconnector, $connector;

    protected $logger;

    protected $storage;

    public function __construct(
        callable $disconnector = null,
        callable $connector = null,
        IStorage $storage = null,
        LoggerInterface $logger = null
    ) {
        $this->disconnector = $disconnector;
        $this->connector = $connector;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * Parallel map.
     *
     * Apply $callback for each element of $data in child processes.
     *
     * @param callable $callback
     * @param $maxChildren
     * @param array $data
     * @return array you must specify $storage in constructor to get return values from $callback
     * @throws ForkException
     */
    public function map(callable $callback, array $data, $maxChildren)
    {
        $this->disconnectAll();
        $pids = [];
        $res = [];

        try {
            foreach ($data as $k => $args) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    throw new ForkException(pcntl_strerror(pcntl_get_last_error()));
                } elseif ($pid > 0) {
                    // if we are in parent collect pids of child processes
                    $pids[$pid] = $k;
                    // reserve place for result value in proper sequence
                    $res[$k] = null;
                    // wait some child to exit if max children reached
                    if (count($pids) >= $maxChildren) {
                        $pid = pcntl_wait($status);
                        $res[$pids[$pid]] = $this->getValue($pid, $status);
                        unset($pids[$pid]);
                    }
                } else {
                    // do the job in child
                    try {
                        try {
                            $this->connectAll();
                            $result = call_user_func_array($callback, (array)$args);
                            $this->putValue(getmypid(), $result);
                        } catch (Exception $ex) {
                            $msg = $this->logException($ex);
                            $this->putValue(getmypid(), $msg);
                            exit(1);
                        }
                    } catch (Exception $ex) {
                        exit(1);
                    } finally {
                        exit(0);
                    }
                }
            }
        } finally {
            // wait all remaining processes
            foreach ($pids as $pid => $one) {
                pcntl_waitpid($pid, $status);
                $res[$pids[$pid]] = $this->getValue($pid, $status);
            }
            $this->connectAll();
        }

        return $res;
    }

    protected function disconnectAll()
    {
        if ($this->disconnector) {
            call_user_func($this->disconnector);
        }
    }

    protected function connectAll()
    {
        if ($this->connector) {
            call_user_func($this->connector);
        }
    }

    protected function logException(Exception $ex)
    {
        $msg = $ex->getCode() . "\n" . $ex->getMessage() . "\n" . $ex->getTraceAsString();
        if ($this->logger) {
            $this->logger->error($msg);
        } else {
//            error_log($msg);
        }
        return $msg;
    }

    protected function getValue($key, $status)
    {
        if ($this->storage) {
            $res = $this->storage->get($key);
            $this->storage->remove($key);
        } else {
            $res = null;
        }
        return $status == 0 ? $res : new ForkException($res);
    }

    protected function putValue($key, $value)
    {
        if ($this->storage) {
            $this->storage->put($key, $value);
        }
    }
}
