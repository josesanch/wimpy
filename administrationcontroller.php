<?php

class AdministrationController extends ApplicationController
{
    public $layout = "admin";
    public $name, $menu;
    public $color = "#4275bb";
    public $background_color = "#FFFFFF";
    public $roles;
    public $url;
    public $subtitle = "Administración Web";
    public $logo_valign = "bottom";
    public $name_color = "black";
    public $show_webmail = true;
    public $show_stats = true;
    public $show_menu = true;
    public $show_back_to_web = true;
    public $show_languages = false;
    public $show_header = true;
    public $url_webmail;
    public $url_stats;
    public $bug_report;
    public $section;
    public $menu_width = 190;
    public $logo = "/images/logo.gif";
    public $css = array();
    private $selected_menu;

    protected $auth;
//    protected $components = array("auth");

    public function __construct()
    {
        parent::__construct();
        $this->view->data = $this;
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
        $this->selected_menu = $this->getSelectedMenu(web::instance()->model);
        foreach ($this->menu as $menu => $submenu) {
            if (!$this->selected_menu) $this->selected_menu = $menu;

            $item = array_shift(array_values($submenu['items']));
            $href = "href='/admin/".$item['link']."'";
            $active = $this->selected_menu == $menu ? "class='active'" : "";
            $str .= "<li $active>
                        <a $href>$menu</a>
                    </li>\n";
        }
        return $str;
    }

    public function getSubMenu()
    {
        $subitems = array();
        $action = web::instance()->model;
        foreach ($this->menu[$this->selected_menu]['items'] as $name => $submenu) {
            $active = $action == $submenu['link'] ? "class='active'" : "";
            $href = "href='/admin/".$submenu['link']."'";
            $subitems[]= "
                        <li $active>
                                <a $href>".ucfirst($name)."</a>
                        </li>";
        }
        return implode("<span class='separador_submenu'> | </span>", $subitems);

    }

    private function preprocessMenu($menu_noprocess, $root = true)
    {
        $menu = array();
        foreach ($menu_noprocess as $name => $data)
        {

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

    private function getSelectedMenu($action, $menu = null, $root = False)
    {
            if(!$menu) {
                $menu = $this->menu;
                $root = True;
            }

            foreach ($menu as $name => $data) {

                if ($data['link']
                    && ($data['link']  == $action
                        || "/admin/".$data['link'] == substr(
                                                        "/".web::instance()->controller."/".
                                                        (web::instance()->model ? web::instance()->model."/" : "").
                                                        "list".web::params(null, false), 0, strlen("/admin/".$data['link'])))) {
                    if($root) return $name;
                    return True;
                }
                if(array_key_exists('items', $data) && count($data['items']))
                    if(array_key_exists('items', $data)
                        && $this->getSelectedMenu($action, $data['items']))
                        return $name;
            }
            return False;
    }

    public function __call($method, $params)
    {
        $modelName = array_shift($params);
        $action = substr($method, 0, -6);
        $adminAction = "admin".ucfirst(strtolower($action));

        $this->menu = $this->preprocessMenu($this->menu);
        $this->view->menu = $this->getMenu();


        if (web::request("no_layout"))
            $this->layout = "no_layout";

        web::instance()->loadModel($modelName);
        $model = new $modelName();
        $this->view->titulo = $model->getTitle();

        // If the model has "AdminList" method we call the method of the model.
        if (method_exists($model, $adminAction)) {
            $model = new $modelName();
            $this->view->content = call_user_func_array(
                array($model, $adminAction),
                $params
            );
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
                web::instance()->location(
                    '/admin/'.get_class($model).
                    "/list".web::params(null, false)
                );
                exit;

            case "save":
                $model = new $modelName();
                $model->saveFromRequest();

                if (!web::request("dialog")) {
                    web::instance()->location(
                        '/admin/'.get_class($model).
                        "/list".web::params(null, false)
                    );
                    exit;
                } else {    // Update de parent form from the dialog.
                    $this->view->content = "
                        <script>
                            updateModelValueDialog('$modelName','".
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
        return dirname(__FILE__)."/views/layouts/".$this->layout.".html";
    }

    public function logoutAction() {
        $this->auth->logout();
        web::instance()->location("/");
        exit;

    }
}
