<?php

namespace Robin;

use \Exception;
use \Robin\Logger;
use \Robin\Interfaces\DataStorage;
use \Robin\FileHandler;

 /**
  * Data keeping class for saving and restoring data of Essences, Drives and Plays.
  * 
  * @package    Robin
  * @author     Yuriy Marin <yuriy.marin@gmail.com>
  */
class Keeper
{
    use Logger;
    
    private $handler;
    public $cache = [];
    
    public function __construct(DataStorage $handler)
    {
        $this->handler = $handler;
    }
    
    /**
     * Writes data for the object
     *
     * @param   string  $object_id  Identifier of the object
     *
     * @return  bool                TRUE on success, FALSE if data was not written
     */ 
    public function save(string $object_id, array $values): bool
    {
        $result = $this->handler->save($object_id, $values);
        
        if ($result) {
            $this->cache[$object_id] = $values;
        }
        
        return $result;
    }
    
    /**
     * Removes data of the object
     *
     * @param   string  $object_id  Identifier of the object, without file extension
     *
     * @return  bool                TRUE on success, FALSE if data was not found or error
     */ 
    public function remove(string $object_id, bool $remove_only_cache = false): bool
    {
        $result = false;
        if (array_key_exists($object_id, $this->cache)) {
            unset($this->cache[$object_id]);
            $result = true;
        }
        
        if (!$remove_only_cache) {
            $result = $this->handler->remove($object_id);
        }
        
        return $result;
    }
    
    /**
     * Reads data of the object
     *
     * @param   string  $object_id  Identifier of the object
     *
     * @return  array               Data in array format or FALSE if not found/read
     */
    public function read(string $object_id)
    {
        if (array_key_exists($object_id, $this->cache)) {
            return $this->cache[$object_id];
        }
        
        $data = $this->handler->read($object_id);
        if ($data) {
            $this->cache[$object_id] = $data;
        }
        
        return $data;
    }
}