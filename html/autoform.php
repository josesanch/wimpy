<?php
class html_autoform extends html_form
{
    public $buttons = array("back", "delete", "save");

    public function __construct($model = null, $css = null)
    {
        $this->css = $css;

        if (is_object($model)) {
            $this->setModel($model);
            parent::__construct(
                get_class($model),
                "/admin/".get_class($model)."/save".web::params()
            );
        } else {
            parent::__construct(get_class($model));
        }
        $this->construct_head();
    }

    private function construct_head()
    {
        $this->add(
            "   <fieldset class='admin_form ".get_class($this->model)."'>
                <legend>".$this->model->getTitle()."</legend>"
        );

        if (!$this->model->id) {
            $tmpUpload = get_class($this->model)."_".rand();
             $this->hidden("tmp_upload")->value($tmpUpload);
        }

        foreach ($this->model->getAllFieldsForForm() as $field => $type) {
            if ($type == 'separator' || $type == '---') {
                $this->add("<h2>$field</h2>");
                continue;
            }

            $attrs = $this->model->getFields($field);

            if ($attrs['primary_key']) {
                $id =$this->model->$field;
                $this->hidden($field)->value($this->model->$field);
            } else {
                $this->auto($field, null, $tmpUpload);
                if ($this->css) $this->css($this->css);
                if($attrs["newline"]) $this->add("<div class='newline'></div>");
            }
            $this->add("\n");
        }

        if ($this->model->hasImages()) {
            $this->add(new html_form_files("", $this->model, $tmpUpload));
        }
    }

    private function construct_foot()
    {
    	$isDialog = web::request("dialog");
    	$modelName = get_class($this->model);
		if ($isDialog) {
			$openUrl = "$('#".$modelName."_dialog').load(url);";
			$ajaxForm = "$('#$modelName').ajaxForm({ target: '#{$modelName}_dialog' })";
		} else {
			$openUrl = "document.location = url;";
		}

        $this->addJS(
                "function delete_item(id) {
                    if (confirm('Está seguro')) {
                        document.location='/admin/".
            get_class($this->model)."/delete/' + id + '".web::params()."';
                    }
                }
                $('#".get_class($this->model)."').validate();

				function openUrl(url) {
					$openUrl
				}
				$ajaxForm"
		);
		$this->add("<div class='form-buttons'>");

        if (in_array("back", $this->buttons)) {
            $this->add(
                "<input class='submit boton-volver' id='boton-volver'
                type='button' value=volver
                onclick=\"openUrl('/admin/".
                get_class($this->model)."/list".web::params()."');\">"
            );
        }

        if ($this->model->id && in_array("delete", $this->buttons)) {
            $this->add(
                "<input class='submit boton-eliminar' id='boton-eliminar'
                type='button' value=eliminar
                onclick=\"delete_item('".$this->model->id."');\">"
            );

        }

        if (in_array("save", $this->buttons)) {
            $this->add(
                "<input class='submit boton-guardar'
                type='submit' id='boton-guardar' value='guardar'/>"
            );
        }

        $this->add($this->_endData);
        $this->add("</div></fieldset>");
    }

    public function toHtml()
    {
        $this->construct_foot();
        return parent::toHtml();
    }

}
