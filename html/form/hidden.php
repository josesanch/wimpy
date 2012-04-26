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

        if(array_key_exists("label", $this->attrs)) {
            $str .= "\n<label for='".($this->_getLabelFor()).
                    "' class='autoform'>\n
                        <span>".$this->attrs['label']."</span>\n";
        }

        $str .= "     <input ".$this->getAttributes()."/>";

        if(isset($this->data)) $str .= "\n".$this->data;

        if(array_key_exists("label", $this->attrs)) $str.= "\n</label>";

        return $str;
    }
}
?>
