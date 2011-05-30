<?php

class html_form_autocomplete extends html_form_input
{
    protected $attrs = array(
        'type'    => 'hidden',
        'class'   => 'textbox',
        'value'   => ''
    );

    public function __construct() {
        // Autocomplete
        $value = $this->model->$field;
        $relatedModel = new $relatedModelName($value);

        if ($attrs["show"]) {
            $primaryKey = array_shift($relatedModel->getPrimaryKeys());
                    $data = $relatedModel->selectFirst(
                        "columns: ".$attrs["show"]." as text",
                        "where: $primaryKey='".$relatedModel->get($primaryKey)."'",
                        "order: text"
                    );
                    $text = $data->text;
                } else {
                    $titleField = $relatedModel->getTitleField();
                    $text = $relatedModel->$titleField;
                }

                // Hidden field that contain the real value
                $input = new html_form_hidden($field);
                $input->value($value)->class("");

                // Text field that contain the name of the field.
                $size = $attrs['size'] ? ($attrs['size'] < 45 ? $attrs['size'] : 45) : 45;
                $inputAutocomplete = new html_form_input($field."_autocomplete");
                $inputAutocomplete->value($text)->size($size)->class("autocomplete textbox");
                $input->label($attrs['label'] ? $attrs['label'] : ucfirst($field));


                if(!$attrs['autocomplete'] || $attrs["readonly"])
                    $inputAutocomplete->disabled(true);


                if ($attrs["dialog"] && !$attrs["readonly"]) {

                    $inputAutocomplete->add(
                        "<input type='button' value='' class='dialog'
							onclick='Dialog.open(\"$relatedModelName\",\"$field\",\"".$this->attrs["name"]."\")'/>"
                    );

                    $this->addToEnd("<div id='{$field}_dialog'></div>");
                }
                $options = array();

    }

    protected function _getJs()
    {

        $options[]= "source : '/ajax/$relatedModelName/autocomplete/field=$field',
                     select : function(event, ui) {
                       $('#$field').val(ui.item.id);
                         if(typeof(autocompleteCallback) != 'undefined')
                           autocompleteCallback('$relatedModelName', '$field', '', data[1]);
                     }";

        return "<script>$('#{$field}_autocomplete').autocomplete({".implode(",", $options)."});</script>";


    }

    public function toHtml()
    {
        $str = "";
        if($this->attrs['label']) {
			if ($this->_getLabelFor()) $for = " for='".$this->_getLabelFor()."'";
            $str .= "\n<label$for class='autoform'>\n
                        <span>".$this->attrs['label']."</span>\n";
		}
        $str .= "     <input ".$this->getAttributes()."/>";
        if($this->data) $str .= "\n".$this->data;
        if($this->attrs['label']) $str.= "\n</label>";
        return $str;
    }


}
