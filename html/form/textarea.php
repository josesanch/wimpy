<?
class html_form_textarea extends html_form_input {

	protected $attrs = array
	(
		'type'    => 'textarea',
		'class'   => 'textbox',
		'value'   => ''
	);
	public function toHtml() {
		if($this->attrs['label']) {
			$str = "<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform textarea'><span>".$this->attrs['label']."</span>\n";
		}
		$str .= "<textarea ".$this->getAttributes('value, type').">".$this->attrs['value']."</textarea>";
		if($this->attrs['label']) $str .= "</label>";
		return $str;
	}
}
?>
