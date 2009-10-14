<?php

require dirname(__FILE__)."/functions.php";
require dirname(__FILE__)."/applicationcontroller.php";
require dirname(__FILE__)."/database.php";
require dirname(__FILE__)."/l10n.php";
require dirname(__FILE__)."/html/object.php";
require dirname(__FILE__)."/html/template.php";
require dirname(__FILE__)."/model.php";
require dirname(__FILE__)."/library/log.php";

/**
*
* Base class of the framework
*/
class Web
{
    public $laguages = array("es");
    public $database;
    public $defaultHtmlEditor = "ckeditor";
    public $controller, $action, $params, $uri, $model;
    public $l10n;
    public $initialized = false;
    public $bench;
    public $auth;

    private $_imagesMaxSize = array(1024, 1024);
    private $_htmlTemplateDir = "/templates";
    private $_debug = false;
    private $_inProduction = true;
    private static $_defaultInstance;    // La primera clase que se crea
    private $_defaultController = "index";
    private $_applicationPath;


    public function __construct($database = null, $languages = null)
    {
        session_start();
        if (isset($_SESSION['initialized'])) $this->initialized = true;


        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
        //error_reporting(E_STRICT);
        if (!web::$_defaultInstance) {
            web::$_defaultInstance = $this;
            $this->l10n = new l10n();
            $this->bench = new bench();
            $this->auth = new auth();
        }
//        if ($languages) $this->setLanguages($languages);
        if ($database) $this->setDatabase($database);
        $this->_applicationPath =  $_SERVER["DOCUMENT_ROOT"]."/../application/";
        if (web::request("debug")) $_SESSION['debug'] = web::request("debug");
    }

    /**
     * Set the available languages in the website.
     *
     * Pone los idiomas disponibles en la web.     *
      * \param array $langs Array of languages.<br/>
      * example: $web->setLanguages(array('es', 'en', 'pt'));
     */

    public function setLanguages(array $langs)
    {
        $this->l10n->setLanguages($langs);
    }

    /**
     * Return the array with the languages of the web
     */
    public function getLanguages()
    {
        return $this->l10n->getLanguages();
    }

    /**
     * Set the maximum size for saving the image files.
     */
    public function setImagesMaxSize($height, $width)
    {
        $this->_imagesMaxSize = array($height, $width);
    }

    public function setDatabase($database)
    {
        if (!$this->database = new Database($database)) {
            web::error("Error conectando con la base de datos $database[0]");
            exit;
        }
        if (!$this->database->tableExists('images')) {
            create_images_and_files_tables($this->database);
        }
        $this->database->exec('SET character_set_results = utf8;');
        $this->database->exec('SET character_set_client = utf8;');

    }

    public function setHtmlTemplatesDir($templatesDir)
    {
        $this->_htmlTemplateDir = $templatesDir;
    }

    public static function instance()
    {
        return web::$_defaultInstance;
    }

    private function parseInfo($uri)
    {
        $url = parse_url($uri);
        $uri = explode("/", substr($url["path"], 1));
        if (in_array($uri[0], $this->l10n->getLanguages())) {
            $lang = array_shift($uri);
            $this->l10n->setLanguage($lang);
        }
        // Controlador por defecto indexController.
        $uri[0] = $uri[0] ? strtolower($uri[0]) : "index";
        // Método por defecto index.
        $uri[1] = isset($uri[1]) && $uri[1] != "" ?
                            strtolower($uri[1]) : "index";
        $this->controller = strtolower(array_shift($uri));
        $this->action = web::processAction(array_shift($uri));
        $this->params = $uri;
    }

    public static function uri($params, $allParams = true)
    {
        $uri = web::params($params, $allParams);
        return "/".web::instance()->controller."/".(
                    web::instance()->model ? web::instance()->model."/" : "").
                    web::instance()->action.$uri;
    }


    public static function params($params = null, $allParams = true)
    {
        $arr = array();
        $uri = '';
        $previousParams = web::instance()->params;
        $arr = web::processParams($previousParams, $arr, $uri);
        $arr = web::processParams($params, $arr, $uri);

        foreach ($arr as $item => $value) {
            if (is_numeric($item) && $allParams) {
                $uri .= "/$value";
            }
        }

        foreach ($arr as $item => $value) {
            if (!is_numeric($item)) {
                $uri .= "/$item=$value";
            }
        }

        if ($_SERVER["QUERY_STRING"]) $uri .= "?".$_SERVER["QUERY_STRING"];
        return $uri;
    }

