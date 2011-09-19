<?php

class ApplicationController
{
	public $view;
	public $layout;
	public $action, $controller;
    public $template = null;
    public $components = array();
    public $request, $response;


    protected $_applicationPath;
    protected $_invokeArgs = array();
    protected $_viewFileSuffix = "html";

	public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
	{

        $this->setRequest($request)
            ->setResponse($response)
            ->setView()
            ->_setInvokeArgs($invokeArgs);


        $this->view->controller = $this->controller = $this->getControllerName();
        $this->view->action = $this->action = $this->getActionName();

		// Create the compoments
		foreach ($this->components as $component => $params) {
			if (is_numeric($component)) { $component = $params; $params = null; }
			$this->$component = new $component($params);
		}
	}


	public function setApplicationPath($path)
	{
		$this->_applicationPath = $path;
        return $this;
	}

    public function setRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    protected function _setInvokeArgs($invokeArgs)
    {
        $this->_invokeArgs = $invokeArgs;
        return $this;
    }

	public function setLayout($layout)
	{
		$this->layout = $layout;
        return $this;
	}

	protected function getLayoutFile()
    {
        return $this->_applicationPath."views/layouts/".$this->layout.".html";
    }

	public function getControllerName()
    {
        return $this->getRequest()->getControllerName();
    }
	public function getActionName()
    {
        return $this->getRequest()->getActionName();
    }

	protected function getViewFile($viewFile)
    {
        if ($this->template) {
            return $this->_applicationPath."views".$this->template.".".$this->_viewFileSuffix;
        }

        return $this->_applicationPath."views/".$this->getControllerName()."/".$viewFile.".".$this->_viewFileSuffix;
    }


	public function afterFilter($controller, $action)
	{
		if(get_class($this) == 'ErrorController') {
			web::instance()->error404();
		}

	}

    public function setTemplate($file)
    {
        $this->template = $file;
        return $this;
    }

	public function renderHtml($viewFile)
	{
		$viewFilePhisical = $this->getViewFile($viewFile);
		if ($this->layout) {
			//$this->view->setLayout($this->getLayoutFile());

			$layout = clone $this->view;
			if (file_exists($viewFilePhisical)) {
				$layout->content = $this->view->toHtml($viewFilePhisical);
			}

			return $layout->toHtml($this->getLayoutFile());

		}
		return $this->view->toHtml($viewFilePhisical);
	}

	public function render($viewFile)
	{
	    if(web::instance()->enableTidy)
    		echo web::instance()->tidy($this->renderHtml($viewFile));
    	else
        	echo $this->renderHtml($viewFile);

	}

    public function setView($view = null)
    {
        if (null !== $view)
			$this->view = $view;
		else
			$this->view = new html_template();

        $this->view->controller = $this->getControllerName();
        $this->view->action = $this->getActionName();

        return $this;
    }

}