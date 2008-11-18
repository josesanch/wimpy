<?
class AdministrationController extends ApplicationController {
	public $layout = "admin";
	public $auth;
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

	public $components = array("auth");

	public function beforeFilter() {

		if(web::instance()->isInProduction() && !$this->auth->isLogged())	{
			$this->auth->requestAuth();
			if(!$this->auth->isLogged()) exit;
		}
	}

	public function __construct() {
		parent::__construct();
		$this->view->data = $this;
		$this->menu = $this->preprocessMenu($this->menu);
		$this->view->menu = $this->getMenu();
	}

	public function getMenu() {
		$str = "";

		$this->selected_menu = $this->getSelectedMenu(web::instance()->model);
		foreach($this->menu as $menu => $submenu) {
			if(!$this->selected_menu) $this->selected_menu = $menu;

			$item = array_shift(array_values($submenu['items']));
			$href = "href='/admin/".$item['link']."'";
			$active = $this->selected_menu == $menu ? "class='active'" : "";
			$str .= "<li $active><a $href>$menu</a></li>\n";
		}
		return $str;
	}

	public function getSubMenu() {
		$subitems = array();
		$action = web::instance()->model;

		foreach($this->menu[$this->selected_menu]['items'] as $name => $submenu) {
			$active = $action == $submenu['link'] ? "class='active'" : "";

			$href = "href='/admin/".$submenu['link']."'";
			$subitems[]= "<li $active><a $href>".ucfirst($name)."</a></li>";
		}
		return implode("<span class='separador_submenu'> | </span>", $subitems);

	}

	private function preprocessMenu($menu_noprocess, $root = true) {
		$menu = array();
		foreach($menu_noprocess as $name => $data)
		{

			if(is_numeric($name)) $name = $data;

			if(is_array($data) && in_array("items", array_keys($data), true))   // Si está bien definido
			{
				$menu[$name]["link"] = $data["link"];
				$menu[$name]["target"] = $data["target"];
				$menu[$name]["params"] = $data["params"];
				$menu[$name]["items"] = $this->preprocessMenu($data["items"], false);
			}
			elseif(is_array($data)) // Si no está bien definido
			{
				$menu[$name]["link"] = "";
				$menu[$name]["items"] = $this->preprocessMenu($data, false);
			} else {	// Si es el final
				$menu[$data]= array("link" => "$name");
			}
		}
		return $menu;
	}

	private function getSelectedMenu($action, $menu = null, $root = False) {
			if(!$menu) {
				$menu = $this->menu;
				$root = True;
			}

			foreach($menu as $name => $data) {
				if($data['link']  == $action) {
					if($root) return $name;
					return True;
				}
				if(array_key_exists('items', $data) && $this->getSelectedMenu($action, $data['items'])) return $name;
			}
			return False;
	}

	public function __call($method, $params) {

		foreach(array("List", "Edit", "Save", 'Delete') as $action) {
			if($pos = strrpos($method, $action)) break;
		}

		if($pos) {
			$model = substr($method, 0, $pos);
			$controller_name = ucfirst($model."Controller");
			$admin_action = "admin$action";

			if(method_exists($model, $admin_action)) {
				$model = new $model();
				$this->view->content = $model->$admin_action($params[0]);
			} else {
				if(web::instance()->loadController($controller_name)) {
					$controller = new $controller_name();
				}

				if($controller && method_exists ($controller, "admin".$action)) {
					$action = "admin".$action;
					$this->view->content = $controller->$action($params[0]);
				} else {

					switch($action) {
						case "List":
//							$list = new html_extjs_grid(new $model());
//							$this->view->content = $list->toHtml();
							$instance = new $model();
//							$grid = new html_grid($instance);
							$this->view->content = "<br>".html_base_grid::toHtml($instance, null, $instance->grid_columns);
							break;

						case "Edit":
							$model = new $model();
							if(isset($params[0])) $model->select($params[0]);
//							$edit = new html_extjs_form($model);
							$edit = new html_autoform($model, $this->css);
							$this->view->content = "<br>".$edit->toHtml();
							break;

						case "Delete":
							$model = new $model();
							if(isset($params[0])) {
								 $model->select($params[0]);
								 $model->delete();
							}
//							web::instance()->redirect('/admin/'.get_class($model));
							web::instance()->location('/admin/'.get_class($model)."/list".web::params());
							exit;

						case "Save":
							$model = new $model();
							$model->saveFromRequest();
//							web::instance()->redirect('/admin/'.get_class($model));
							web::instance()->location('/admin/'.get_class($model)."/list".web::params());
//							echo "{ success : true}";
							exit;
					}
				}
			}
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
?>