    private static function processParams($params, $arr, &$uri)
    {

        if (!is_array($params)) {
            $params = explode("/", $params);
            array_shift($params);
        }
        $count = 0;
        foreach ($params as $p) {
            $a = explode('=', $p, 2);
            if (isset($a[1])) $arr[$a[0]] = $a[1];
            else $arr[$count++] = $p; // $uri .= "/".$p;
        }
        return $arr;
    }

    public function run($uri = null, $view = null, $render = false)
    {
        if ($this->_inProduction) make_link_resources();
        $this->initialized = true;
        $_SESSION['initialized'] = true;

        if (!$uri) {
            $this->render = $render = true;
            $uri = $_SERVER["REQUEST_URI"];
        }

        $this->uri = $uri;

        $this->DealSpecialCases();
        $this->parseInfo($uri);

        switch ($this->controller) {
            case 'admin':
                return $this->callAdminDispatcher($render);
                break;

            case 'ajax':
                $this->callAjaxDispatcher();
                break;

            case 'helpers':
                $this->callHelpersDispatcher();
                break;

            case 'resources':
                if (file_exists($_SERVER['DOCUMENT_ROOT']."/resources")) {
                    return $this->callDefaultDispatcher($render, $view);
                } else {
                    $controller = new resourcesController();
                    $controller->getAction($this->action, $this->params);
                }
                break;
        default:
                return $this->callDefaultDispatcher($render, $view);
        }

    }


    private function getController($view = null, $admin = false)
    {
        // Si estamos en administración.
        if ($this->controller == 'admin' && $this->action  == 'index') {
            $this->action = array_shift($this->params);
            $this->action = $this->action ? $this->action : 'index';
        }

        $controllerClass = ucfirst(
        str_replace('-', '_', web::canonize($this->controller))).
        "Controller";

        $action = $this->action;

        if (!$this->loadController($controllerClass)
            || (!method_exists($controllerClass, $this->action."Action") && !$admin)
        ) {
            $action = "error";
            $controllerClass = "ErrorController";
            array_unshift($this->params, $this->controller, $this->action);
            $this->loadController("ErrorController");
        }

        $controller = new $controllerClass($view);
        $controller->setApplicationPath($this->_applicationPath);
        $controller->view->controller = $this->controller;
        $controller->view->action = $this->action;

        if (method_exists($controller, "beforeFilter")) {
            call_user_method_array("beforeFilter", $controller, $this->params);
        }
        return array($controller, $action);
    }


    private function callDefaultDispatcher($render = true, $view = null)
    {
        list($controller, $action) = $this->getController($view);
        if (!$render) $controller->layout = '';
        call_user_method_array($action."Action", $controller, $this->params);

        if ($render) {
            $controller->render($this->action);
        } else {
            $value = $controller->renderHtml($this->action);
        }

        if (method_exists($controller, "afterFilter")) {
            call_user_method_array("afterFilter", $controller, $this->params);
        }
        return $value;
    }

    private    function callAdminDispatcher($render = true)
    {
        if ($this->action == "index") {
            list($controller, $action) = $this->getController($view, true);
            call_user_method_array(
                $action."Action",
                $controller,
                $this->params
            );
        } else {
            list($controller, $action) = $this->getController(null, true);
            $this->model = $model = $this->action;
            $this->action = $action = array_shift($this->params);
            // By default we call to list method of the model.
            if (!$action) {
                $this->redirect("/admin/$model/list"); exit;
            }
            call_user_method_array(
                $model.ucfirst($action),
                $controller,
                $this->params
            );
        }

        if ($render) {
            $controller->render($this->action);
        } else {
            return $controller->renderHtml($this->action);
        }
    }

    private function callAjaxDispatcher()
    {
        // Cuando el controlador es ajax llamamos a la funcion function
        // usando helpers_model_ajax del modelo /ajax/model/function
        $controllerName = $this->action;
        $action = array_shift($this->params);
        $model = new $controllerName();
        $ajax = new helpers_model_ajax($model);
        call_user_method_array($action, $ajax, $this->params);
    }

