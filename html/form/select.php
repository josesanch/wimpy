<?php
class html_form_select extends html_form_input
{

    protected $selectedValues = array();
    protected $disabledValues = array();
    protected $attrs = array
        (
            'type'    => 'select',
            'class'   => 'select',
            'value'   => '',
            'options' => array()

        );

    public function toHtml()
    {
        if (array_key_exists("label", $this->attrs)) {
            $str = "\n<label for='".(
                array_key_exists("id", $this->attrs) ? $this->attrs['id'] : $this->attrs['name']
            )."' class='autoform'>
                <span>".$this->attrs['label']."</span>\n";
        }

        $str .= "    <select ".$this->getAttributes(
            array('value', 'type', 'options', 'selectedOptions')
        ).">".$this->getOptions()."</select>\n";

        if (array_key_exists("label", $this->attrs)) {
            $str .= "</label>\n";
        }
        return $str;
    }

    public function add($values, $selectedValues = null, $at_top = false, $disabled = false )
    {
        if(is_array($values)) {
            if(count($values) > 0) {
                if(isset($values[0]) && is_a($values[0], 'activerecord')) {
                    foreach($values as $value) {
                        $row = array_values($value->getRowData());
                        $this->attrs['options'][$row[0]] = $row[1];
                    }

                } else {
                    if($at_top)
                        $this->attrs['options'] = $values + $this->attrs['options'];
                    else
                        $this->attrs['options'] += $values;
                }
            }
        }
        if(isset($selectedValues)) $this->setSelectedValues($selectedValues);
        return $this;
    }

    public function select($values)
    {

        if (is_string($values)) {
            $arrValues = explode(",", $values);
            $values = array();
            foreach ($arrValues as $val) {
                $values[]= trim($val);
            }
        }

        $this->selectedValues = $values;
        return $this;
    }

    public function disabled($values)
    {
        if (is_string($values)) $values = array_map(trim, explode(",", $values));
        $this->disabledValues = $values;
        return $this;
    }

    public function remove($values) {
        if (is_string($values)) $values = array_map(trim, explode(",", $values));
        foreach ($values as $value) {
            if (isset($this->attrs['options'][$value]))
                unset($this->attrs['options'][$value]);

        }
    }

    protected function getOptions()
    {
        $html = "";

        $values = is_array($this->selectedValues) ?
            $this->selectedValues :
            array($this->selectedValues);

        $disabledValues = is_array($this->disabledValues) ?
            $this->disabledValues :
            array($this->disabledValues);

        if (array_key_exists("options", $this->attrs) && is_array($this->attrs["options"])) {

            foreach ($this->attrs['options'] as $value => $text) {
                $selected = in_array($value, $values) ? " selected='selected'" : "";
                $disabled = in_array($value, $disabledValues) ? " disabled='disabled'" : "";
                $html .= "
                       <option value=\"$value\"$selected{$disabled}>$text</option>";
            }
        }
        return $html;

    }

    public function clear() {
        $this->attrs['options'] = array();
    }

    public function getSelectedValues()
    {
        if ($this->selectedValues) {
            if (count($this->selectedValues) == 1)
                return $this->selectedValues[0];
            return $this->selectedValues;
        }
        return array_shift(array_keys($this->attrs['options']));
    }
}
