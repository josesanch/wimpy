<?
class html_base_form extends html_form_input {

	protected $model, $data = '';
	private $name, $url;

	protected $attrs = array
	(
		'method'    => 'post'
	);


	public function __construct($model = null) {
		if(is_object($model)) {
			$this->model = $model;
			$this->url = "/admin/".$this->name."/save";
			$this->attrs['name'] = get_class($model);
			$this->action($this->url)->id($this->name);
		} else  {
			$this->attrs['name'] = $model;
			$this->id($model);

		}

	}

	public function add($str) {
		$this->data .= $str;
	}

	public function toHtml() {

		return $this->head().$this->getParsedData().$this->foot();
	}

	private function head() {

		//$url =
		$str = init_extjs()."<form ".$this->getAttributes().">";
		if($this->model) {
			foreach($this->model->getPrimaryKeys() as $item) {
				$str .= "<input type='hidden' name='$item' value='".$this->model->$item."'>";
			}
		}
		return $str;

	}

	private function foot() {
		return "</form>";
	}

	public function setButtons($buttons) {


	}


	private function getParsedData() {
		$data = $this->data;
		$template = new html_template();
		$template->loadData($data);
		$template->assign("model", $this->model);
		$str =  $template->toHtml();
/*
		$str .= "<script>Ext.QuickTips.init();";

		foreach($template->getFormItems() as $item) {
			$str .= html_extjs_field::toHtml($this->model, $item, $item);
		}
		$str .= "</script>";
		*/
		return $str;

	}
}
?>
