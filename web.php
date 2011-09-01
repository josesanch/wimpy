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
	const NOTIFY_BY_EMAIL = "desarrollo@o2w.es";

    public $laguages = array("es");
    public $database;
    public $defaultHtmlEditor = "ckeditor";
    public $controller, $action, $params, $uri, $model;
    public $l10n;
    public $initialized = false;
    public $bench;
    public $auth;
    public $enableTidy = false;
    //public $authMethod = Auth::FORM;
    public $authMethod = Auth::REALM;

    private $_imagesMaxSize = array(1024, 1024);
    private $_htmlTemplateDir = "/templates";
    private $_debug = false;
    private $_inProduction = true;
    private $_reportErrors = false;
    private static $_defaultInstance;    // La primera clase que se crea
    private $_defaultController = "index";
    private $_applicationPath;
    private $_l10nControllerMaps;
    public $showErrors = true;
    private $_metaTags;
    public $cssFiles = array("/css/main.css");
    public $jsFiles = array();
    public $pageTitle = "";
    public $adminController;


    public function __construct($database = null, $languages = null)
    {
        session_start();
        if (isset($_SESSION['initialized'])) $this->initialized = true;
        error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
        //error_reporting(E_ALL);
        //error_reporting(E_STRICT);

		if ($database) $this->setDatabase($database);

        if (!web::$_defaultInstance) {
            web::$_defaultInstance = $this;
            $this->l10n = new l10n();
            $this->bench = new bench();
            $this->auth = new auth();
            try {
                if ($this->database) $this->data = new webdata(1);
            } catch (Exception $e) {
            }
        }
//        if ($languages) $this->setLanguages($languages);

        $this->_applicationPath =  $_SERVER["DOCUMENT_ROOT"]."/../application/";
        if (web::request("debug")) $_SESSION['debug'] = web::request("debug");

        $this->_metaTags = array(
            "Content-Type" => array("http-equiv", "application/xhtml+xml; charset=UTF-8"),
            "Cache-Control" => array("http-equiv", "max-age=200"),
            "Content-Script-Type" => array("name", "text/javascript"),
            "Content-language" => array("name", web::instance()->getLanguage()),
            "robots" => array("name", "all"),
            "Author" => array("name", "O2W eSolutions, http://www.o2w.es"),
            "google-site-verification" => array("name", web::instance()->googleVerification)
        );
/*
        if (array_pop(explode(".", $_SERVER["SERVER_NAME"], 2)) == "o2w.es") {
            $this->inDevelopment = true;
        }
*/
        if(ini_get('display_errors')) $this->reportErrors(true);
    }


    /**
     * Set the available languages in the website.
     *
     * Pone los idiomas disponibles en la web
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
		$proto = array_shift(explode(":", $database[0]));
		switch ($proto) {
			case "mysql":
				$dbConector = "database_mysql";
				break;
			case "pgsql":
				$dbConector = "database_pgsql";
				break;
		}

        try {
			$this->database = new $dbConector($database);
		} catch (PDOException $e) {
            web::error("Error conectando con la base de datos");
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

    public static function uri($params, $allParams = true, $exclude = array())
    {
        $uri = web::params($params, $allParams, true, $exclude);
        return "/".web::instance()->controller."/".(
                    web::instance()->model ? web::instance()->model."/" : "").
                    web::instance()->action.$uri;
    }


    public static function params($params = null, $allParams = true, $queryString = true, $exclude = array())
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
            if (!is_numeric($item) && !in_array($item, $exclude)) {
                $uri .= "/$item=$value";
            }
        }

        if ($_SERVER["QUERY_STRING"] && $queryString) {
			$query = array();
			foreach ($_GET as $item => $value) {
				if (!array_key_exists($item, $arr) && !in_array($item, $exclude))
				if (is_array($value)) {
					foreach ($value as $v) $query[]= $item."[]=".urlencode($v);
				} else {
					$query[]= "$item=".urlencode($value);
				}
			}
			$uri .= "?".implode("&", $query);
		}
        return $uri;
    }

    private static function processParams($params = null, $arr = null, &$uri = null)
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
                return $this->_callAdminDispatcher($render);
                break;

            case 'ajax':
                $this->_callAjaxDispatcher();
                break;

            case 'helpers':
                $this->_callHelpersDispatcher();
                break;

            case 'resources':
                if (file_exists($_SERVER['DOCUMENT_ROOT']."/resources")) {
                    return $this->_callDefaultDispatcher($render, $view);
                } else {
                    $controller = new resourcesController();
                    $controller->getAction($this->action, $this->params);
                }
                break;

            case 'images':
                $file = new helpers_images();
                $file->select($this->action);
                $file->download();
            break;

            case 'files':
                $file = new helpers_files();
                $file->select($this->action);
                $file->download("inline");
            break;

        default:
                return $this->_callDefaultDispatcher($render, $view);        }

    }


    private function _getController($view = null, $admin = false)
    {
        // Si estamos en administración.
        if ($this->controller == 'admin' && $this->action  == 'index') {
            $this->action = array_shift($this->params);
            $this->action = $this->action ? $this->action : 'index';
        }

        $controllerName = $this->controller;

        if ($this->l10n->isNotDefault()) { // Buscamos en los maps
            $maps = $this->_l10nControllerMaps[$this->l10n->getLanguage()];
            if ($orgControllerName = array_search(web::canonize($controllerName), $maps)) {
                $controllerName = $orgControllerName;
            }
        }

        $controllerClass =  ucfirst(
            str_replace(
                '-',
                '_',
                web::canonize($controllerName)
            )
        )."Controller";

        $action = $this->action;

        if (!$this->loadController($controllerClass)
            || (!method_exists($controllerClass, $this->action."Action")
            && !$admin)
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
            call_user_func_array(
                array($controller, "beforeFilter"),
                $this->params
            );
        }
        return array($controller, $action);
    }


    private function _callDefaultDispatcher($render = true, $view = null)
    {
        list($controller, $action) = $this->_getController($view);
        if (!$render) $controller->layout = '';
        call_user_func_array(array($controller, $action."Action"), $this->params);

        if ($render) {
            $controller->render($this->action);
        } else {
            $value = $controller->renderHtml($this->action);
        }

        if (method_exists($controller, "afterFilter")) {
            call_user_func_array(array($controller, "afterFilter"), $this->params);
        }

        return $value;
    }

    private function _callAdminDispatcher($render = true)
    {
        if ($this->action == "index") {
            list($controller, $action) = $this->_getController($view, true);
            call_user_func_array(
                array($controller, $action."Action"),
                $this->params
            );
        } else {
            list($controller, $action) = $this->_getController(null, true);
            $this->model = $model = $this->action;
            $this->action = $action = array_shift($this->params);
            // By default we call to list method of the model.
            if (!$action) {
                $this->redirect("/admin/$model/list");
                exit;
            }
            $params = $this->params;
            array_unshift($params, $model);

            switch ($action) {
				case "list":
					if (!web::auth()->hasPermission($model, auth::VIEW)) web::forbidden();
					break;
				case "edit":
					if (!web::auth()->hasPermission($model, auth::MODIFY) && !web::auth()->hasPermission($model, auth::VIEW)) web::forbidden();
					break;
				case "save":
					if (!web::auth()->hasPermission($model, auth::MODIFY) && !web::auth()->hasPermission($model, auth::ADD)) web::forbidden();
					break;
				case "delete":
					if (!web::auth()->hasPermission($model, auth::DELETE)) web::forbidden();
					break;
			}

            call_user_func_array(
                array($controller, $action."Action"),
                $params
            );
        }


        if ($render) {
            $controller->render($this->action);
        } else {
            return $controller->renderHtml($this->action);
        }
    }

    private function _callAjaxDispatcher()
    {
        // Cuando el controlador es ajax llamamos a la funcion function
        // usando helpers_model_ajax del modelo /ajax/model/function
        $controllerName = $this->action;
        $action = array_shift($this->params);
        $model = new $controllerName();
        $ajax = new helpers_model_ajax($model);
        call_user_func_array(array($ajax, $action), $this->params);
    }

    private function _callhelpersDispatcher()
    {
        // Cuando el controlador es ajax llamamos a la funcion function
        // usando helpers_model_ajax del modelo /helpers/class/action
        $controllerName = "helpers_$this->action";
        $action = array_shift($this->params);
        $model = new $controllerName();
        //call_user_method_array($action, $model, $this->params);
        call_user_func_array(array($model, $action), $this->params);
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

    public function reportErrors($error = null)
    {
		if ($error) $this->_reportErrors = $error;
        return $this->_reportErrors;
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

    public static function request($param = null, $notInclude = null)
    {
        $arr = array();
        $arr = web::processParams(web::instance()->params, $arr);
        $arr = array_merge($arr, $_REQUEST);

        if (!isset($param)) {
            if (isset($notInclude)) {
                return array_diff_key($arr, array_combine($notInclude, $notInclude));
            }
            return $arr;
        }
        if (isset($arr[$param])) return $arr[$param];
    }

    private function processAction($param)
    {
        return str_replace("-", "_", convert_to_url($param));
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
            call_user_func_array(array($controller, "notfoundAction"), $this->params);
            echo $controller->renderHtml("notfound");
        } else {
//            echo "<h1>Error 404</h1>";
            web::instance()->redirect("/");
        }

        exit;
    }

    public function forbidden()
    {
		header('HTTP/1.1 403 Forbidden');
		echo "<h1>Acceso denegado</h1>";
		exit;
	}

    public static function debug($texto, $file = null, $linea = null)
    {
//        log::to_file("EXEC $texto<hr>");

        if (
            web::request("debug") == "true"
            || (array_key_exists("debug", $_SESSION) && $_SESSION['debug'] == "true")
        ) {
            echo "<pre style='padding: 1em; border: 1px dashed #666;'>
                    <span style='font-size: 0.6em;'>$file ($linea)</span>:\n";
            //var_dump($texto);
            echo $texto;
            echo "</pre>";
        }
    }

    public static function error($texto, $file = null, $linea = null, $notify = null)
    {
        $str = "<pre style='padding: 1em; border: 1px dashed #666;'>
                    <h3 style='color: red;'> * Se ha producido un error </h3>";

        if (!web::instance()->isInProduction()
            || web::instance()->reportErrors()
            || web::instance()->isDevelopmentServer())
			$str .= "<span style='font-size: 0.6em;'>$file ($linea)</span>:\n$texto";

        if ($notify == web::NOTIFY_BY_EMAIL
            && web::instance()->isInProduction()
            && !web::instance()->reportErrors()) {
			$mailMsg = "<pre style='padding: 1em; border: 1px dashed #666;'>
						<h3 style='color: red;'> * Se ha producido un error </h3>
						<span style='font-size: 0.6em;'>$file ($linea)</span>:\n
						$texto
                    </pre>";

			$notificando = "<hr/><h4>* Notificado por e-mail a ".web::NOTIFY_BY_EMAIL."</h4>";

			$mail = new net_mail();
			$mail->msg($mailMsg);
			$mail->subject("ERROR de SQL en: ".$_SERVER["SERVER_NAME"]." - ".web::uri());
			$mail->send(web::NOTIFY_BY_EMAIL, web::NOTIFY_BY_EMAIL);
		}

		if (web::instance()->showErrors)
            echo $str."$notificando</pre>";
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

    public static function bench($force = null)
    {
        if (!web::instance()->isInProduction() || $force)
            return web::instance()->bench->toHtml($force);
    }

    public static function &auth()
    {
        return web::instance()->auth;
    }

    public function tidy($value)
    {
        $config = array(
           'indent'         => true,
           'output-xhtml'   => true,
           'wrap'           => 800
        );

        $tidy = new tidy();
        $tidy->parseString($value, $config, 'utf8');
        $tidy->cleanRepair();
        return $tidy;
    }

	/**
	 * Devuelve true si el servidor es un servidor de desarrollo.
	 * Para que el servidor sea de desarrollo tiene que existir un fichero en .htdocs llamado .development
	 * @return boolean
	 */
	public function isDevelopmentServer()
	{
		return file_exists($_SERVER["DOCUMENT_ROOT"]."/.development");
	}

    public static function isMobile($type = null)
    {
        if (web::request("mobile") || $_SESSION["mobile"]) return true;
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
            if (isset($type)) {
                return preg_match("/$type/i",strtolower($_SERVER['HTTP_USER_AGENT']));
            }
            return true;
        }

    }

    public static function getView($view)
    {
        return new html_template($_SERVER["DOCUMENT_ROOT"]."/../application/views/{$view}.html");
    }

    public function setl10nControllerMaps($maps)
    {
        $this->_l10nControllerMaps = $maps;
    }


    public function setMetaTag($name, $value, $type = "name") {
        $this->_metaTags[$name] = array($type, $value);
        return web::instance();
    }

    public static function setPageTitle($title) {
        web::instance()->pageTitle = $title;
        return web::instance();
    }
    public static function setDescription($desc) {
        web::instance()->setMetaTag("description", $desc);
        return web::instance();
    }
    public static function setKeywords($key) {
        web::instance()->setMetaTag("keywords", $key);
        return web::instance();
    }
