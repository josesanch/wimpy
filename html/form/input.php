<?
class html_form_input extends html_object {

	protected $attrs = array
	(
		'type'    => 'text',
		'class'   => 'textbox',
		'value'   => ''
	);

	// Protected data keys
	protected $protect = array();

	// Validation rules, matches, and callbacks
	protected $rules = array();
	protected $matches = array();
	protected $callbacks = array();

	// Validation check
	protected $is_valid;

	// Errors
	protected $errors = array();
	protected $error_messages = array();


	public function __construct($name)
	{
		$this->attrs['name'] = $name;
	}

	public function __call($method, $args)
	{
		if ($method == 'rules') {
			if (empty($args))
				return $this->rules;

			// Set rules and action
			$rules  = $args[0];
			$action = substr($rules, 0, 1);

			if (in_array($action, array('-', '+', '='))) {
				// Remove the action from the rules
				$rules = substr($rules, 1);
			} else {
				// Default action is append
				$action = '';
			}

			$this->add_rules(explode('|', $rules), $action);
		}
		elseif ($method == 'name') {
			// Do nothing. The name should stay static once it is set.
			return $this->attrs['name'];
		} else {
			if (empty($args))
				return $this->attrs[$method];
			else
				$this->attrs[$method] = $args[0];

		}
		return $this;
	}

	public function toHtml() {
		$str = "";
		if($this->attrs['label']) $str .= "<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform'><span>".$this->attrs['label']."</span>\n";

		$str .= "\n	<input ".$this->getAttributes()."/>\n";
		if($this->attrs['label']) $str.= "</label>";
		return $str;
	}

}
?>
