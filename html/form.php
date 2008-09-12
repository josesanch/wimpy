<?
class html_form extends html_object {

	protected $attrs = array(
		'method' => 'POST'
	);
	protected $inputs = array();
	protected $model = null;

	public function __construct($name, $action = null, $method = null, $attrs = array()) {
		$this->attrs["name"] = $name;
		if($method) $this->attrs["method"] = $method;
		$this->attrs['action'] = isset($action) ? $action : $_SERVER["PHP_SELF"];
		$this->attrs += $attrs;
	}

	public function toHtml() {
		if($this->attrs["onsubmit"] == "return form(this);") {
			kernel::loadJS("form");
		}


		$this->process();
		return "<FORM ".$this->getAttributes().">".$this->data."</FORM>";
	}


	public function __call($method, $args) {

		// Class name
		$input = 'html_form_'.$method;
		switch(count($args)) {
			case 1:
				$input = new $input($args[0]);
			break;
			case 2:
				$input = new $input($args[0], $args[1]);
			break;
		}

		$this->inputs[]= $input;
		return $input;
	}

	public function add($object) {
		$this->inputs[]= $object;
	}
	protected function process() {
		foreach($this->inputs as $input) {
			if(is_a($input, 'html_form_file')) $this->attrs['enctype'] = 'multipart/form-data';
			$this->data .= $input;

		}
	}

	public function auto($field, $lang = null, $tmp_upload = null) {

		$attrs = $this->model->getFields($field);

/*
		echo "<pre>";
		var_dump($attrs);
		echo "</pre>";
		*/
		if(isset($attrs["belongs_to"])) {
				$model_name = $attrs["belongs_to"];
				$model_item = new $model_name;
				$name =$model_item->getTitleField();

				$input = new html_form_select($field);
				$input->add($model_item->select("columns: id as value, $name as text"))->select($this->model->$field);
		} else {
			switch($attrs['type']) {
				case 'text':
					if($attrs['html']) {
						$input = new html_form_htmleditor($lang ? $field."|".$lang : $field);
						$input->width('100%')->height(300);
					} else {
						$input = new html_form_textarea($lang ? $field."|".$lang : $field);
						$input->rows(10)->cols(60);
					}
					$input->value($this->model->get($field, ($lang ? $lang : l10n::instance()->getDefaultLanguage()), false));
				break;

				case 'date':
					$input = new html_form_date($field);
					if($this->model->$field != '0000-00-00' and $this->model->$field != '') $input->value(strftime('%d/%m/%Y', strtotime($this->model->$field)));

				break;

				case 'image':
				case 'file':
					$input = new html_form_file($field, $this->model);
				break;

				case 'enum':
					$input  =  new html_form_select($field);
					$values = array();
					foreach($attrs['values'] as $val)  $values[$val] =  $val;
					$input->add($values);
					$input->select($this->model->$field);
				break;

				case 'files':
					$input = new html_form_files($lang ? $field."|".$lang : $field, $this->model, $tmp_upload);
					break;

				case 'int':
					$size = $attrs['size'] ? $attrs['size'] : 11;

				case 'varchar':
				default:
					$input = new html_form_input($lang ? $field."|".$lang : $field);
					if(!$size) $size = $attrs['size'] ? ($attrs['size'] < 80 ? $attrs['size'] : 80)  : 50;
					$input->size($size);
					if($attrs['size']) $input->maxsize($attrs['size']);
					$input->value($this->model->get($field, ($lang ? $lang : l10n::instance()->getDefaultLanguage()) , false));
			}
		}
		if($lang)
			$input->label($attrs['label'] ? $attrs['label']." ($lang)" : $field." ($lang)");
		else
			$input->label($attrs['label'] ? $attrs['label'] : $field);

		$this->inputs[]= $input;
		if($attrs['l10n'] && !$lang) {
			foreach(l10n::instance()->getNotDefaultLanguages() as $lang) $this->auto($field, $lang);
		}

		return $input;
	}

	public function get($field) {
		foreach($this->inputs as &$input) {
			if(is_a($input, 'html_object') && $input->name() == $field) return $input;
		}
	}

	public function setModel($model) {
		$this->model = $model;
	}
}
?>
