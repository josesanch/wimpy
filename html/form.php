<?
class html_form extends html_object {

	public $css = null;
	private $js = "";

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
		return "<form ".$this->getAttributes().">\n".$this->data."</form>\n";
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
		$this->data .= "
<script>
	$(document).ready(function() {
			$this->js
	});
</script>";
	}

	public function auto($field, $lang = null, $tmp_upload = null) {

		$attrs = $this->model->getFields($field);

		if(isset($attrs["belongs_to"])) {
				$model_name = $attrs["belongs_to"];
				$model_item = new $model_name;
				$name = $model_item->getTitleField();
				if(!$attrs['autocomplete']) {
					$input = new html_form_select($field);
					$input->add($model_item->select("columns: id as value, $name as text"))->select($this->model->$field);
				} else {
					// Autocomplete
					$input = new html_form_input($field."_autocomplete");
					$input_hidden = new html_form_hidden($field);
					if($this->model->$field) {
						$input_hidden->value($this->model->$field);
						$model_item->select($this->model->$field);
						$input->value($model_item->$name);
					}

					$this->addJS("
								$('#{$field}_autocomplete').autocomplete('/ajax/$model_name/autocomplete')
											.result(function(event, data, formatted) {
												if (data)
													$('#$field').val(data[1]);
												else
													$('#$field').val('');
											}).blur(function(){
											    $(this).search();
											});
					");
					$this->add($input_hidden);

				}

		} else {
			switch($attrs['type']) {
				case 'text':
					if($attrs['html']) {
						$input = new html_form_htmleditor($lang ? $field."|".$lang : $field);
						$input->width('100%')->height(300)->css($this->css);
					} else {
						$input = new html_form_textarea($lang ? $field."|".$lang : $field);
						$input->rows(10)->cols(60);
					}
					$input->value($this->model->get($field, ($lang ? $lang : l10n::instance()->getDefaultLanguage()), false));
				break;

				case 'date':
					$input = new html_form_input($field);
					$this->addJS("$('#$field').datepicker({changeMonth: true, changeYear: true}, $.datepicker.regional['es']);\n");
					$input->size(10);
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
					if(!$size) $size = $attrs['size'] ? ($attrs['size'] < 45 ? $attrs['size'] : 45)  : 45;
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

	public function remove($field) {
		$this->inputs = array_filter($this->inputs, create_function('$input', 'return !(is_a($input, "html_object") && $input->name() == '.$field.');'));
/*		foreach($this->inputs as $id => $input) {

			if(is_a($input, 'html_object') && $input->name() == $field) {
				unset($this->inputs[]);
			}
		}
		*/
	}
	public function get($field) {
		foreach($this->inputs as $input) {
			if(is_a($input, 'html_object') && $input->name() == $field) return $input;
		}
	}

	public function setModel($model) {
		$this->model = $model;
	}

	public function addJS($str) {
		$this->js .= $str;
	}
}
?>
