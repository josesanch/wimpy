<?php

class log extends Model {
    public $title = "Log";

    protected $fields = array(
                            "id" => "int primary_key auto_increment",
                            "user" => "varchar(25)",
                            "ip" => "varchar(25)",
                            "status" => "varchar(15)",
                            "message" => "varchar(255)",
                            "modified" => "datetime",
                            'query' => 'text'
                            );
    public $grid_columns = "id, user, ip, status, message, modified";

    const OK = "OK";
    const WARNING = "WARNING";
    const ERROR = "ERROR";

    public static function add($user, $message, $status = log::OK, $sql = '') {
        if (!web::instance()->logging) return;
        if(!web::database()->tableExists("log")) {
            web::database()->query("CREATE TABLE  `log` (
                              `id` int(11) NOT NULL AUTO_INCREMENT,
                              `user` varchar(25) COLLATE utf8_bin DEFAULT NULL,
                              `ip` varchar(25) COLLATE utf8_bin DEFAULT NULL,
                              `status` varchar(15) COLLATE utf8_bin DEFAULT NULL,
                              `message` varchar(255) COLLATE utf8_bin DEFAULT NULL,
                              `modified` datetime NOT NULL,
                              `query` text,
                              PRIMARY KEY (`id`)
                            ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
        }
        web::database()->query("insert into log (user, ip, status, message, modified, query)
                            values ('$user', '".$_SERVER['REMOTE_ADDR']."', '$status', '$message', NOW(), ".web::instance()->database->quote($sql).")");
    }

    public function adminList() {
        $instance = new log();
        return "<br/>". $this->listItems($instance, null, $instance->grid_columns, "modified desc");
    }


    private function listItems($model, $sql = null, $columns = null, $order = null) {
        $form = new html_base_form(get_class($model));
        $form->onsubmit('return do_search(this);');

        $model->setPageSize(25);
        if(web::request("page")) $model->setCurrentPage(web::request("page"));

        if(web::request("search")) {
            if($columns) $c = array_map(trim, explode(",", $columns));
            else $c = array_keys($model->getFields());
            $search = array();
            foreach($c as $field) {
                $search[]= "$field like '%".urldecode(web::request('search'))."%'";
            }
            $search = " (".join(" or ", $search).")";
            $sql = $sql ? $sql." and ".$search : $search;
        }

        $columns = $columns ? preg_split(" ?, ?", $columns) :  array_keys($model->getFields());
        $sqlcolumns = array();
        foreach($columns as $column) {
            $attrs = $model->getFields($column);
            if($attrs['belongs_to']) {

                $belongs_model_name = $attrs['belongs_to'];
                $table = substr($column, 0, -3);
                $belongs_model = new $belongs_model_name;
                $sqlcolumns[]= "(select ".$belongs_model->getTitleField()." from $table secondary_table_$table where secondary_table_$table.id=".$model->getDatabaseTable().".$column) as $column";
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

        $paginas = array(__("Mostrando")." $de a $hasta de ".$model->total_results);
        $paginacion = helpers_paginate::toHtml($results);
        if($paginacion) $paginas[]= $paginacion;

        $paginas = implode("&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;", $paginas);
        $form->add("
                <div class='listado-resultados'>
                <div>
                    <table border=0 width='100%' cellpadding=0 cellspacing='0'>
                        <td>
                            Buscar: <input type='text' name='search' class='texto-buscar' value='".urldecode(web::request('search'))."' size=20>
                            <input type=button value=buscar class='boton-buscar'  onclick=\"do_search(this.form)\";>
                            <input type=button value='nuevo' class='boton-nuevo' onclick='document.location=\"/admin/".get_class($model)."/edit/0".web::params()."\"'>

                        <td align='right'>$paginas</td>
                    </table>
                </div>
                </div>


        ");

        $form->add("\n<table border=0 class='grid' cellpadding=3 cellspacing=1 align=center width='98%'>
                        <tr >\n");

        foreach($columns as $column) {
            if(web::request('order') == $column) {
                $arrow = web::request('desc') == 'true' ? "&uarr;" : "&darr;";
            } else {
                $arrow = '';
            }
            $attrs = $model->getFields($column);
            $label = $attrs['label'] ? $attrs['label'] : $column;
            $form->add("	<th class='grid_header'><a href='".web::uri("/order=$column/desc=$desc")."' class='header'>$label</a> $arrow</th>\n");
        }

        $i = 0;

        $form->add("<tbody id='table_body'>\n");

        foreach($results as $row) {
            $form->add(
                "<tr class='grid_row ".$row->get("status")." row_".($i++ % 2 == 0 ? 'even' : 'odd')."' onclick='document.location=\"/admin/".get_class($row)."/edit".web::params("/".$row->get("id"))."\"'>
                    <td class='value' style='display: none;'>".$row->get("id")."</td>
                ");



            foreach($columns as $column) {
                $form->add("	<td class=grid_cell><a href='/admin/".get_class($row)."/edit".web::params("/".$row->get("id"))."'>".$row->get($column)."</a></td>\n");
            }

            $form->add("</tr>\n");
        }
        $form->add("</tbody></table>\n");

        $form->add("<div class='pie-listado-resultados'><div>".helpers_paginate::toHtml($results)."</div></div><br>
        <script>
            var background = '';
            $('.grid_row').bind('mouseover', function() {

                $(this).addClass('grid_row_hover');

            });
            $('.grid_row').bind('mouseout', function() {
                $(this).removeClass('grid_row_hover');
            });

            function do_search(form) {
                document.location='".web::uri("/page=/search=")."' + form.search.value;
                return false;
            }");

        $form->add("
            </script>
            <div id='mensajes' style='display: none; border: 1px solid gray; padding: 1em;'></div>
            <style type='text/css'>
                .grid_row.ERROR td { background: #CC3333; }
                .grid_row.ERROR td a {color: white; }
                .grid_row.WARNING td { background: #ffd18c; }

            </style>
        ");
        return $form->toHtml();

    }
}
