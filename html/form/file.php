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
			$str = "
			<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform' style='clear: both;'>
				<span>".$this->attrs['label']."</span>";
		}

		if($this->model->$field) {
			$str .= "
				<span id='file_$field'>
					<input type='hidden' id='_file_$field' name='_file_$field' value='no-delete'/>
					<a href='".$this->model->$field->url()."' target='_blank'><img src='".($this->model->$field->src("90x90", 'INABOX'))."' style='border: 1px solid gray; padding: 1px; margin-bottom: 10px; float: left;'/></a>
					<a href=\"javascript:void(document.getElementById('file_$field').innerHTML = '');\"><img src='/resources/icons/cross.gif' border='0' style='border: 1px solid gray; float: left;'/></a>
				</span>
			";
		}

		$str .= "<input ".$this->getAttributes()."/>";
		if($this->attrs['label'])
			$str.= "
			</label>";
		return $str;
	}
}
?>
