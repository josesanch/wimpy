<?php

/**
 * Objeto para hacer debug.
 *
 * @author    Jos� S�nchez Moreno
 * @copyright Oxigenow eSolutions
 * \ingroup utils
 * @version 1.0
 * Valid coding standards
 */
class file
{
    var $file;

    function file($url = null)
    {
        $this->file = $url;
    }

    function getExtension($file = null)
    {
        $file = isset($file) ? $file : $this->file;
        $data = pathinfo($file);
        return $data["extension"];
    }

    function exists()
    {
        return file_exists($this->file);
    }

}