    private function callhelpersDispatcher()
    {
        // Cuando el controlador es ajax llamamos a la funcion function
        // usando helpers_model_ajax del modelo /helpers/class/action
        $controllerName = "helpers_$this->action";
        $action = array_shift($this->params);
        $model = new $controllerName();
        call_user_method_array($action, $model, $this->params);
    }


    public function getApplicationPath()
    {
        return $this->_applicationPath;
    }


    public function loadController($name)
    {
        if (file_exists($this->_applicationPath."controllers/$name.php")) {
            require_once($this->_applicationPath."controllers/$name.php");
            return class_exists($name, False);
        }
        return false;
    }

    public function loadModel($name)
    {
        if (file_exists($this->_applicationPath."models/$name.php")) {
            return class_exists($name, False);
        }
        return false;
    }


    public function setLanguage($lang)
    {
        $this->l10n->setLanguage($lang);
    }

    public function getLanguage()
    {
        return $this->l10n->getLanguage();
    }

    public function setDefaultLanguage($lang)
    {
        $this->l10n->setDefaultLanguage($lang);
    }

    public function setInProduction($p)
    {
        $this->_inProduction = $p;
    }

    public function isInProduction()
    {
        return $this->_inProduction;
    }

    public function redirect($uri)
    {
        $this->run($uri, null, True);
    }

    public function location($uri)
    {
        header("Location: $uri");
        exit;
    }

    public static function request($param)
    {
        $arr = array();
        $arr = web::processParams(web::instance()->params, $arr);
        $arr = array_merge($arr, $_REQUEST);
        return $arr[$param];

    }

    private function processAction($param)
    {
        return str_replace('-', '_', $param);
    }

    public function setDefaultHtmlEditor($editor = "fckeditor")
    {
        $this->defaultHtmlEditor = $editor;
    }
    public function initialized()
    {
        return $this->initialized;
    }

    public function autoSelectLanguage()
    {
        if (!$this->initialized)    $this->l10n->autoSelectLanguage();
    }

    private static function canonize($str)
    {
        $arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o',
                    'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i',
                    'Ó' => 'o', 'Ú' => 'u', '"' => '-', '.' => '_');
        return str_replace(' ', '-', strtr(strtolower($str), $arr));
    }

    public static function database()
    {
        return web::instance()->database;
    }

    private function DealSpecialCases()
    {
        switch ($this->uri) {
        case '/robots.txt':
            header("Content-type: text/plain");
            echo "User-Agent: *\nAllow: /\n";
            exit;
        }
    }

    public function error404()
    {
        header("HTTP/1.0 404 Not Found");
        header("Status: 404 Not Found");

        if (method_exists("ErrorController", "notfoundAction")) {
            $controller = new ErrorController();
            $controller->setApplicationPath($this->_applicationPath);
            $controller->view->controller = $this->controller;
            $controller->view->action = $this->action;
            call_user_method_array(
                "notfoundAction",
                $controller,
                $this->params
            );
            echo $controller->renderHtml("notfound");
        } else {
//            echo "<h1>Error 404</h1>";
            $this->redirect("/");
        }

        exit;
    }

    public static function debug($texto, $file, $linea)
    {
//        log::to_file("EXEC $texto<hr>");

        if (web::request("debug") == "true" || $_SESSION['debug'] == "true") {
            echo "<pre style='padding: 1em; border: 1px dashed #666;'>
                    <span style='font-size: 0.6em;'>$file ($linea)</span>:\n";
            var_dump($texto);
            echo "</pre>";
        }
    }

    public static function error($texto, $file, $linea)
    {
        echo "<pre style='padding: 1em; border: 1px dashed #666;'>
                    <h3 style='color: red;'>
                      <span style='font-size: 0.6em;'>$file ($linea)</span>:\n";
        var_dump($texto);
        echo "</h3></pre>";
    }

    public static function warning($texto, $file, $linea)
    {
        if (!web::instance()->isInProduction()) {
            echo "<pre style='padding: 1em; border: 1px dashed #orange;'>
                    <h3 style='color: orange;'>
                    <span style='font-size: 0.6em;'>$file ($linea)</span>:\n";
            var_dump($texto);
            echo "</h3></pre>";
        }
    }

    public static function bench()
    {
        if (!web::instance()->isInProduction())
            return web::instance()->bench->toHtml();
    }

    public static function &auth()
    {
        return web::instance()->auth;
    }

}
