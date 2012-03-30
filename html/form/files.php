<?
class html_form_files extends html_form_input {

    protected $model;
    public static $instance = false;
    private $prepend = '';
    private $tmp_upload;
    private static $default_instance;	// La primera clase que se crea
    protected $attrs = array
    (
        'type'    => 'file',
        'class'   => 'textbox',
        'value'   => ''
    );

    public function __construct($field, $model, $tmp_upload, $form = null) {
        $this->model = $model;
        $this->tmp_upload = $tmp_upload;
        $this->form = $form;
        parent::__construct($field);
        if(!html_form_files::$default_instance)  {
            html_form_files::$default_instance = $this;
            $this->prepend = "
                <script type='text/javascript' src='/resources/uploadify/jquery.uploadify.v2.1.0.min.js'></script>
                <script type='text/javascript' src='/resources/uploadify/swfobject.js'></script>
                <link href='/resources/uploadify/uploadify.css' rel='stylesheet' type='text/css' />
                <div id='loader' style='display: none;'></div>
                ";
        }

    }

    public function toHtml()
    {
        $model_name = get_class($this->model);
        $iditem = $this->model->id;
        $field = $this->attrs['name'];
        $tmp_upload = $this->tmp_upload;
        $fileDataName = $field ? $field : "file";

        $str .= "
        <label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."'
            class='autoform no-margin' style='clear: both;'>
            ".$this->attrs['label']."
        </label>
        <div id='container-files-$field'></div>
        <div id='fileQueue_$field'></div>";
        if (web::auth()->hasPermission($this->model, auth::MODIFY)) {
            $str .= "
        <div class='contenedor-boton-upload'>
            <input type='file' name='uploadify_$field' id='uploadify_$field'/>
        </div>";
        }
        $javascript = "new GridFiles('$field', '$model_name', '$iditem', '$tmp_upload');";

        if($this->form) {
            $this->form->addJS($javascript, true);
        } else {
            $str .= "<script type='text/javascript'>
                        $(document).ready(function() { $javascript });</script>";
        }


        return $this->prepend."$str";
    }
}
?>