// TODO: Generar contenido de header
    public function header($tipo = "xhtml")
    {
        $language = $this->getLanguage();
        $pageTitle = $this->pageTitle;
        switch ($tipo) {

        case "html5":
            $doctype = "<!DOCTYPE html>
<html lang=\"$language-$language\">\n";
            $this->_metaTags["charset"] = array("name", "charset=UTF-8");
            $this->_metaTags["viewport"] = array("name", "width=device-width,initial-scale=1");
            unset($this->_metaTags["http-equiv"]);
            unset($this->_metaTags["Cache-Control"]);
            unset($this->_metaTags["Content-Script-Type"]);
            unset($this->_metaTags["Content-language"]);
            break;

        default:
            $doctype = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"$language\">";
        }


        foreach ($this->_metaTags as $meta => $arr) {
            if ($arr[1]) $metaTags .= "<meta $arr[0]=\"$meta\" content=\"$arr[1]\"/>\n";
        }

        foreach ($this->cssFiles as $file) {
            $cssTags .= "<link media=\"screen\" rel=\"stylesheet\" href=\"$file\" type=\"text/css\" />\n";
        }

        if (file_exists($_SERVER["DOCUMENT_ROOT"]."/css/hacks.css")) {
            $cssHacks .= "<!--[if lt IE 7]><link rel=\"stylesheet\" type=\"text/css\" href=\"/css/hacks.css\" /><![endif]-->";
        }


        foreach ($this->jsFiles as $file) {
            if (substr(0, 1, $file) != "<")
                $jsTags .= $file;
            else
                $jsTags .= "<script src=\"$file\" type=\"text/javascript\"></script>\n";
        }

        return <<<EOT
$doctype
<head>
<title>$pageTitle</title>
$metaTags
$cssTags
$cssHacks
$jsTags
EOT;
    }

    public static function js()
    {
        $array = func_get_args();
        if (!web::instance()->inDevelopment
            && file_exists($_SERVER["DOCUMENT_ROOT"].basename("/")."/js/all.min.js")) {
            return "<script src=\"/js/all.min.js\" type=\"text/javascript\"></script>";
        } else {
            foreach ($array as $file) $str .= js($file);
        }
        return $str;
    }

    public static function css()
    {
        $array = func_get_args();
        if (!web::instance()->inDevelopment
            && file_exists($_SERVER["DOCUMENT_ROOT"].basename("/")."/css/all.min.css")) {
            return "<link rel=\"stylesheet\" href=\"/css/all.min.css\" type=\"text/css\" media=\"screen\"/>";
        } else {
            foreach ($array as $file) $str .= css($file);
        }
        return $str;
    }
}
