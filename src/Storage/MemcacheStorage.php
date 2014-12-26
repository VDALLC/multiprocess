<?php
namespace Vda\Multiprocess\Storage;

use Memcache;

class MemcacheStorage implements IStorage
{
    protected $mc;

    protected $host, $port;

    public function __construct($host, $port)
    {
        $this->mc = new Memcache();
        $this->host = $host;
        $this->port = $port;
    }

    public function put($key, $value)
    {
        $this->mc->connect($this->host, $this->port);
        $res = $this->mc->set(self::class . $key, serialize($value));
        $this->mc->close();
        return $res;
    }

    public function get($key)
    {
        $this->mc->connect($this->host, $this->port);
        $res = $this->mc->get(self::class . $key);
        $this->mc->close();
        return unserialize($res);
    }

    public function remove($key)
    {
        $this->mc->connect($this->host, $this->port);
        $res = $this->mc->delete(self::class . $key);
        $this->mc->close();
        return $res;
    }
}
