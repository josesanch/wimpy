<?php

class helpers_model_ajax  {
    protected $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function listItems()
    {
        $limit = $where = $order = "";

        if(isset($_REQUEST["limit"]))
            $limit = "limit: ".$_REQUEST["start"].", ".$_REQUEST["limit"];

        if(isset($_REQUEST["query"]))
            $where =  "nombre like '%".$_REQUEST["query"]."%'";

        if(isset($_REQUEST["sort"]))
            $order = "order: ".$_REQUEST["sort"]." ".$_REQUEST["dir"];

        $count = $this->model->count($where);

        if ($_REQUEST["fields"])
            $columns = "columns: ".$_REQUEST["fields"];

        $data =  $this->model->select($where, $limit, $columns, $order);

        $items = array();
        foreach($data as $item) $items[] = $item->getRowData();
        echo json_encode(array("items" => $items, "count" => $count));
    }


    public function files($action, $field, $id)
    {
        $cond = "";
        switch ($action) {

            case 'read':
                $this->model->select($id);
                $images = new helpers_images();
                $module = web::request("tmp_upload") ? web::request("tmp_upload") : $this->model->image_label;
                if(!web::request("tmp_upload")) $cond = " and iditem='$id' ";

                $data =  $images->select("module='$module' $cond and field='$field' ", "order:orden", "columns: id, nombre, extension, tipo");
                if (web::auth()->hasPermission($this->model, auth::DELETE) || web::auth()->hasPermission($this->model, auth::MODIFY))
                    $sortable= " sortable";
                $str = "
                <h5 class='images'>".count($data)." archivos</h5>
                <ul id='files_$field' class='images-dataview clearfix$sortable'>";
                $filters = new html_template_filters();
                foreach($data as $item) {
                    $str .="
                                <li id='images-$item->id'>
                                    <div>";
                    if ($item->isImage()) {
                        $str .= "<a href='".$item->url()."' class='dataview-image' rel='images-{$module}'><img src='".$item->src("100x80", thumb::CROP)."' title='$item->nombre ".$filters->bytes($item->size())."'/></a>";
                    } else {

                        $str .= "<img src='".$item->src("100x80", thumb::CROP)."' title='$item->nombre ".$filters->bytes($item->size())."'/>";
                    }
                    $editable = "";
                    if (web::auth()->hasPermission($this->model, auth::DELETE) || web::auth()->hasPermission($this->model, auth::MODIFY)) {
                        $str .="			<a href='#' class='images-delete' title='Eliminar archivo' id='$id-".get_class($this->model)."-$field-$item->id-".web::request("tmp_upload")."'><img src='/resources/icons/cross.gif' border='0'/></a>";

                        $editable = "class='editable'";
                    }
                    $str .= "
                                        <a href='".$item->url()."' class='images-download' id='$id-".get_class($this->model)."-$field-$item->id-".web::request("tmp_upload")."' target='_blank' title='Descargar documento'><img src='/resources/admin/images/document-save.png' border='0'/></a>
                                    </div>
                                    <input type='text' $editable id='file-$item->id' value='$item->nombre'/>
                                </li>";
                }
                $str .= "</ul>";
                echo $str;
                exit;
            break;

            case 'save':
                web::log("$action, $id, $field");
                web::log(var_export($_FILES, true));
                if (isset($id)) {
                    $this->model->select($id);
                }
                echo ($this->model->uploadImage($field)) ? "1"  : "0";
                exit;


            case 'destroy':
                $image = new helpers_images();
                $image->delete($id);
                exit;

            case 'update':
                list($t, $id) = explode('-', $_REQUEST['id']);
                web::database()->exec("UPDATE images SET nombre='".$_REQUEST["value"]."' where id=".$id);
                echo $_REQUEST['value'];
                exit;
        }

    }


    public function load($id = null)
    {
        $item = $this->model->select($id);
        $arr = array();
        foreach($this->model->getFields() as $field => $attr) {
            $arr[$field] = $this->model->$field;
        }
#		var_dump($arr);
//		echo  '{ data: '.json_encode($arr).', success: true, "recordCount" : 1}';
//		echo  json_encode(array('success' => 'true',"data" => $arr));
        header('Content-type: text/xml;');
        echo '<?xml version="1.0" encoding="UTF-8"?><response success="true"><data>';
        foreach($this->model->getFields() as $field => $attr) {
            if($attr["type"] == 'text')
                echo "<$field><![CDATA[".$item->$field."]]></$field>";
        }

        echo '</data></response>';

        exit;
    }



