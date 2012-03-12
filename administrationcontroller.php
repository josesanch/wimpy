<?php

class AdministrationController extends ApplicationController
{
    public $layout            = "admin";
    public $name, $menu;
    public $color             = "#4275bb";
    public $background_color  = "#FFFFFF";
    public $roles;
    public $url;
    public $subtitle          = "Administración Web";
    public $logo_valign       = "bottom";
    public $name_color        = "black";
    public $show_webmail      = true;
    public $show_stats        = true;
    public $show_menu         = true;
    public $show_back_to_web  = true;
    public $show_languages    = false;
    public $show_header       = true;
    public $url_webmail;
    public $url_stats;
    public $bug_report;
    public $section;
    public $menu_width        = 190;
    public $logo              = "/images/logo.gif";
    public $css               = array();
    private $_selectedMenu    =  null;
    private $_selectedSubmenu = null;
    protected $auth;
//    protected $components = array("auth");

    public function __construct(Zend_Controller_Request_Abstract $request = null, Zend_Controller_Response_Abstract $response = null, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
        $this->view->data = $this;
        web::instance()->adminController = $this;
    }

    public function beforeFilter()
    {
        $this->auth = &web::instance()->auth;
        if (web::instance()->isInProduction() && !$this->auth->isLogged()) {
            $this->auth->requestAuth();
            if(!$this->auth->isLogged()) exit;
        }
    }


    public function getMenu()
    {
        $str = "";
        if (!$this->_selectedMenu && !$this->_selectedSubmenu)
            list($this->_selectedMenu, $this->_selectedSubmenu) = $this->_getItem();

        foreach ($this->menu as $menu => $submenu) {
            if (!$this->_selectedMenu) $this->_selectedMenu = $menu;

            $arrayValues = array_values($submenu['items']);
            $item = array_shift($arrayValues);
            $href = "href='/admin/".$item['link']."'";
            $active = $this->_selectedMenu == $submenu ? "class='active'" : "";
            $str .= "<li $active>
                        <a $href>$menu</a>
                    </li>\n";
        }
        return $str;
    }

    public function getSubMenu()
    {
        $subitems = array();
        foreach ($this->_selectedMenu["items"] as $name => $submenu) {
            $active = $this->_selectedSubmenu["link"] == $submenu["link"] ? "class='active'" : "";
            $href = "href='/admin/".$submenu['link']."'";
            $subitems[]= "
                        <li $active>
                                <a $href>".ucfirst($name)."</a>
                        </li>";
        }
        return implode("<span class='separador_submenu'> | </span>", $subitems);
    }

    private function _getItem($force = false)
    {
        $modelName = web::instance()->model;
        if(!$modelName) $selectFirst = true;

        $params = web::params(null, null, false, array("page-$modelName","order-$modelName","desc-$modelName"));
        $action = web::instance()->action;
        $regExpActions = "edit";

        if ($action == "edit") {
            $regExpActions = "list|edit";
            // Eliminamos el elemento que estamos editando.
            $params = "/".implode("/", array_slice(explode("/", $params), 1));
        }

        $regExp = "#$modelName/($action|$regExpActions)$params.*?#";
        $menu = $this->menu;
        foreach ($menu as $tab) {
            foreach ($tab["items"] as $submenu) {
                if ($selectFirst) {
                    return array($tab, $submenu);
                }

                $link = $submenu["link"];
                if (!$force) {
                    if (preg_match($regExp, $link)) {
                        return array($tab, $submenu);
                    }
                } else {
                    if ($link == $modelName) {
                        return array($tab, $submenu);
                    }
                }
            }
        }
        if (!$force)
            return $this->_getItem(true);
    }

