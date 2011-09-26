<?php

class view_renderer_twig extends view_renderer_abstract implements view_renderer_interface
{
    protected $_suffixFile = ".twig";

    public function render(Array $data = null)
    {
        if (null != $data) $this->_data = $data;

        $loader = new Twig_Loader_Filesystem($this->_templateDirectory);
        $twig = new Twig_Environment($loader, array(
            'cache' => $this->_getCacheDirectory(),
            'auto_reload' => true
        ));
        $twig->addExtension(new view_renderer_twig_extension_render());
        $twig->addExtension(new view_renderer_twig_extension_text());
        $twig->addExtension(new view_renderer_twig_extension_wimpy());

        $template = $twig->loadTemplate($this->_templateFile);
        return $template->render($this->_data);
    }

    private function _getCacheDirectory()
    {
        if ($this->_cacheDirectory)
            return $this->_cacheDirectory;

        return $this->_templateDirectory."/cache/twig";
    }
}