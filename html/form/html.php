<?
class html_form_html extends html_object {

    protected $attrs = array
    (
        'type'    => 'html',
        'value'   => ''
    );

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

    public function toHtml()
    {
        return $this->attrs["value"].$this->data;
    }

}
