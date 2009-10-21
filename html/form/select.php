<?
class html_form_select extends html_form_input {

	protected $selectedValues;
	protected $attrs = array
	(
		'type'    => 'select',
		'class'   => 'select',
		'value'   => '',
		'options' => array()

	);

	public function toHtml() {
		if($this->attrs['label']) {
			$str = "\n<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform'>\n    <span>".$this->attrs['label']."</span>\n";
		}

		$str .= "	<select ".$this->getAttributes(array('value', 'type', 'options', 'selectedOptions')).">".$this->getOptions()."</select>\n";
		if($this->attrs['label']) $str .= "</label>\n";
		return $str;
	}

	public function add($values, $selectedValues = null, $at_top = false ) {
		if(is_array($values)) {
			if(count($values) > 0) {
				if(is_a($values[0], 'activerecord')) {
					foreach($values as $value) {
						$row = array_values($value->getRowData());
						$this->attrs['options'][$row[0]] = $row[1];
					}

				} else {
					if($at_top)
						$this->attrs['options'] = $values + $this->attrs['options'];
					else
						$this->attrs['options'] += $values;
/*					foreach($values as $value => $text) {
						$this->attrs['options'][$value] = $text;
					}
					*/
				}
			}
		}
		if(isset($selectedValues)) $this->setSelectedValues($selectedValues);
		return $this;
	}

	public function select($values) {
		if (is_string($values)) $values = split("[ ]?,[ ]?", $values);
		$this->selectedValues = $values;
		return $this;
	}

	private function getOptions() {
		foreach($this->attrs['options'] as $value => $text) 	{
			if(isset($this->selectedValues)) {
				$selected = in_array($value, is_array($this->selectedValues) ? $this->selectedValues : array($this->selectedValues)) ? " selected" : "";
				}
			$html .= "\n		<option value=\"$value\"$selected>$text</option>";
		}
		return $html;

	}

	public function clear() {
		$this->attrs['options'] = array();
	}
}
?>
