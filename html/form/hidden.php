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
		//var_dump($this->attrs);
        $str = "";
        if($this->attrs['label'])
            $str .= "\n<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] ).
                    "' class='autoform'>\n
                        <span>".$this->attrs['label']."</span>\n";

        $str .= "     <input ".$this->getAttributes()."/>";
        if($this->data) $str .= "\n".$this->data;
        if($this->attrs['label']) $str.= "\n</label>";
        return $str;
    }
}
?>
