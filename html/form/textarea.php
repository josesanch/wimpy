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
			$str = "<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform'>".$this->attrs['label']."</label>";
		}
		return "$str<textarea ".$this->getAttributes('value, type').">".$this->attrs['value']."</textarea>";
	}
}
?>
