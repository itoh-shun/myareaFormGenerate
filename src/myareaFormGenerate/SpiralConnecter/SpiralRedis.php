<?php

namespace SiLibrary\SpiralConnecter;

class SpiralRedis
{
    private $cache;

    public function __construct()
    {
        if(spiral()){
            $this->cache = spiral()->getCache();
        }
    }

    public function get($key)
    {
        if($this->cache){ 
            return $this->cache->get($key);
        }
        return '';
    }

    public function set($key, $value): void
    {
        if($this->cache){ 
            $this->cache->set($key, $value);
        }
    }

    public function exists($key)
    {
        if($this->cache){ 
            return $this->cache->exists($key);
        }
        return '';
    }

    public function delete($key)
    {
        if($this->cache){ 
            return $this->cache->delete($key);
        }
        return '';
    }
    public function decr($key, $value = 1)
    {
        if($this->cache){ 
            return $this->cache->decr($key, $value);
        }
        return '';
    }
    public function incr($key, $value = 1)
    {
        if($this->cache){ 
            return $this->cache->incr($key, $value);
        }
        return '';
    }
    public function setTimeout($timeout = 900)
    {
        if($this->cache){ 
            return $this->cache->setTimeout($timeout);
        }
        return '';
    }
}
