<?php

class ErrorController extends ApplicationController
{
	public function errorAction($controller, $action, $params) {

    	echo "Error";
    	exit;
    }
}
?>
