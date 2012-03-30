<?php

interface config_interface
{
    public function get($var);
    public function set($var, $value);
    public function read($file);
}