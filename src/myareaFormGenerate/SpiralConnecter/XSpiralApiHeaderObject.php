<?php

namespace SiLibrary\SpiralConnecter;

class XSpiralApiHeaderObject
{
    private string $func = '';
    private string $method = '';
    private string $action = '';

    public function __construct($func, $method, $action = 'request')
    {
        $this->func = $func;
        $this->method = $method;
        $this->action = $action;
    }

    public function __toString()
    {
        return "{$this->func}/{$this->method}/{$this->action}";
    }

    public function func()
    {
        return $this->func;
    }

    public function method()
    {
        return $this->method;
    }

    public function action()
    {
        return $this->action;
    }
}
