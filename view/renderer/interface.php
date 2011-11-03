<?php

interface view_renderer_interface
{
    public function loadTemplate($template);
    public function render(Array $data = null);
    public function setDirectory($directory, $cacheDirectory = null);

    public function __set($item, $value);
    public function __get($item);

}