    public function autocomplete($valor)
    {

        //if (web::request("term")) $valor = web::request("term");
        //if (!$valor) exit;
        $limit = web::request("limit");
        if (!$limit) $limit = 25;
        if ($limit) $limit = "limit: $limit";
        if(web::request("field")) {
            $attrs = $this->model->getFields(web::request("field"));
            if($attrs["show"]) $name = $attrs["show"];
        }

        if(!$name) $name = $this->model->getTitleField();


        if (web::request("term"))
            $q = strtolower(web::request("term"));
        else
            $q = strtolower($_GET["q"]);

        $primaryKey = array_shift($this->model->getPrimaryKeys());


        $results = $this->model->select(
            "columns: $primaryKey as id, $name as text",
            "where: $name like '%$q%'", "order: $name", $limit
        );

        if (web::request("q")) {
            foreach($results as $row) {
                echo $row->text."|".$row->id."\n";
            }
        } else {
            $json = array();
            foreach($results as $row) {
                $json[]= array("id" => $row->id, "label" => $row->text, "value" => $row->text);
            }

            echo json_encode($json);
        }
        exit;
    }

    public function reorderImages()
    {
        $orden = web::request("orden");
        $images = explode(",", $orden);
        $ids = array();
        $count = 0;
        foreach ($images as $img) {
            $id = array_pop(explode("-", $img));
            web::database()->query("UPDATE images SET orden='".($count++)."' WHERE id='$id'");
        }
    }

    /*
     * Reordenar las listas de administración.
     */

    public function reorderList()
    {
        $orden = web::request("orden");
        $rows = explode(",", $orden);
        $ids = array();

        // Obtenemos el mínimo
        foreach ($rows as $row) {
            $explode = explode('-', $row);
            $ids[]= array_pop($explode);
        }

        $result = web::database()->query("SELECT min(orden) as minimo FROM ".$this->model->getDatabaseTable()." where id in (".implode(",", $ids).")")->fetch();
        $count = $result["minimo"];

        // Ordenamos lo demás
        foreach ($rows as $row) {
            $explode = explode('-', $row);
            $id = array_pop($explode);

            web::database()->query("UPDATE ".$this->model->getDatabaseTable()." SET orden='".($count++)."' WHERE id='$id'");
        }

        exit;

    }

    public function delete() {
        $this->model->delete($_REQUEST["id"]);
    }

    private function parse($request) {
       return (isset($_REQUEST[$request])) ? json_decode(stripslashes($_REQUEST[$request]), true) : null;

    }

    public function getValue($id)
    {
        $attrs = $this->model->getFields(web::request("field"));
        if($attrs["show"]) $name = $attrs["show"];
        if(!$name) $name = $this->model->getTitleField();

        $primaryKey = array_shift($this->model->getPrimaryKeys());

        $data = $this->model->selectFirst(
            "columns: $name as text",
            "where: $primaryKey='$id'"
        );
        /*
        var_dump("columns: $name as text",
            "where: $primaryKey='$id'");
            */
        echo $data->text;
        exit;
    }

    public function getValueDialog($id)
    {

        $attrs = $this->model->getFields(web::request("field"));
        $relatedModelName = $attrs["belongs_to"] ? $attrs["belongs_to"] : $attrs["belongsTo"];
        $relatedModel = new $relatedModelName;

        if($attrs["show"]) $name = $attrs["show"];
        if(!$name) $name = $relatedModel->getTitleField();

        $fieldName = $field = web::request("field");
        if (substr($fieldName, -3) == "_id") $fieldName = substr($fieldName, 0, -3);

        $primaryKey = array_shift($relatedModel->getPrimaryKeys());
        $sql = "select $name as text from $relatedModelName as $fieldName
         where $fieldName.$primaryKey='$id'";
        $data = web::database()->query($sql)->fetch();

        echo $data["text"];
        exit;
    }
}
