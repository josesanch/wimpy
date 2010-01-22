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
    //'return do_search(this);';
    private $_instance = true;

    public function toHtml($model, $sql = null, $columns = null, $order = null)
    {
        $modelName = get_class($model);
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


        if (web::request("search")) {
            if ($columns) $c = split(" ?, ?", $columns);
            else $c = array_keys($model->getFields());
            $search = array();
            foreach ($c as $field) {
                $search[]= "$field like '%".urldecode(web::request('search'))."%'";
            }
            $search = " (".join(" or ", $search).")";
            $sql = $sql ? $sql." and ".$search : $search;
        }

        $columns = $columns ? split(" ?, ?", $columns) :  array_keys($model->getFields());
        $sqlcolumns = array();

        foreach ($columns as $column) {
            $attrs = $model->getFields($column);
            if($attrs['belongs_to']) {
                $belongs_model_name = $attrs['belongs_to'];
                $belongs_model = new $belongs_model_name;
                $table = $belongs_model_name;
                $fieldToSelect = !$attrs["show"] ?
                    $belongs_model->getTitleField() :
                    $attrs["show"];

                $sqlcolumns[]= "
                    (
                        SELECT $fieldToSelect
                        FROM
                            $table secondary_table_$table
                        WHERE
                            secondary_table_$table.id=".
                            $model->getDatabaseTable().".$column
                    ) as $column";
            } else {
                $sqlcolumns[] = $column;
            }
        }
        // We add the primary key to the seleted fields.
        $primaryKey = array_shift($model->getPrimaryKeys());
        if(!in_array($primaryKey, $sqlcolumns)) $sqlcolumns[]=$primaryKey;

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
                $order = "order: ".$model->field_used_for_ordenation;
                $ordenation = true;
            } else {
                $order = "order: id";
            }
        }

		if (web::request("page"))
            $model->setCurrentPage(web::request("page"));

		// Hacemos la consulta al modelo
        $results = $model->select(
			$sql,
			"columns: ".implode(", ", array_filter($sqlcolumns)),
			$order
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


            if($botonNuevo && web::auth()->hasPermission($model, auth::ADD))
                $formData .=
                    "<input type=button value='nuevo' class='boton-nuevo'
                    onclick='openUrl(\"/admin/".get_class($model)."/edit/0".
                    web::params()."\")'>";

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
                "<tr class='grid_row row_".($i++ % 2 == 0 ? 'even' : 'odd')."' $trEvent>
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
                $formData .= "<td class=grid_cell>$value</td>\n";
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
                mouseOverResults();
                function openUrl(url) {
                    $openUrl
                }";

        if($ordenation) {
                $formData .=
                    "$(document).ready(function () {
                        $('#table_body').sortable({
                            containment: 'parent',
                            axis:	'y',
                            update:
                                function(e, ui) {
                                    prev = ui.item.prev().children('.value').html();
                                    id = ui.item.children('.value').html()
                                    $('#mensajes').html('Realizando cambios').load('/ajax/".get_class($model)."/reorderList/' + id + '/' + prev);
                                }
                        });
                    });";
        }

        $formData .=
            "</script>
            <div id='mensajes'
            style='display: none; border: 1px solid gray; padding: 1em;'>
            </div>";


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
