<?php

abstract class view_renderer_abstract implements view_renderer_interface
{
    protected $_templateFile;
    protected $_templateDirectory;
    protected $_cacheDirectory;
    protected $_data;
    protected $_layoutFile;
    protected $_suffixFile = ".html";


    public function loadTemplate($template)
    {
        $this->_templateFile = $template.$this->_suffixFile;
        return $this;
    }

    public function setDirectory($directory, $cacheDirectory = null)
    {
        $this->_templateDirectory = $directory;
        if (null !== $cacheDirectory)
            $this->setCacheDirectory($cacheDirectory);

        return $this;
    }

    public function getDirectory()
    {
        return $this->_templateDirectory;
    }


    public function setCacheDirectory($directory)
    {
        $this->_cacheDirectory = $directory;
        return $this;
    }

	public function __set($item, $value)
    {
        $this->_data[$item] = $value;
        return $this;
	}

	public function __get($item)
    {
		if(isset($this->_data[$item]))
			return $this->_data[$item];
	}

    public function hasLayouts()
    {
        return false;
    }

    public function setLayout($layout)
    {
        $this->_layoutFile = $layout;
        return $this;
    }


    protected function _getPhisicalTemplateFile()
    {
        return $this->_templateDirectory.$this->_templateFile;
    }

    protected function _getPhisicalLayoutFile()
    {
        return $this->_layoutFile;
    }

    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }
}