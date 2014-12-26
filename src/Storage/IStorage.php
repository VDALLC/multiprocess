<?php
namespace Vda\Multiprocess\Storage;

/**
 * Interface IStorage
 *
 * Implementers of this interface MUST survive after pcntk_fork. i.e. open and close resource on each put/get/remove.
 */
interface IStorage
{
    /**
     * @param int $key
     * @param mixed $value
     * @return bool
     */
    public function put($key, $value);

    /**
     * @param int $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param int $key
     * @return bool
     */
    public function remove($key);
}