    // Preprocesado antes de hacer nada para organizar los datos.
    private function preprocessMenu($menu_noprocess, $root = true)
    {
        $menu = array();
        foreach ($menu_noprocess as $name => $data) {

                if (is_numeric($name)) $name = $data;

                if (is_array($data) && in_array("items", array_keys($data), true))  { // Si está bien definido

                    $menu[$name]["link"] = $data["link"];
                    $menu[$name]["target"] = $data["target"];
                    $menu[$name]["params"] = $data["params"];
                    $menu[$name]["items"] = $this->preprocessMenu($data["items"], false);

                } elseif (is_array($data)) {// Si no está bien definido

                    $menu[$name]["link"] = "";
                    $menu[$name]["items"] = $this->preprocessMenu($data, false);
                } else {    // Si es el final
                    if (class_exists($name)) {
                        $model = new $name();
                        $data = __($model->getTitle());
                    }
                    $menu[$data]= array("link" => $name);
                }
            }
        return $menu;
    }

    public function __call($method, $params)
    {
        $modelName = array_shift($params);
        $action = substr($method, 0, -6);
        $adminAction = "admin".ucfirst(strtolower($action));

        $this->menu = $this->preprocessMenu($this->menu);
        $this->view->menu = $this->menu;


        if (web::request("no_layout"))
            $this->layout = "no_layout";

        web::instance()->loadModel($modelName);
        $model = new $modelName();
        $this->view->titulo = $model->getTitle();

        // If the model has "AdminList" method we call the method of the model.
        if (method_exists($model, $adminAction)) {
            $model = new $modelName();
            $model->layout = $this->layout;

            $this->view->content = call_user_func_array(
                array($model, $adminAction),
                $params
            );
            $this->layout = $model->layout;
            return;
        }

        $controllerName = ucfirst($modelName."Controller");
        if (web::instance()->loadController($controllerName)) {
            $controller = new $controllerName();
        }
        // If exists a controler named like the model and it has a method
        // named "AdminList", we call that method.
        if ($controller && method_exists($controller, $adminAction)) {
            $this->view->content = call_user_func_array(
                array($controller, $adminAction),
                $params
            );
            return;
        }
        // Default actions.
        switch (strtolower($action)) {
        case "list":

            $instance = new $modelName();

            $this->view->content = "<br/>".html_base_grid::toHtml(
                $instance, null, $instance->grid_columns
            );
            break;

        case "edit":
            $model = new $modelName();
            if(isset($params[0])) $model->select($params[0]);
            $edit = new html_autoform($model, $this->css);
            $this->view->content = "<br>".$edit->toHtml();
            break;

        case "delete":
            $model = new $modelName();
            if(isset($params[0])) {
                $model->select($params[0]);
                $model->delete();
            }

            $model->adminRedir();

        case "save":
            $model = new $modelName();
            $model->saveFromRequest();

            if (!web::request("dialog")) {
                $model->adminRedir();
            } else {    // Update de parent form from the dialog.
                $this->view->content = "
                        <script>
                            parent.Dialog.click('$modelName','".
                    web::request("field")."','".
                    web::request("parent")."','$model->id');
                        </script>
                        ";
                break;
            }

        default:
            header("HTTP/1.0 404 Not Found");
            header("Status: 404 Not Found");
            echo "<h1>Error 404</h1>";
            exit;
        }
    }

    protected function getLayoutFile() {
        if (file_exists(web::instance()->getApplicationPath()."/views/layouts/".$this->layout.".html")) {
            return web::instance()->getApplicationPath()."/views/layouts/".$this->layout.".html";

        }

        if (file_exists(dirname(__FILE__)."/views/layouts/".$this->layout.".html"))
            return dirname(__FILE__)."/views/layouts/".$this->layout.".html";
    }

    public function logoutAction()
    {
        web::instance()->auth->logout();
        web::instance()->location("/");
        exit;

    }

    public function indexAction()
    {
        $this->menu = $this->preprocessMenu($this->menu);
        list($this->_selectedMenu, $this->_selectedSubmenu) = $this->_getItem();
        web::instance()->location("/admin/".$this->_selectedSubmenu["link"]);
        exit;
    }

    public function setSelected($menu, $submenu)
    {
        $this->_selectedMenu = $menu;
        $this->_selectedSubmenu = $submenu;
    }

}
