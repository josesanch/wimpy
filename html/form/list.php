<?php
class html_form_list extends html_form_select
{

    protected $selectedValues = array();
    protected $disabledValues = array();
    protected $attrs = array
    (
        'type'    => 'list',
        'class'   => 'list',
        'value'   => '',
        'options' => array()

    );

    public function toHtml()
    {
        if ($this->attrs['label']) {
            $str = "\n<label for='".(
                $this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name']
                )."' class='autoform'>
                <span>".$this->attrs['label']."</span>\n";
        }

        $str .= "<ul ".$this->getAttributes(array('value', 'options', 'selectedOptions')
        ).">".$this->getListItems()."</ul>\n";

        if($this->attrs['label'])
            $str .= "</label>\n";
        return $str;
    }

    protected function getListItems()
    {
		$values = is_array($this->selectedValues) ?
                    $this->selectedValues :
                    array($this->selectedValues);

		$disabledValues = is_array($this->disabledValues) ?
			$this->disabledValues :
			array($this->disabledValues);

        foreach ($this->attrs['options'] as $value => $text) {
            $selected = in_array($value, $values) ? " selected='selected'" : "";
            $disabled = in_array($value, $disabledValues) ? " disabled='disabled'" : "";
            $html .= "
                       <li value=\"$value\"$selected{$disabled}>$text</li>";
        }
        return $html;

    }



}
