<?php

class config_ini implements config_interface
{
    private $_vars = array();
    public function __construct($file = null)
    {
        if ($file)
            $this->read($file);
    }
    public function get($var)
    {

    }

    public function set($var, $value)
    {

    }

    public function read($file)
    {
        if (isset($this->$_vars[$file])) {
            return $this->$_vars[$file];
        }

        $this->_vars[$file] = parse_ini_file(web::instance()->getApplicationPath() . "config/$file.ini", true);
        return $this->_vars[$file];
    }
}