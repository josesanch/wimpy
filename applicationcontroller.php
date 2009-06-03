<?php

class ApplicationController {
	public $view;
	public $layout;
	protected $application_path;
	public $action, $controller;


	public function __construct($view = null) {
		if($view)
			$this->view = $view;
		else
			$this->view = new html_template();

		// Create the compoments
		foreach($this->components as $component => $params) {
			if(is_numeric($component)) { $component = $params; $params = null; }
			$this->$component = new $component($params);
		}
	}



	public function setApplicationPath($path) {
		$this->application_path = $path;
	}

	public function setLayout($layout) {
		$this->layout = $layout;
	}

	public function renderHtml($view) {
		$view_file = $this->getViewFile($view);
		$controller_name = $this->getControllerName();

		if($this->layout)  {
			$layout = clone $this->view;
			if(file_exists($view_file)) {
				$layout->content = $this->view->toHtml($view_file);
			}
			return $layout->toHtml($this->getLayoutFile());
		} else {
			return $this->view->toHtml($view_file);
		}
	}

	public function render($view) {
		echo $this->renderHtml($view);

	}


	protected function getLayoutFile() 		{ return $this->application_path."views/layouts/".$this->layout.".html"; }
	protected function getViewFile($view) 	{ return $this->application_path."views/".$this->getControllerName()."/$view.html"; }
	public function getControllerName() 	{ return strtolower(substr(get_class($this), 0, -10)); }

	public function afterFilter($controller, $action) {
		if(get_class($this) == 'ErrorController') {
			web::instance()->error404();
		}

	}


}
?>
