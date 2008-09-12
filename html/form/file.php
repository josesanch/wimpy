<?
class html_form_file extends html_form_input {

	protected $model;
	protected $attrs = array
	(
		'type'    => 'file',
		'class'   => 'textbox',
		'value'   => ''
	);

	public function __construct($field, $model) {
		$this->model = $model;
		parent::__construct($field);
	}

	public function toHtml() {
		$field = $this->attrs['name'];
		if($this->attrs['label']) {
			$str = "<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform'>".$this->attrs['label']."</label>";
		}

		if($this->model->$field) $str .= "<div id='file_$field'>
			<input type='hidden' id='_file_$field' name='_file_$field' value='no-delete'>
			<img src='".($this->model->$field->src("90x90", 'INABOX'))."' style='border: 1px solid gray; padding: 1px; margin-bottom: 10px;' align='left'>
				<a href=\"javascript:void(document.getElementById('file_$field').innerHTML = '');\"><img src='/resources/icons/cross.gif' border='0' style='border: 1px solid gray; '></a>
			</div>
		";
		return "$str<div style='clear: both;'><input ".$this->getAttributes()."></div>";
	}
}
?>
