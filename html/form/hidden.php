<?
class html_form_hidden extends html_form_input {

	protected $attrs = array
	(
		'type'    => 'hidden',
		'class'   => 'textbox',
		'value'   => ''
	);

	 public function toHtml()
    {
        $str = "";

        $str .= "     <input ".$this->getAttributes()."/>";
        if($this->data) $str .= "\n".$this->data;
        return $str;
    }
}
?>
