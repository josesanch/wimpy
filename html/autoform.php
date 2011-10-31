<?php
class html_autoform extends html_form
{
    public $buttons = array("back" => "Volver", "delete" => "Eliminar", "save" => "Guardar");

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
            "
				<div id='alert-messages'></div>
				<fieldset class='admin_form ".get_class($this->model)."'>
                <legend>".$this->model->getTitle()."</legend>"
        );

        if (!$this->model->id) {
            $tmpUpload = get_class($this->model)."_".rand();
             $this->hidden("tmp_upload")->value($tmpUpload);
        }

        foreach ($this->model->getAllFieldsForForm() as $field => $type) {
			$attrs = $this->model->getFields($field);
			$this->auto($field, null, $tmpUpload, $type);
			if ($this->css) $this->css($this->css);
			if($attrs["newline"]) $this->add("<div class='newline'></div>");
            $this->add("\n");
        }

        if ($this->model->hasImages()) {
            $this->add(new html_form_files("", $this->model, $tmpUpload));
        }
    }

    private function construct_foot()
    {
        if(web::auth()->hasPermission($this->model, auth::MODIFY))
    	$isDialog = web::request("dialog");
    	$modelName = get_class($this->model);
		$parent = web::request("parent");
		$field = web::request("field");
        $this->addJS("Wimpy.init('$modelName', '$parent', '$field');", true);
        if ($this->openDiv) {
            $this->add("</div></div>");
        }
		$this->add("<div class='form-buttons'>");
		foreach ($this->buttons as $button => $value) {
            if (is_numeric($button)) {
                $button = $value;
                $value = null;
            }
			$this->add($this->_getButton($button, $value));
		}

        $this->add($this->_endData);
        $this->add("
			</div>
        </fieldset>"
        );
    }

    public function getButton($type, $value = null)
    {
        return $this->_getButton($type, $value);
    }

    private function _getButton($type, $value = null)
	{
		$isDialog = web::request("dialog");
		switch ($type) {

			case "back":
                $value = $value ? $value : "volver";
				if(web::request("redir"))
					$urlBack = web::request("redir");
				else
					$urlBack = "/admin/".get_class($this->model)."/list".
								web::params(null, false);

				if ($isDialog) {
					$back = "goUrl('$urlBack','".web::request("field")."', '$modelName');";
				} else {
					$back = "goUrl('$urlBack');";
				}
                return "<input class='submit boton-volver' id='boton-volver' type='button' value='$value' onclick=\"$back\">";

			case "delete":
                $value = $value ? $value : "eliminar";
				if ($this->model->id && web::auth()->hasPermission($this->model, auth::DELETE)) {
					$urlDelete = "/admin/".get_class($this->model)."/delete".web::params();

					if ($isDialog) {
						$delete = "confirmGoUrl('$urlDelete','".web::request("field")."', '$modelName');";
					} else {
						$delete = "confirmGoUrl('$urlDelete');";
					}
					return "<input class='submit boton-eliminar' id='boton-eliminar' type='button' value='$value' onclick=\"$delete\">";
				}
				break;

			case "save":
                $value = $value ? $value : "guardar";
				if (
					(web::auth()->hasPermission($this->model, auth::ADD) && !$this->model->id)
						||
					(web::auth()->hasPermission($this->model, auth::MODIFY) && $this->model->id)
				) {
				  return "\n<input class='submit boton-guardar' type='submit' id='boton-guardar' value='$value'/>";
				}
				break;
			default:
				return $type;
		}
	}

    public function toHtml()
    {
        $this->construct_foot();
        return parent::toHtml();
    }

}
