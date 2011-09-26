<?php

class view_renderer_template extends view_renderer_abstract implements view_renderer_interface
 {

     protected $_htmltemplate;

     public function __construct() {
         $this->_htmltemplate = new html_template();
     }

    public function render(Array $data = null)
    {
        if (null != $data) $this->_data = $data;
        $template = $this->_htmltemplate;

		if ($this->_layoutFile) {
			$layout = clone $template;
			if (file_exists($this->_getPhisicalTemplateFile())) {
				$layout->content = $template->toHtml($this->_getPhisicalTemplateFile());
			}
            return $layout->toHtml($this->_getPhisicalLayoutFile());
		}

		return $template->toHtml($this->_getPhisicalTemplateFile());
    }


    public function __set($item, $value)
    {
        $this->_data[$item] = $value;
        $this->_htmltemplate->assign($item, $value);
        return $this;
	}


    public function hasLayouts()
    {
        return true;
    }


}