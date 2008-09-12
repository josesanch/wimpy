<?php

/**
 * Objeto para hacer debug.
 *
 * @author    José Sánchez Moreno
 * @copyright Oxigenow eSolutions
 * \ingroup utils
 * @version 1.0
 */
class file
{
	var $file;

	function file($url)
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
?>
