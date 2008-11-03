<?php

class resourcesController extends ApplicationController {


	public function getAction()	{
		$mimes = array('css' => 'text/css');
		$args = func_get_args();
		$url = dirname(__FILE__).'/resources/'.$args[0].'/'.implode("/", $args[1]);
		$path = pathinfo($url);
		if(in_array($path['extension'], array_keys($mimes))) {
		 	$mime_type = $mimes[$path['extension']];
		} else {
			$mime_type = mime_content_type($url);
		}
		ob_start("ob_gzhandler");

		header("Content-type: $mime_type");
		echo file_get_contents($url);
		exit;
	}

}

?>
