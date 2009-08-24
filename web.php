<?

require dirname(__FILE__)."/functions.php";
require dirname(__FILE__)."/applicationcontroller.php";
require dirname(__FILE__)."/database.php";
require dirname(__FILE__)."/l10n.php";
require dirname(__FILE__)."/html/object.php";
require dirname(__FILE__)."/html/template.php";


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
		//error_reporting(E_STRICT);
		if(!web::$default_instance)  {
			web::$default_instance = $this;
			$this->l10n = new l10n();

		}
//		if($languages) $this->setLanguages($languages);
		if($database) $this->setDatabase($database);
		$this->application_path =  $_SERVER["DOCUMENT_ROOT"]."/../application/";
		if(web::request("debug")) $_SESSION['debug'] = web::request("debug");
	}

	/**
	 * Set the available languages in the website.
	 *
	 * Pone los idiomas disponibles en la web.	 *
 	 * \param array $langs Array of languages.<br/>
 	 * example: $web->setLanguages(array('es', 'en', 'pt'));
	 */

	public function setLanguages(array $langs) {
		$this->l10n->setLanguages($langs);
	}
	public function getLanguages() {
		return $this->l10n->getLanguages();
	}

	public function setImagesMaxSize($height, $width) {
		$this->images_max_size = array($height, $width);
	}

	public function setDatabase($database) {
		if(!$this->database = new Database($database)) {
			web::error("Error conectando con la base de datos $database[0]");
			exit;
		}
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
		if(in_array($uri[0],  $this->l10n->getLanguages())) {
			$lang = array_shift($uri);
			$this->l10n->setLanguage($lang);
		}

		$uri[0] = $uri[0] ? strtolower($uri[0]) : "index";	// Controlador por defecto indexController.
		$uri[1] = isset($uri[1]) && $uri[1] != "" ? strtolower($uri[1]) : "index";  // Método por defecto index.
		$this->controller = strtolower(array_shift($uri));
		$this->action = web::processAction(array_shift($uri));
		$this->params = $uri;
	}

	public static function uri($params, $all_params = true) {
		$uri = web::params($params, $all_params);
		return "/".web::instance()->controller."/".
								(web::instance()->model ? web::instance()->model."/" : "").
								web::instance()->action.
								$uri;
	}


	public static function params($params = null, $all_params = true) {
		$arr = array();
		$uri = '';
		$previous_params = web::instance()->params;
		$arr = web::processParams($previous_params, $arr, $uri);
		$arr = web::processParams($params, $arr, $uri);

		foreach($arr as $item => $value) { if(is_numeric($item) && $all_params)	$uri .= "/$value";	}
		foreach($arr as $item => $value) { if(!is_numeric($item))  	$uri .= "/$item=$value"; }

		if($_SERVER["QUERY_STRING"]) $uri .= "?".$_SERVER["QUERY_STRING"];
		return $uri;
	}

	private static function processParams($params, $arr, &$uri) {

		if(!is_array($params)) {
			$params = explode("/", $params);
			array_shift($params);
		}
		$count = 0;
		foreach($params as $p) {
			$a = explode('=', $p, 2);
			if(isset($a[1])) $arr[$a[0]] = $a[1];
			else $arr[$count++] = $p; // $uri .= "/".$p;
		}
		return $arr;
	}

	public function run($uri = null, $view = null, $render = false) {
		if($this->in_production) make_link_resources();
		$this->initialized = true;
		$_SESSION['initialized'] = true;

		if(!$uri) {
			$render = true;
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

			case 'resources':
				if(file_exists($_SERVER['DOCUMENT_ROOT']."/resources")) {
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


	private function getController($view = null, $admin = false) {
		// Si estamos en administración.
		if($this->controller == 'admin' && $this->action  == 'index') {
			$this->action = array_shift($this->params);
			$this->action = $this->action ? $this->action : 'index';
		}

		$controller_class = ucfirst(str_replace('-', '_', web::canonize($this->controller)))."Controller";
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
			$value = $controller->renderHtml($this->action);
		}
		if(method_exists($controller, "afterFilter")) {
			call_user_method_array("afterFilter", $controller, $this->params);
		}
		return $value;

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

		if($render) {
			$controller->render($this->action);
		} else {
			return $controller->renderHtml($this->action);
		}
	}

	private function callAjaxDispatcher() {
		// Cuando el controlador es ajax llamamos a la funcion function usando helpers_model_ajax del modelo /ajax/model/function
		$controller_name = $this->action;
		$action = array_shift($this->params);
		$model = new $controller_name();
		$ajax = new helpers_model_ajax($model);
		call_user_method_array($action, $ajax, $this->params);
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

	public function loadModel($name) {
		if(file_exists($this->application_path."models/$name.php")) {
			return class_exists($name, False);
		}
		return false;
	}


	public function setLanguage($lang) { $this->l10n->setLanguage($lang); }
	public function getLanguage() { return $this->l10n->getLanguage(); }

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

	private static function canonize($str) {
		$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', '"' => '-', '.' => '_');
		return str_replace(' ', '-', strtr(strtolower($str), $arr));
	}

	public static function database() {
		return web::instance()->database;
	}

	private function DealSpecialCases() {
		switch ($this->uri) {
			case '/robots.txt':
				header("Content-type: text/plain");
				echo "User-Agent: *\nAllow: /\n";
			exit;
		}
	}

	public function error404() {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");

		if(method_exists ("ErrorController", "notfoundAction")) {
			$controller = new ErrorController();
			$controller->setApplicationPath($this->application_path);
			$controller->view->controller = $this->controller;
			$controller->view->action = $this->action;
			call_user_method_array("notfoundAction", $controller, $this->params);
			echo $controller->renderHtml("notfound");
		} else {
//			echo "<h1>Error 404</h1>";
			$this->redirect("/");
		}

		exit;
	}

	public static function debug($texto, $file, $linea) {
//		log::to_file("EXEC $texto<hr>");

		if(web::request("debug") == "true" || $_SESSION['debug'] == "true") {
			echo "<pre style='padding: 1em; border: 1px dashed #666;'><span style='font-size: 0.6em;'>$file ($linea)</span>:\n";
			var_dump($texto);
			echo "</pre>";
		}
	}

	public static function error($texto, $file, $linea) {
		echo "<pre style='padding: 1em; border: 1px dashed #666;'><h3 style='color: red;'><span style='font-size: 0.6em;'>$file ($linea)</span>:\n";
		var_dump($texto);
		echo "</h3></pre>";
	}

	public static function warning($texto, $file, $linea) {
		if(!web::instance()->isInProduction()) {
			echo "<pre style='padding: 1em; border: 1px dashed #orange;'><h3 style='color: orange;'>
					<span style='font-size: 0.6em;'>$file ($linea)</span>:\n";
			var_dump($texto);
			echo "</h3></pre>";
		}
	}

}
?>
