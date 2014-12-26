<?php
namespace Vda\Multiprocess\Storage;

class ShmopStorage implements IStorage
{
    public function put($key, $value)
    {
        $value = serialize($value);
        $id = shmop_open($key, 'n', 0666, strlen($value));
        $res = shmop_write($id, $value, 0);
        shmop_close($id);
        return $res !== false;
    }

    public function get($key)
    {
        $id = shmop_open($key, 'a', 0, 0);
        $res = shmop_read($id, 0, shmop_size($id));
        shmop_close($id);
        return unserialize($res);
    }

    public function remove($key)
    {
        $id = shmop_open($key, 'w', 0, 0);
        return shmop_delete($id);
    }
}
