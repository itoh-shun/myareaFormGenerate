<?php

namespace SiLibrary\SpiralConnecter;
class HttpRequestParameter extends \stdClass
{
    public function __construct(array $array = [])
    {
        foreach ($array as $key => $val) {
            $this->set($key, $val);
        }
    }

    public function delete($key){
        unset($this->{$key});
    }

    public function set($key, $value)
    {
        $this->{$key} = $value;
    }

    public function get($key)
    {
        if (!isset($this->{$key})) {
            return null;
        }
        return $this->{$key};
    }

    public function toJson()
    {
        $array = $this->toArray();
        array_walk_recursive($array, function(&$item) {
            if (is_array($item) || is_object($item)) {
                $item = $this->sortRecursively((array) $item);
            }
        });
        return json_encode($array, true);
    }
    
    private function sortRecursively($array)
    {
        foreach ($array as &$value) {
            if (is_array($value) || is_object($value)) {
                $value = $this->sortRecursively((array) $value);
            }
        }
        if (is_array($array)) {
            ksort($array);
        }
        return $array;
    }
    public function toArray()
    {
        return (array) $this;
    }

    public function remake()
    {
        return new self($this->toArray());
    }
}
