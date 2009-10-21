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
				<script type='text/javascript' src='/resources/uploadify/jquery.uploadify.v2.0.3.min.js'></script>
				<script type='text/javascript' src='/resources/uploadify/swfobject.js'></script>
				".js("jquery/jeditable")."
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
		<div id='fileQueue_$field'></div>
		<div class='contenedor-boton-upload'>
			<input type='file' name='uploadify_$field' id='uploadify_$field'/>
		</div>";

		$javascript = "
				function load_images_$field() {
					$('#container-files-$field').load('/ajax/$model_name/files/read/$iditem/$field/?tmp_upload=$tmp_upload');
				}

				$(document).ready(function() {
					$('#uploadify_$field').uploadify({
						'uploader'       : '/resources/uploadify/uploadify.swf',
						'script'         : '/ajax/$model_name/files/save/$iditem/$field',
						 'scriptData'  	: { 'tmp_upload' : '$tmp_upload' },
						'cancelImg'      : '/resources/uploadify/cancel.png',
						'folder'         : 'uploads',
						'queueID'        : 'fileQueue_$field',
						'auto'           : true,
						'multi'          : true,
						'fileDataName' 	: '$fileDataName',
						'onComplete' : function () {
							load_images_$field();
						}
					});
					load_images_$field();
				});
		";
		if($this->form) {
            $this->form->addJS($javascript);
		} else {
		    $str .= "<script type='text/javascript'>$javascript</script>";
		}


		return $this->prepend."$str";
	}
}
?>
