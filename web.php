<?
include_once(dirname(__FILE__)."/functions.php");
require dirname(__FILE__)."/applicationcontroller.php";

class Web {

	private $images_max_size = array(1024, 1024);
	public $laguages = array("es");
	private $html_template_dir = "/templates";
	private $debug = false;
	private $in_production = true;
	public $database;
	private static $default_instance;	// La primera clase que se crea
	private $default_controller = "index";
	private $application_path;
	public $defaultHtmlEditor = "fckeditor";
	public $controller, $action, $params, $uri, $model;
	public $l10n;
	public $initialized = false;


	public function __construct($database = null, $languages = null) {
		session_start();
		if(isset($_SESSION['initialized'])) $this->initialized = true;

		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING );

		if(!web::$default_instance)  {
			web::$default_instance = $this;
			$this->l10n = new l10n();
			make_link_resources();
		}
//		if($languages) $this->setLanguages($languages);
		if($database) $this->setDatabase($database);

		$this->application_path =  $_SERVER["DOCUMENT_ROOT"]."/../application/";
	}


	public function setLanguages($langs) {
		$this->l10n->setLanguages($langs);
	}
	public function getLanguages() {
		return $this->l10n->getLanguages();
	}

	public function setImagesMaxSize($height, $width) {
		$this->images_max_size = array($height, $width);
	}

	public function setDatabase($database) {
		$this->database = new Database($database);
		if(!$this->database->tableExists('images')) {
			create_images_and_files_tables($this->database);
		}
		$this->database->exec('SET character_set_results = utf8;');
		$this->database->exec('SET character_set_client = utf8;');

	}

	public function setHtmlTemplatesDir($templates_dir) {
		$this->html_template_dir = $templates_dir;
	}

	public static function instance() {
		return web::$default_instance;
	}

	private function parseInfo($uri) {
		$url = parse_url($uri);
		$uri = explode("/", substr($url["path"], 1));
		$uri[0] = $uri[0] ? strtolower($uri[0]) : "index";	// Controlador por defecto indexController.
		$uri[1] = isset($uri[1]) && $uri[1] != "" ? strtolower($uri[1]) : "index";  // Método por defecto index.
		$this->controller = strtolower(array_shift($uri));
		$this->action = web::processAction(array_shift($uri));
		$this->params = $uri;
	}

	public static function uri($params) {
		$uri = web::params($params);
		return "http://".$_SERVER['SERVER_NAME']."/".
								web::instance()->controller."/".
								(web::instance()->model ? web::instance()->model."/" : "").
								web::instance()->action.
								$uri;
	}


	public static function params($params) {
		$arr = array();
		$uri = '';
		$previous_params = web::instance()->params;
		$arr = web::processParams($previous_params, $arr, $uri);

		$arr = web::processParams($params, $arr, $uri);

		foreach($arr as $item => $value) {	$uri .= "/$item=$value"; }
		return $uri;
	}

	private function processParams($params, $arr, &$uri) {
		if(!is_array($params)) {
			$params = explode("/", $params);
			array_shift($params);
		}

		foreach($params as $p) {
			$a = explode('=', $p, 2);
			if(isset($a[1])) $arr[$a[0]] = $a[1];
			else  $uri .= "/".$p;
		}
		return $arr;
	}

	public function run($uri = null, $view = null, $render = false) {
		$this->initialized = true;
		$_SESSION['initialized'] = true;

		if(!$uri) {
			$render = true;
			$uri = $_SERVER["REQUEST_URI"];
		}
		$this->uri = $uri;
		$this->parseInfo($uri);
		switch ($this->controller) {
			case 'admin':
				return $this->callAdminDispatcher($render);
			break;

			case 'ajax':
				$this->callAjaxDispatcher();
			break;

			default:
				return $this->callDefaultDispatcher($render, $view);

		}
	}


	private function getController($view = null, $admin = false) {

		// Si estamos en administración.
		if($this->controller == 'admin' && $this->action  == 'index') {
			$this->action = array_shift($this->params);
			$this->action = $this->action ? $this->action : 'index';
		}

		$controller_class = ucfirst($this->controller)."Controller";
		$action = $this->action;

		if(!$this->loadController($controller_class) || (!method_exists ($controller_class, $this->action."Action") && !$admin)) {
			$action = "error";
			$controller_class = "ErrorController";
			array_unshift($this->params, $this->controller, $this->action);
			$this->loadController("ErrorController");
		}

		$controller = new $controller_class($view);
		$controller->setApplicationPath($this->application_path);
		$controller->view->controller = $this->controller;
		$controller->view->action = $this->action;

		if(method_exists($controller, "beforeFilter")) {
			call_user_method_array("beforeFilter", $controller, $this->params);
		}

		return array($controller, $action);
	}


	private function callDefaultDispatcher($render = true, $view = null) {
		list($controller, $action) = $this->getController($view);

		if(!$render) $controller->layout = '';

		call_user_method_array($action."Action", $controller, $this->params);

		if($render) {
			$controller->render($this->action);
		} else {
			return $controller->renderHtml($this->action);
		}
	}

	private	function callAdminDispatcher($render = true) {
		if($this->action == "index") {
			list($controller, $action) = $this->getController($view, true);
			call_user_method_array($action."Action", $controller, $this->params);
		} else {
			list($controller, $action) = $this->getController(null, true);
			$this->model = $model = $this->action;
			$this->action = $action = array_shift($this->params);
			// By default we call to list method of the model.
			if(!$action) { $this->redirect("/admin/$model/list"); exit; }
			call_user_method_array($model.ucfirst($action), $controller, $this->params);
		}

		if($render)
			$controller->render($this->action);
		else
			return $controller->renderHtml($this->action);
	}

	private function callAjaxDispatcher() {
		// Cuando el controlador es ajax llamamos a la funcion functionAjax del modelo /ajax/model/function
		$controller_name = $this->action;
		$action = array_shift($this->params);
		$model = new $controller_name();
		call_user_method_array($action."Ajax", $model, $this->params);
	}

	public function getApplicationPath() {
		return $this->application_path;
	}


	public function loadController($name) {
		if(file_exists($this->application_path."controllers/$name.php")) {
			require_once($this->application_path."controllers/$name.php");
			return class_exists($name, False);
		}
		return false;
	}

	public function setLanguage($lang) { $this->l10n->setLanguage($lang); }
	public function getLanguage($lang) { return $this->l10n->getLanguage(); }

	public function setDefaultLanguage($lang) {
		$this->l10n->setDefaultLanguage($lang);
	}

	public function setInProduction($p) {
		$this->in_production = $p;
	}

	public function isInProduction() {
		return $this->in_production;
	}

	public function redirect($uri) {
		$this->run($uri, null, True);
	}

	public function location($uri) {
		header("Location: $uri");
		exit;
	}

	public static function request($param) {
		$arr = array();
		$arr = web::processParams(web::instance()->params, $arr);
		$arr = array_merge($arr, $_REQUEST);
		return $arr[$param];

	}

	private function processAction($param) {
		return str_replace('-', '_', $param);
	}

	public function setDefaultHtmlEditor($editor = "fckeditor") {
		$this->defaultHtmlEditor = $editor;
	}
	public function initialized() {
		return $this->initialized;
	}

	public function autoSelectLanguage() {
		if(!$this->initialized)	$this->l10n->autoSelectLanguage();
	}

}
?>
