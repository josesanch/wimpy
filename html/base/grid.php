<?
class html_base_grid extends html_object
{
    public $onClick;
    public $redirectTo;
    public $buttons = array("search", "new");
    public $search;
    public $showSearch = true;
    public $onSubmit;
    public $pageSize = 25;
    public $onDelete;

    //'return do_search(this);';
    private $_instance = true;

    public function toHtml($model, $sql = null, $columns = null, $order = null)
    {
        $modelName = get_class($model);
        $table = $model->getDatabaseTable();
        $fields = array_keys($model->getFields());

        $dialog = web::request("dialog") ? "dialog" : "";
        $form = new html_base_form($modelName);
        $form->method("get");


        if ($this->_instance) {
            if($this->onSubmit) $form->onsubmit($this->onSubmit);
			$model->setPageSize($this->pageSize);
        } else {
            //$form->onsubmit('return do_search(this);');
            $model->setPageSize(25);
        }
		// Seleccionamos la página

		// Hacemos el or de los campos para ponerlo en el where
        if (web::request("search")) {
            if ($columns) $c = preg_split("/\s*,\s*/", $columns);
            else $c = array_keys($model->getFields());
            $search = array();

            foreach ($c as $field) {
				$search[]= $model->fields($field)->getSql()." like '%".urldecode(web::request('search'))."%'";
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

        if($order) $order = "order: $order";
        if(web::request("order")) {
            $order = "order: ".web::request("order");
            $desc = web::request("desc") == 'true' ? "false" : "true";
            if(web::request("desc") == 'true') $order .= " desc";
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

		if (web::request("page"))
            $model->setCurrentPage(web::request("page"));
		//echo "<pre>$sql</pre>";
		//var_dump($sqlcolumns);
		// Hacemos la consulta al modelo
        $results = $model->select(
			$sql,
			"columns: ".implode(", ", array_filter($sqlcolumns)),
			$order,
			ActiveRecord::INNER
		);


        $de = ($model->current_page - 1) * $model->page_size + 1;
        $hasta = $de + $model->page_size - 1;
		$hasta =  $hasta > $model->total_results ? $model->total_results : $hasta;
        if($de > $hasta) $de = $hasta;
        $paginas = array(__("Mostrando")." $de a $hasta de ".$model->total_results);
        $paginacion = html_base_grid::_getPaginate($results);

        if($paginacion) $paginas[]= $paginacion;

        $paginas = implode("&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;", $paginas);

        if($this->_instance) {
            $botonNuevo = in_array("new", $this->buttons);
            $botonBuscar = in_array("search", $this->buttons);
            $data = $this->search;
        } else {
            $botonNuevo = $botonBuscar = true;
        }

        if(!$this->_instance || $this->showSearch) {
            $formData =
                    "<div class='listado-resultados'>Buscar:
                        <input type='text' name='search'
                        class='texto-buscar' id='search'
                        value='".urldecode(web::request('search'))."'
                        size=20/>";

            if($botonBuscar && web::auth()->hasPermission($model, auth::VIEW))
                $formData .=
                    "<input type=submit value=buscar class='boton-buscar'/>";

            if($botonNuevo && web::auth()->hasPermission($model, auth::ADD)) {
				if (!$dialog)
					$formData .= "<input type=button value='nuevo' class='boton-nuevo' onclick='goUrl(\"/admin/".get_class($model)."/edit/0".web::params()."\")'>";
				else
					$formData .= "<input type=button value='nuevo' class='boton-nuevo' onclick='goUrl(\"/admin/".get_class($model)."/edit/0".web::params()."\", \"".web::request("field")."\",\"".web::request("parent")."\")'>";
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

        foreach($columns as $column) {
            if(web::request('order') == $column) {
                $arrow = web::request('desc') == 'true' ? "&uarr;" : "&darr;";
            } else {
                $arrow = '';
            }
            $attrs = $model->getFields($column);
            $label = $attrs['label'] ? $attrs['label'] : $column;
            $formData .= "
				<th class=grid_header>
					<a href='".web::uri("/order=$column/desc=$desc")."' class='header $dialog'>$label</a>
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
                $urlFunction = "updateModelValueDialog".
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

				switch ($attrs["type"]) {
					case "date":
						if($value != "0000-00-00")
							$value = format::date($value);
						else
							$value = "";
					break;
				}
                $formData .= "
                <td class=grid_cell>$value</td>";
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

		$form->action(web::uri(null, null, array("page")));
		if (!$this->_instance || $this->showSearch) {
			$form->add($formData);
			return $form->toHtml();
		}
		return $formData;
    }

	private static function _getPaginate($results)
	{

		if (web::request("dialog")) {
            $arr = helpers_paginate::toArray($results);
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
			$paginas = helpers_paginate::toHtml($results);
		}
		return $paginas;
	}

}
