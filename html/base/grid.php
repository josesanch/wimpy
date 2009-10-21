<?
class html_base_grid extends html_object
{

    public $onClick;
    public $buttons = array("search", "new");
    public $search;
    private $_instance = true;

    public function toHtml($model, $sql = null, $columns = null, $order = null)
    {
        $modelName = get_class($model);
        $dialog = web::request("dialog") ? "dialog" : "";
        $form = new html_base_form($modelName);
        $form->onsubmit('return do_search(this);');

        $model->setPageSize(25);
        if(web::request("page")) $model->setCurrentPage(web::request("page"));

        if(web::request("search")) {
            if($columns) $c = split(" ?, ?", $columns);
            else $c = array_keys($model->getFields());
            $search = array();
            foreach($c as $field) {
                $search[]= "$field like '%".urldecode(web::request('search'))."%'";
            }
            $search = " (".join(" or ", $search).")";
            $sql = $sql ? $sql." and ".$search : $search;
        }

        $columns = $columns ? split(" ?, ?", $columns) :  array_keys($model->getFields());
        $sqlcolumns = array();
        foreach($columns as $column) {
            $attrs = $model->getFields($column);
            if($attrs['belongs_to']) {

                $belongs_model_name = $attrs['belongs_to'];
                $table = substr($column, 0, -3);
                $belongs_model = new $belongs_model_name;
                $sqlcolumns[]= "(select ".$belongs_model->getTitleField().
                    " from $table secondary_table_$table where
                    secondary_table_$table.id=".
                    $model->getDatabaseTable().".$column) as $column";
            } else {
                $sqlcolumns[] = $column;
            }

        }
        if($order) $order = "order: $order";
        if(web::request("order")) {
            $order = "order: ".web::request("order");
            $desc = web::request("desc") == 'true' ? "false" : "true";
            if(web::request("desc") == 'true') $order .= " desc";
        } else {
            $desc = "false";
        }


        if(!$order) {
            if($model->getFields($model->field_used_for_ordenation)) {
                $order = "order: ".$model->field_used_for_ordenation;
                $ordenation = true;
            } else {
                $order = "order: id";
            }
        }
        $results = $model->select($sql, "columns: ".join(", ", $sqlcolumns), $order);
        $de = ($model->current_page - 1) * $model->page_size + 1;
        $hasta = $de + $model->page_size - 1;
        $hasta =  $hasta > $model->total_results ? $model->total_results : $hasta;
        if($de > $hasta) $de = $hasta;
        $paginas = array(__("Mostrando")." $de a $hasta de ".$model->total_results);
        $paginacion = helpers_paginate::toHtml($results);
        if($paginacion) $paginas[]= $paginacion;

        $paginas = implode("&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;", $paginas);

        if($this->_instance) {
            $botonNuevo = in_array("new", $this->buttons);
            $botonBuscar = in_array("search", $this->buttons);
            $data = $this->search;
        } else {
            $botonNuevo = $botonBuscar = true;
        }

        $form->add(
                "<div class='listado-resultados'>
                <div>
                    <table border=0 width='100%' cellpadding=0 cellspacing='0'>
                        <td>
                            Buscar:
                            <input type='text' name='search' class='texto-buscar' id='search' value='".urldecode(web::request('search'))."' size=20/>"
        );


        if($botonBuscar)
            $form->add("<input type=button value=buscar class='boton-buscar' onclick=\"do_search(this.form)\">");

        if($botonNuevo)
            $form->add(
                "<input type=button value='nuevo' class='boton-nuevo'
                onclick='openUrl(\"/admin/".get_class($model)."/edit/0".web::params()."\")'>"
            );

        $form->add(
            "$data
                 <td align='right'>$paginas</td>
                    </table>
                </div>
                </div>
                <table border=0 class='grid' cellpadding=3 cellspacing=1
                align=center width='98%'>
                <tr >\n"
        );

        foreach($columns as $column) {
            if(web::request('order') == $column) {
                $arrow = web::request('desc') == 'true' ? "&uarr;" : "&darr;";
            } else {
                $arrow = '';
            }
            $attrs = $model->getFields($column);
            $label = $attrs['label'] ? $attrs['label'] : $column;
            $form->add(
                "<th class=grid_header>
                <a href='".web::uri("/order=$column/desc=$desc")."'
                class='header $dialog'>
                $label</a> $arrow</th>\n"
            );
        }

        $i = 0;

        $form->add("<tbody id='table_body'>\n");

        foreach($results as $row) {
            if($this->onClick) {
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
                    "\",\"".($row->get($row->getTitleField()))."\")";

                $url = "javascript:$urlFunction";
                $trEvent = "onclick='$urlFunction'";
            } else {
                $url = "/admin/".get_class($row)."/edit".web::params("/".$row->get("id"));
                $trEvent = "onclick=openUrl('$url')";
            }
            $form->add(
                "<tr class='grid_row row_".($i++ % 2 == 0 ? 'even' : 'odd')."' $trEvent>
                    <td class='value' style='display: none;'>
                    ".$row->get("id")."
                    </td>"
                );


//href='$url'
            foreach ($columns as $column) {
                $form->add("
                    <td class=grid_cell>
                        ".
                        $row->get($column)."
                    </td>\n"
                );

            }

            $form->add("</tr>\n");
        }
        $form->add("</tbody></table>\n");
        if (web::request("dialog")) {
            $bindLinks =  "$('a.dialog').bind('click', function() { openUrl($(this).attr('href')); return false; });";
            $openUrl = "$('#".$modelName."_dialog').load(url);";
        } else {
            $openUrl = "document.location = url;";
        }

        $form->add(
            "<div class='pie-listado-resultados'>
            <div>".helpers_paginate::toHtml($results)."</div></div><br>
             <script>
                var background = '';
                $('.grid_row').bind('mouseover', function() {
                    $(this).addClass('grid_row_hover');
                });

                $('.grid_row').bind('mouseout', function() {
                    $(this).removeClass('grid_row_hover');
                });


                function do_search(form) {
                    openUrl('".web::uri("/page=/search=")."' + form.search.value);
                    return false;
                }
                $bindLinks

                function openUrl(url) {
                    $openUrl
                }"
            );

        if($ordenation) {
                $form->add(
                    "$(document).ready(function () {
                        $('#table_body').sortable({
                            containment:    'parent',
                            axis:                   'y',
                            update:
                                function(e, ui) {
                                    prev = ui.item.prev().children('.value').html();
                                    id = ui.item.children('.value').html()
                                    $('#mensajes').html('Realizando cambios').load('/ajax/".get_class($model)."/reorderList/' + id + '/' + prev);
                                }
                        });

                    });"
                );
        }
        $form->add(
            "</script>
            <div id='mensajes'
            style='display: none; border: 1px solid gray; padding: 1em;'>
            </div>"
        );

        return $form->toHtml();

    }
}
