<?php

class html_form extends html_object
{

    public $css = null;
    protected $_javascript = "";
    protected $_javascriptOnload = "";
    protected $_endData;

    protected $attrs = array(
        'method' => 'POST'
    );
    protected $inputs = array();
    protected $model = null;

    public function __construct($name, $action = null, $method = null, $attrs = array())
    {
        $this->attrs["name"] = $name;
        if($method) $this->attrs["method"] = $method;
        $this->attrs['action'] = isset($action) ? $action : $_SERVER["PHP_SELF"];
        $this->attrs += $attrs;
    }

    public function toHtml()
    {
        if($this->attrs["onsubmit"] == "return form(this);") {
            kernel::loadJS("form");
        }


        $this->process();
        return "<form ".$this->getAttributes().">\n".$this->data."</form>\n";
    }


    public function css($url)
    {
        $this->css = $url;
    }

    public function __call($method, $args)
    {

        // Class name
        $input = 'html_form_'.$method;
        switch (count($args)) {
            case 1:
                $input = new $input($args[0]);
            break;
            case 2:
                $input = new $input($args[0], $args[1]);
            break;
        }

        $this->inputs[]= $input;
        return $input;
    }

    public function add($object)
    {
        $this->inputs[]= $object;
    }


    protected function process()
    {
        foreach($this->inputs as $input) {
            if(is_a($input, 'html_form_file'))
                $this->attrs['enctype'] = 'multipart/form-data';
            $this->data .= $input;
        }
        $this->data .= "
<script type='text/javascript'>
    $(document).ready(function() {
            $this->_javascriptOnload
    });
    $this->_javascript;
</script>";
    }

    public function auto($field, $lang = null, $tmp_upload = null)
    {

        $attrs = $this->model->getFields($field);

        if(isset($attrs["belongs_to"])) {
                $relatedModelName = $attrs["belongs_to"];

                if(!$attrs['autocomplete']) {

	                $relatedModel = new $relatedModelName();
	                $name = $relatedModel->getTitleField();

                    $input = new html_form_select($field);
                    $input	->add(
                    			$relatedModel->select(
                    				"columns: id as value, $name as text"
                    			)
                    		)
                    		->select($this->model->$field);

                } else {
                    // Autocomplete
					$value = $this->model->$field;
					$relatedModel = new $relatedModelName($value);
					$titleField = $relatedModel->getTitleField();
					// Hidden field that containt the real value
                    $inputHidden = new html_form_hidden($field);
					$inputHidden->value($value)->class("");
					// Text field that contain the name of the field.
                    $input = new html_form_input($field."_autocomplete");
                    $input->value($relatedModel->$titleField);

                    $this->addJS("
                                $('#{$field}_autocomplete').autocomplete('/ajax/$relatedModelName/autocomplete')
                                            .result(function(event, data, formatted) {
                                                if (data)
                                                    $('#$field').val(data[1]);
                                                else
                                                    $('#$field').val('');
                                            }).blur(function(){
                                                $(this).search();
                                            });
                    ", true);
                    $this->addToEnd($inputHidden);
                }

        } else {
            switch($attrs['type']) {
                case 'text':
                    if($attrs['html']) {
                        $input = new html_form_htmleditor($lang ? $field."|".$lang : $field);
                        $input->width('100%')->height(300)->css($this->css);
                    } else {
                        $input = new html_form_textarea($lang ? $field."|".$lang : $field);
                        $input->rows(10)->cols(60);
                    }

                    $input->value($this->model->get($field, ($lang ? $lang : l10n::instance()->getDefaultLanguage()), false));
                break;

                case 'date':
                    $input = new html_form_input($field);
                    $this->addJS("$('#$field').datepicker({changeMonth: true, changeYear: true}, $.datepicker.regional['es']);\n", true);
                    $input->size(10);
                    if($this->model->$field != '0000-00-00' and $this->model->$field != '')
                        $input->value(strftime('%d/%m/%Y', strtotime($this->model->$field)));

                break;

                case 'image':
                case 'file':
                    $input = new html_form_file($field, $this->model);
                break;

                case 'enum':
                    $input  =  new html_form_select($field);
                    $values = array();
                    foreach($attrs['values'] as $val)  $values[$val] =  $val;
                    $input->add($values);
                    $input->select($this->model->$field);
                break;

                case 'files':
                    $input = new html_form_files($lang ? $field."|".$lang : $field, $this->model, $tmp_upload, $this);
                    break;

                case 'int':
                    $size = $attrs['size'] ? $attrs['size'] : 11;

                case 'varchar':
                default:
                    $inputName = $lang ? $field."|".$lang : $field;
                    $input = new html_form_input($inputName);

                    if(!$size)
                        $size = $attrs['size'] ? ($attrs['size'] < 45 ? $attrs['size'] : 45) : 45;

                    $input->size($size);
                    if($attrs['size']) $input->maxsize($attrs['size']);
                    $input->class($input->class()." ".$attrs['validate']);
                    if($attrs['not null']) $input->class($input->class()." required");
                    $value = $this->model->get($field, ($lang ? $lang : l10n::instance()->getDefaultLanguage()));

                    $input->value(str_replace('"', '&quot;', $value), false);
            }
        }

        if($lang)
            $input->label($attrs['label'] ? $attrs['label']." ($lang)" : ucfirst($field)." ($lang)");
        else
            $input->label($attrs['label'] ? $attrs['label'] : ucfirst($field));

        if($attrs["dialog"]) {

            $input->add("
                <input type='button' value='' class='dialog'
            	onclick='showModelDialog(\"$relatedModelName\",\"$field\",\"".$this->attrs["name"]."\")'/>"
			);
			$this->addToEnd("<div id='{$relatedModelName}_dialog'></div>");
        }

        $this->inputs[]= $input;


        if($attrs['l10n'] && !$lang) {
            foreach(l10n::instance()->getNotDefaultLanguages() as $lang)
            	$this->auto($field, $lang);
        }

        return $input;
    }

    public function remove($field)
    {
        $this->inputs = array_filter(
        	$this->inputs,
        	create_function(
        		'$input',
        		'return !(is_a($input, "html_object")
        		&& $input->name() == '.$field.');'
        	)
        );
    }

    public function &get($field)
    {
        foreach($this->inputs as $input) {
            if(is_a($input, 'html_object') && $input->name() == $field)
                return $input;
        }
    }
    public function addAfter($field, $data)
    {
        $count = 0;

        foreach($this->inputs as $input) {
            if(is_a($input, 'html_object') && $input->name() == $field)
                break;
            $count++;
        }
        array_splice($this->inputs, $count+1, 0, $data);


    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function addJS($str, $onload = false)
    {
        if($onload)
            $this->_javascriptOnload .= $str;
        else
            $this->_javascript .= $str;
    }

    public function addToEnd($data)
    {
        $this->_endData .= $data;
    }
}
?>
