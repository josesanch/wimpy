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
    protected $_viewRendererClass;
    protected $_templateFile = null;

	public function __construct(Zend_Controller_Request_Abstract $request = null, Zend_Controller_Response_Abstract $response = null, array $invokeArgs = array())
	{

        $this->setRequest($request)
            ->setResponse($response)
            ->_setInvokeArgs($invokeArgs)
            ->_setupViewRenderer();

		// Create the compoments
		foreach ($this->components as $component => $params) {
			if (is_numeric($component)) { $component = $params; $params = null; }
			$this->$component = new $component($params);
		}
	}

    private function _setupViewRenderer()
    {
        $viewRenderer = isset($this->_invokeArgs["viewRenderer"]) ? $this->_invokeArgs["viewRenderer"] : "view_renderer_template";

        switch ($viewRenderer) {
        case "view_renderer_twig":
            $this->view = new view_renderer_twig();
            break;

        case "view_renderer_template":
        default:
            $this->view = new view_renderer_template();

        }
    }

	public function setApplicationPath($path)
	{
		$this->_applicationPath = $path;
        return $this;
	}

    public function setRequest($request)
    {
        if (null !== $request) {
            $this->request = $request;
            $this->view->controller = $this->controller = $this->getControllerName();
            $this->view->action = $this->action = $this->getActionName();
        }
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
            $this->template.".".$this->_viewFileSuffix;
        }

        return $this->getControllerName()."/".$viewFile;
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
        if (null !== $this->_templateFile) {
            $viewFile = $this->_templateFile;
        }

        if ($this->layout && $this->view->hasLayouts()) {
            $this->view->setLayout("layouts/".$this->layout);
        }
        $this->view->loadTemplate($this->getViewFile($viewFile));
        return $this->view->render();
	}

	public function render($viewFile)
	{
	    if(web::instance()->enableTidy)
    		echo web::instance()->tidy($this->renderHtml($viewFile));
    	else
        	echo $this->renderHtml($viewFile);

	}

    public function setViewRenderer($view = null)
    {
        $this->view = $view;
    }



    public function _getParam($param)
    {
        return $this->getRequest()->getParam($param);
    }
}