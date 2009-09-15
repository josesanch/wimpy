<?
class html_autoform extends html_form {

	public function __construct($model = null, $css = null) {
		$this->css = $css;

		if(is_object($model)) {
			$this->setModel($model);
			parent::__construct(get_class($model), "/admin/".get_class($model)."/save".web::params());
		} else  {
			parent::__construct(get_class($model));
		}
		$this->construct_head();
	}

	private function construct_head() {
		$this->add("
				<fieldset class='admin_form ".get_class($this->model)."'>
				<legend>".get_class($this->model)."</legend>
			");

		if(!$this->model->id) {
			$tmp_upload = get_class($this->model)."_".rand();
		 	$this->hidden("tmp_upload")->value($tmp_upload);
		}

		foreach($this->model->getAllFields() as $field => $attrs) {
			if($attrs['primary_key']) {
				$id =$this->model->$field;
				$this->hidden($field)->value($this->model->$field);
			} else {
				$this->auto($field, null, $tmp_upload);
				if($this->css) $this->css($this->css);
			}
			$this->add("\n");
		}

		if($this->model->hasImages()) {
			$this->add(new html_form_files("", $this->model, $tmp_upload));
		}
	}

	private function construct_foot() {

		$this->add("
		<script>
				function delete_item(id) {
					if(confirm('Está seguro')) {
						document.location='/admin/".get_class($this->model)."/delete/' + id + '".web::params()."';
					}
				}
				$('#".get_class($this->model)."').validate();
			</script>
			<div class='form-buttons'>
				<input class='submit boton-volver' type='button' value=volver onclick=\"document.location='/admin/".get_class($this->model)."/list".web::params()."'\">
			");

		if($this->model->id)
			$this->add("<input class='submit boton-eliminar' type='button' value=eliminar onclick=\"delete_item('".$this->model->id."');\">");

		$this->add("
				<input class='submit boton-guardar' type='submit' value='guardar'/>
			</div>
			");
		$this->add("</fieldset>");

	}

	public function toHtml() {
		$this->construct_foot();
		return parent::toHtml();
	}

}
?>
