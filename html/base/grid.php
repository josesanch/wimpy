<?
class html_base_grid extends html_object
{
    public $onClick;
    public $redirectTo;
    public $buttons = array("search" => "buscar", "new" => "Nuevo");
    public $search;
    public $showSearch = true;
    public $onSubmit;
    public $pageSize;
	public $columnsForSearch = null;
    public $onDelete;

    //'return do_search(this);';
    private $_instance = true;

    public function toHtml($model, $sql = null, $columns = null, $order = null)
    {

        $modelName 	= get_class($model);
        $orderField = "order-$modelName";
        $descField 	= "desc-$modelName";
        $searchField = "search-$modelName";
        $pageField 	= "page-$modelName";

        $table 	= $model->getDatabaseTable();
        $fields = array_keys($model->getFields());

        $dialog = web::request("dialog") ? "dialog" : "";
        $form = new html_base_form($modelName);
        $form->method("get");


        if ($this->_instance) {
            if($this->onSubmit) $form->onsubmit($this->onSubmit);
			$model->setPageSize($this->pageSize ? $this->pageSize : (web::instance()->gridSize ? web::instance()->gridSize : 25));
        } else {
            //$form->onsubmit('return do_search(this);');
			$model->setPageSize(web::instance()->gridSize ? web::instance()->gridSize : 25);
        }
		// Seleccionamos la página

		// Hacemos el or de los campos para ponerlo en el where
        if (web::request($searchField)) {
			if ($this->columnsForSearch) $c = preg_split("/\s*,\s*/", $this->columnsForSearch);
			elseif ($columns) $c = preg_split("/\s*,\s*/", $columns);
            else $c = array_keys($model->getFields());

            $search = array();

            foreach ($c as $field) {
				$search[]= $model->fields($field)->getSql()." like '%".urldecode(web::request($searchField))."%'";
            }

            $search = " (".join(" or ", $search).")";
            $sql = $sql ? $sql." and ".$search : $search;
        }

        $columns = $columns ? preg_split("/\s*,\s*/", $columns) : $fields;
        $sqlcolumns = array();

        foreach ($columns as $column) {
			$sqlcolumns[]= $model->fields($column)->getSqlColumn();
        }


        // We add the primary key to the seleted fields.
        $primaryKey = array_shift($model->getPrimaryKeys());

        if($primaryKey && !in_array($primaryKey, $sqlcolumns)) $sqlcolumns[]= "$table.$primaryKey";

        if ($order) $order = "order: $order";
        if (web::request($orderField)) {
            $order = "order: ".web::request($orderField);
            $desc = web::request($descField) == 'true' ? "false" : "true";
            if (web::request($descField) == 'true') $order .= " desc";
        } else {
            $desc = "false";
        }

		// Tenemos encuenta la ordenación seleccionada.
        if (!$order) {
            if($model->getFields($model->field_used_for_ordenation)) {
                $order = "order: $table.".$model->field_used_for_ordenation;
                $ordenation = true;
            } else {
                $order = "order: $table.id";
            }
        }

		if (web::request($pageField))
            $model->setCurrentPage(web::request($pageField));
		//echo "<pre>$sql</pre>";
		//var_dump($sqlcolumns);
		// Hacemos la consulta al modelo
        $results = $model->select(
			$sql,
			"columns: ".implode(", ", array_filter($sqlcolumns)),
			$order,
			ActiveRecord::INNER
		);

		// La página es una página fuera del ámbito del resultado.
		if (((web::request($pageField) - 1) * $model->page_size) >= $model->total_results) {
			$model->setCurrentPage(1);
			$results = $model->select(
				$sql,
				"columns: ".implode(", ", array_filter($sqlcolumns)),
				$order,
				ActiveRecord::INNER
			);
		}


        $de = ($model->current_page - 1) * $model->page_size + 1;
        $hasta = $de + $model->page_size - 1;
		$hasta =  $hasta > $model->total_results ? $model->total_results : $hasta;
        if($de > $hasta) $de = $hasta;
        $paginas = array(__("Mostrando")." $de a $hasta de ".$model->total_results);
        $paginacion = html_base_grid::_getPaginate($results, $pageField);

        if($paginacion) $paginas[]= $paginacion;

        $paginas = implode("&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;", $paginas);

        if($this->_instance) {
            $buttons = array();
            foreach ($this->buttons as $item => $value) {
                if (is_numeric($item)) {
                    $buttons[$value] = $value == "new" ? "nuevo" : "buscar";
                } else {
                    $buttons[$item] = $value;
                }
            }
            $data = $this->search;
        } else {
            $grid = new html_base_grid();
            $buttons = $grid->buttons;
        }

        if(!$this->_instance || $this->showSearch) {
            $formData =
                    "<div class='listado-resultados'>Buscar:
                        <input type='text' name='$searchField'
                        class='texto-buscar' id='search'
                        value='".urldecode(web::request($searchField))."'
                        size=20/>";

            if(array_key_exists("search", $buttons) && web::auth()->hasPermission($model, auth::VIEW))
                $formData .=
                    "<input type=submit value='$buttons[search]' class='boton-buscar'/>";

            if(array_key_exists("new", $buttons) && web::auth()->hasPermission($model, auth::ADD)) {
				if (!$dialog)
					$formData .= "<input type=button value='$buttons[new]' class='boton-nuevo' onclick='goUrl(\"/admin/".get_class($model)."/edit/0".web::params()."\")'>";
				else
					$formData .= "<input type=button value='$buttons[new]' class='boton-nuevo' onclick='goUrl(\"/admin/".get_class($model)."/edit/0".web::params()."\", \"".web::request("field")."\",\"".web::request("parent")."\")'>";
			}

			$formData .=
                "$data
                 <div id='listado-paginas'>$paginas</div>
                 </div>";
        }

        $formData .=
            "<table border=0 class='grid' cellpadding=3 cellspacing=1
            align=center width='98%'>
            <tr >\n";

        foreach ($columns as $column) {
            if (web::request($orderField) == $column) {
                $arrow = web::request($descField) == 'true' ? "&uarr;" : "&darr;";
            } else {
                $arrow = '';
            }
            $attrs = $model->getFields($column);
            $label = $attrs['label'] ? $attrs['label'] : $column;
            $formData .= "
				<th class=grid_header>
					<a href='".web::uri("/$orderField=$column/$descField=$desc")."' class='header $dialog'>$label</a>
					$arrow
				</th>\n";
        }
        if ($this->onDelete) $formData .= "<th class=grid_header></th>";

        $i = 0;

        $formData .= "<tbody id='table_body'>\n";

        foreach ($results as $row) {
            if ($this->onClick) {

                $urlFunction = $this->onClick."\"".
                    $row->get("id").
                    "\",\"".($row->get($row->getTitleField()))."\")";

                $url = "javascript:$urlFunction";
                $trEvent = "onclick='$urlFunction'";
            } elseif($dialog) {
                $urlFunction = "Dialog.click".
                    "(\"$modelName\",\"".web::request("field").
                    "\",\"".web::request("parent").
                    "\",\"".$row->get("id").
                    "\")";

                $url = "javascript:$urlFunction";
                $trEvent = "onclick='$urlFunction'";
            } else {
                $url = "/admin/".get_class($row)."/edit".web::params("/".$row->get("id"));

                if($this->redirectTo) $url .= "/?redir=".$this->redirectTo;

                $trEvent = "onclick=openUrl('$url')";
            }

            $formData .=
                "<tr class='grid_row row_".($i++ % 2 == 0 ? 'even' : 'odd')."' $trEvent id='id-".$row->get("id")."'>
                    <td class='value' style='display: none;'>
                    ".$row->get("id")."
                    </td>";

			// Ponemos los valores de las columnas
            foreach ($columns as $column) {
				$attrs = $row->getFields($column);
				$value = $row->get($column);
				$style = "";
				switch ($attrs["type"]) {
					case "bool":
						if($value == 1) {
							$value = '<img src="/resources/admin/images/check.png"/>';
							$style = "align='center'";
						} else {
							$value = "";
						}
					break;
					case "date":
						if($value != "0000-00-00")
							$value = format::date($value);
						else
							$value = "";
					break;
				}
				if ($attrs["money"]) $value = number_format($value, 0, ',', '.');

                $formData .= "
                <td class='grid_cell'$style>$value</td>";
            }

            // Si hemos especificado un callback de borrado
			if ($this->onDelete) {
				$urlFunction = $this->onDelete."\"".
                    $row->get("id").
                    "\",\"".($row->get($row->getTitleField()))."\")";

                $url = "javascript:$urlFunction";

				$formData .= "
				<td onClick='$urlFunction;event.cancelBubble=true;' style='font-size: 0.8em; color: gray; text-align: center;'>
					<img src='/resources/icons/delete.gif'/>&nbsp;Eliminar
				</td>";
			}
            $formData .= "</tr>\n";
        }
        $formData .= "</tbody></table>\n";

        if (web::request("dialog")) {
            $bindLinks =  "$('a.dialog').bind('click', function() { openUrl($(this).attr('href')); return false; });";
            $openUrl = "$('#".web::request("field")."_dialog').load(url);";
            $ajaxForm = "$('#".get_class($model)."').ajaxForm({ target: '#".web::request("field")."_dialog' })";
        } else {
            $openUrl = "document.location = url;";
        }

        $formData .=
            "<div class='pie-listado-resultados'>
				<div>$paginacion</div>
            </div>
            <br/>
             <script>
                $bindLinks
                $ajaxForm
                function openUrl(url) {
                    $openUrl
                }";


		$formData .= "GridResults.init('$modelName', ".($ordenation ? "true" : "false").");</script>";

		$form->action(web::uri(null, null, array($searchField)));
		if (!$this->_instance || $this->showSearch) {
			$form->add($formData);
			return $form->toHtml();
		}
		return $formData;
    }

	private static function _getPaginate($results, $pageField)
	{

		if (web::request("dialog")) {
            $arr = helpers_paginate::toArray($results, 10, $pageField);
            $paginas = "";
            foreach ($arr as $pagina) {
				switch ($pagina[0]) {
					case 'prev';
						$paginas .= "\n<a href=javascript:openUrl('$pagina[1]')>&lsaquo;&lsaquo;</a>";
					break;
					case 'next':
						$paginas .= "\n<a href=javascript:openUrl('$pagina[1]')>&rsaquo;&rsaquo;</a>";
					break;

					default :
						if ($pagina[2] == "selected")
							$paginas .= "\n$pagina[0]";
						else
							$paginas .= "\n<a href=javascript:openUrl('$pagina[1]')>$pagina[0]</a>";
					break;
				}
			}
		} else {
			$paginas = helpers_paginate::toHtml($results, array(),  __("Páginas").": ", 10, "&nbsp;", $pageField);
		}
		return $paginas;
	}

}
