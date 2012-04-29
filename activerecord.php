<?php

class ActiveRecord
{
    const NORMAL = "SQL_NORMAL";
    const INNER = "SQL_INNER";
    public $current_page = 1;
    public $page_size = null;
    public $total_results;

    protected $database;
    protected $database_table;
    protected $exists = false;
    protected $where_primary_keys;
    protected $row_data = array();
    protected $row_data_l10n;
    protected static $metadata = array();

    private $select_columns;
    private $select_conditions;
    private $select_limit;
    private $select_order;
    private $results;
    private $select_joins;

    public function __construct()
    {
        if (!$this->database_table) $this->database_table = strtolower(get_class($this));
        if (!$this->database) $this->setDatabase(web::database());
        $this->readMetadata();
        $this->setWherePK();
    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    private function setWherePK()
    {
        $where = array();
        foreach ($this->getPrimaryKeys() as $key) {
            if (isset($this->row_data[$key])){
                $where[]= " {$key} = '{$this->$key}'";
                    } else {
                return false;
                    }
        }
        $this->where_primary_keys = implode(" AND ", $where);
    }

    public function select($args = '')
    {
        $args = func_get_args();
        $this->results = call_user_func_array(array($this, "selectSql"), $args);
        $this->dumpValues();
        if (count($this->results) == 0) return array();
        foreach ($this->results as $result) $result->exists = true;
        if (isset($args[0]) && is_numeric($args[0])) return $this->results[0];
        return $this->results;
    }

    public function sql($args)
    {
        return new Database_Select($this->database_table, $args);
    }

    public function selectFirst($what = null)
    {
        $params = func_get_args();
        array_push($params, "limit: 1");
        call_user_func_array(array($this, "select"), $params);
        if (count($this->results) == 0) return False;
        $this->results[0]->setWherePK();
        return $this->results[0];
    }

    public function count($args = '')
    {
        $args = func_get_args();
        array_push($args, "columns: count(*) as total");
        $results = call_user_func_array(
            array($this, "selectSql"),
            $args
        );
        return $results[0]->total;
    }

    public function create($values = '')
    {

        foreach ($this->getFields() as $field => $attrs) {
            $this->row_data[$field] = array_key_exists($field, $values) ? $values[$field] : null;
        }
        return $this->save();
    }

    public function lastInsertId()
    {
        return $this->database->lastInsertId();
    }

    public function save()
    {
        if ($this->exists()) {    // UPDATE
            $insert = false;
            $sql = "UPDATE $this->database_table SET ";
            $fields_to_update = array();
            foreach ($this->getFields() as $name => $attrs) {
                if (
                    (!array_key_exists($name, $this->row_data)
                     || (array_key_exists($name, $this->row_data) && is_null($this->row_data[$name]))
                     || (array_key_exists($name, $this->row_data) && $this->row_data[$name] == ""))
                    && in_array($attrs['type'], array('int', 'decimal', 'bool'))) {
                    $fields_to_update[] = $name."=Null";
                } else {
                    $fields_to_update[] = $name."=".$this->database->quote($this->row_data[$name]);
                }
            }
            $sql .= join(", ", $fields_to_update)." where ".$this->where_primary_keys;
        } else {            // INSERT
            $insert = true;
            $fields = array(); $values = array();
            foreach ($this->getFields() as $name => $attrs) {

                if ((!array_key_exists($name, $this->row_data)
                     || !$this->row_data[$name])
                    && in_array($attrs['type'], array('int', 'decimal'))) continue;

                if (array_key_exists($name, $this->row_data)
                    && isset($this->row_data[$name])) {
                    $fields[] = $name;
                    $values[] = $this->database->quote($this->row_data[$name]);
                }
            }
            $fields = join(", ", $fields);

            $values = join(", ", $values);
            $sql = "INSERT into $this->database_table ($fields) values ($values)";

        }
        //var_dump($sql);
        //        exit;
        //        orderontime::debug($sql, true));
        //        log::to_file($sql."<br/><hr>");
        //        web::debug(__FILE__, __LINE__, $sql);

        // Execute the query.
        if (!$this->database->exec($sql) && $this->database->errorCode() > 0 ) {
            var_dump($sql, $this->database->errorInfo());
        } else {
            $id =  $this->lastInsertId();

            if (!$insert) $id = $this->row_data['id'];
            $this->savel10n($id);
            $this->id = $id;
            $this->setWherePK();
            // Save the changes in the log.
            if (is_a($this, "Model"))
                log::add(web::auth()->get("user"), $this->getTitle()." [$id] ".($insert ? "CREATED" : "MODIFIED"), log::OK, $sql);

            return $id;
        }


    }

    protected function savel10n($id)
    {
        $this->database->exec("DELETE FROM l10n WHERE  model='".get_class($this)."' and row='$id'");
        $sql = "INSERT INTO l10n (lang, model, field, data, row) values ";
        $values = array();
        if (isset($this->row_data_l10n)) {
            foreach ($this->row_data_l10n as $lang => $rows) {
                foreach ($rows as $field => $data) {
                    $values[]= "('$lang', '".get_class($this)."', '$field', ".$this->database->quote($data).", ".$this->database->quote($id).")";
                }
            }
        }
        if ($values) {
            $sql .= implode(",", $values);
            $this->database->exec($sql);
        }
    }

    public function selectSql($args = '')
    {
        $where = $order = $limit = $group = $joins = "";
        $mode = ActiveRecord::NORMAL;
        $order = $joins = "";
        $this->select_columns = $this->select_order = $this->select_limit = $this->select_conditions = '';
        $args = func_get_args();
        $table = $this->getDatabaseTable();

        // Toma el primer elemento como where
        $explodeArgs = explode(":", $args[0]);
        if (count($args) > 0 && !in_array(array_shift($explodeArgs), array("order", "limit", "columns"))) {
            $what = array_shift($args);
        }


        $this->select_columns = join(", $table.", array_keys($this->getFields()));
        if ($this->select_columns) $this->select_columns = "$table.$this->select_columns";

        if (isset($what)) {
            if (is_numeric($what)) {
                $primaryKeys = $this->getPrimaryKeys();
                $primary_key = array_shift($primaryKeys);
                $this->select_conditions = $table.".".$primary_key."='$what' ";
            } else {
                $this->select_conditions = $what;
            }
        }

        foreach ($args as $arg) {
            if (strpos($arg, ":")) {
                list($command, $arguments) = explode(":", $arg);
                switch ($command) {
                    case 'order':
                        $this->select_order = $arguments;
                        break;

                    case 'limit':
                        $this->select_limit = $arguments;
                        break;

                    case 'columns':
                        $this->select_columns = $arguments;
                        break;

                    case 'where':
                        $this->select_conditions = $arguments;
                        break;

                    case 'group':
                        $this->select_group = $arguments;
                        break;

                    case 'joins':
                        $this->select_joins = $arguments;
                        break;
                }
            } elseif($arg == ActiveRecord::NORMAL || $arg == ActiveRecord::INNER) {
                $mode = $arg;
            }
        }


        if ($mode == ActiveRecord::INNER) {
            $joins .= $this->_getLeftJoins();
        }

        if ($this->select_conditions)   $where = " WHERE ".$this->select_conditions;
        if ($this->select_order)        $order = " ORDER BY ".$this->select_order;
        if ($this->select_group)        $group = " GROUP BY ".$this->select_group;
        if ($this->select_limit)        $limit = " LIMIT ".$this->select_limit;
        if ($this->select_joins)        $joins .= " ".$this->select_joins;
        $sql = "
            SELECT
                ".$this->select_columns."
            FROM
                ".$this->database_table."
            $joins
            $where
            $order
            $group
           ";

        if ($this->page_size) {
            $sqlPaginacion = "
            select count(*) as total from (
             $sql $limit
            ) as table2";

            $statement = $this->database->query($sqlPaginacion);

            if ($statement) {
                $total_result = $statement->fetch();
                if (!$total_result) {
                    echo "Error: ",get_class($this)." -> $sql";
                }
                $this->total_results = $total_result[0];
                $statement->fetchAll();    // Para soportar unbuferred queryes
                // Si son varias páginas hacemos el límite
                if ($this->total_results > $this->page_size)
                    $limit = " LIMIT ".(($this->current_page - 1) * $this->page_size).", ".$this->page_size;
            }
        }

        $sql .= $limit;
        //        echo "<pre>".$sql."</pre><hr/>";

        $statement =  $this->database->query($sql, PDO::FETCH_ASSOC);

        if ($statement) {
            $rows = $statement->fetchAll();
            $results = array();

            $class_name = get_class($this);
            $item = new $class_name();
            if ($this->page_size) {
                $item->select_columns = $this->select_columns;
                $item->total_results = $this->total_results;
                $item->current_page = $this->current_page;
                $item->page_size = $this->page_size;
            }

            foreach ($rows as $row) {
                $current_item = clone $item;
                $current_item->setPrivateData($row);
                $results[] = $current_item;
            }
            return $results;
        } else {

        }

    }
    /*
      public function join($type = "INNER", $table, $conditions) {
      $this->select_joins[]= array($type, $table, $conditions);
      return this;
      }
    */

    private function _getLeftJoins()
    {
        $joins = array();
        $table = $this->getDatabaseTable();
        $count = 2;
        foreach ($this->getAllFields() as $field => $attrs) {
            if (isset($attrs["belongs_to"])) {
                $relatedTable = $attrs["belongs_to"];
                $fieldName = $field;
                if (substr($fieldName, -3) == "_id") $fieldName = substr($fieldName, 0, -3);
                if ($fieldName == $table) $fieldName.= $count++;
                $joins[]= "
                    LEFT OUTER JOIN $relatedTable $fieldName ON
                        $fieldName.id=$table.$field
                ";
            }
        }
        return implode("\n", $joins);
    }

    private function _alterColumns($data)
    {

        if (!$data) return $data;
        var_dump($data);
        $fields = implode("|", array_keys($this->getAllFields()));
        $table = $this->getDatabaseTable();
        $data = preg_replace("/(^|[\(\s,=])(?<!as )($fields)([\s,=\)]|$)/i", '$1'.$table.'.$2'.'$3', $data);
        return $data;
    }

    protected function dumpValues()
    {
        //        foreach ($this->getFields() as $field => $attrs) {
        //            if (isset($this->results[0]->$field)) $this->$field = $this->results[0]->$field;
        //        }
        if (isset($this->results[0])) $this->row_data = $this->results[0]->getRowData();
        $this->setWherePK();
    }

    private function readMetadata()
    {
        if (!array_key_exists(
                $this->database_table,
                ActiveRecord::$metadata[$this->database->uri])
        ) {
            if ($this->fields) {
                $metadata = &$this->getMetadata();
                $metadata = array("fields" => array(), "primary_keys" => array());

                foreach ($this->fields as $name => $attrs) {
                    if (!$attrs || $attrs == 'separator' || substr($attrs, 0, 3) == '---') continue;

                    $metadata["AllFields"][$name] = array();
                    $field = &$metadata["AllFields"][$name];
                    if (substr($name, -3) == "_id") {
                        $fk_field = preg_replace('/_id$/', '', $name);
                        if (in_array($fk_field, $this->belongs_to)) {
                            $field["belongs_to"] = $fk_field;
                        }
                    }

                    $attrs = preg_replace("/^(integer)/i", "int", $attrs);
                    $attrs = preg_replace("/^(string)/i", "varchar", $attrs);
                    preg_match("/^(int|varchar|enum|text|decimal|float|datetime|time|date|bool|image|html|files|file)(\(([^\)]+)\))?/i", $attrs, $egs);
                    preg_match("/(default)='(.*)'/", $attrs, $defaultegs);
                    preg_match("/(label)='([^']*)'/", $attrs, $labelregs);
                    if (array_key_exists(1, $egs)) $field["type"] = $egs[1];
                    if (array_key_exists(3, $egs)) $field["size"] = $egs[3];

                    if (array_key_exists(2, $defaultegs)) $field["default_value"] = $defaultegs[2];

                    $field["label"] = array_key_exists(2, $labelregs) ? ucfirst($labelregs[2]) : ucfirst($name);

                    $attrs = explode(" ", $attrs);
                    if (in_array("primary_key", $attrs)) {
                        $metadata["primary_keys"][]= $name;
                        $field["primary_key"] = true;
                    }

                    // Set the values of the enum
                    if ($field['type'] == 'enum') {
                        $field['values'] = split_csv($field['size']);
                        //                        $field['values'] = split("\'*\s*,\'", $field['size']);
                        unset($field['size']);
                    }
                    if (in_array("not_null", $attrs)) $field["not null"] =  true;
                    if (in_array("l10n", $attrs)) $field["l10n"] =  true;
                    if (in_array("html", $attrs)) $field["html"] =  true;
                    if (in_array("auto_increment", $attrs) || in_array("autoincrement", $attrs)) $field["autoincrement"] = true;
                    if (in_array("autocomplete", $attrs) || in_array("autocomplete", $attrs)) $field["autocomplete"] = true;
                    if (in_array("dialog", $attrs) || in_array("dialog", $attrs)) $field["dialog"] = true;
                    if (in_array("newvalues", $attrs) || in_array("newvalues", $attrs)) $field["newvalues"] = true;
                    if (in_array("newline", $attrs) || in_array("newline", $attrs)) $field["newline"] = true;
                    if (in_array("hidden", $attrs) || in_array("hidden", $attrs)) $field["hidden"] = true;
                    if ($field['type'] != 'image' && $field['type'] != 'file' && $field['type'] != 'files' )
                        $metadata["fields"][$name] = &$field;

                    unset($defaultegs);
                    unset($labelregs);
                }
            }
        }
    }


    public function __get($property)
    {
        if ($this->__isset($property))
            return $this->get($property);
    }

    public function __isset($name)
    {
        if (array_key_exists($name, $this->getAllFields()) ||
            array_key_exists($name, $this->row_data)) {
            return true;
        }
        return false;
    }

    public function get($property, $selected_lang = null, $return_default_lang_is_not_exists = true)
    {
        // We can see if is a photo or an file.
        if (array_key_exists($property, $this->getAllFields()) || array_key_exists($property, $this->row_data)) {
            $idItem = null;
            $field      = $this->getFields($property);
            $primaryKey = $this->getFirstPrimaryKey();
            if (array_key_exists($primaryKey, $this->row_data)) {
                $idItem     = $this->row_data[$primaryKey];
            }
            $moduleName = get_class($this);
            $fieldName  = $property;

            // Si el field es una archivo o imagenes.
            switch ($field["type"]) {
                case "image":
                    $images = new helpers_images();
                case "file":
                    if (!$images) $images = new helpers_files();
                    $this->$fieldName = $images->getFirstFor($moduleName, $fieldName, $idItem);
                    return $this->$fieldName;
                    break;

                case "files":
                    $images = new helpers_images();
                    $imgs = $images->getAllFor($moduleName, $fieldName, $idItem);
                    $this->$fieldName = $imgs;
                    return $imgs;
                    break;
            }


            if (isset($field)
                && array_key_exists("l10n", $field)
                && $field['l10n']
                && web::instance()->l10n->isNotDefault($selected_lang)
            ) {
                if (!$selected_lang)
                    $selected_lang = web::instance()->l10n->getSelectedLang();

                if ($this->row_data_l10n[$selected_lang][$property])
                    return stripslashes($this->row_data_l10n[$selected_lang][$property]);

                $sta = $this->database->query("
                    SELECT
                        data, field
                    FROM
                        l10n
                    WHERE
                        lang='$selected_lang'
                        AND model='".get_class($this)."'
                        AND row='".$this->row_data['id']."'"
                );

                //                                                                        and field='".$property."'

                // If not exists the value
                if (!$sta || $sta->rowCount() == 0) {
                    return $return_default_lang_is_not_exists ? stripslashes($this->row_data[$property]) : null;
                }

                if ($rows = $sta->fetchAll()) {
                    foreach ($rows as $row) {
                        $this->set($row['field'], $row['data'], $selected_lang);
                    }
                    return stripslashes($this->row_data_l10n[$selected_lang][$property]);
                }
            }
            if (array_key_exists($property, $this->row_data)) {
                return stripslashes($this->row_data[$property]);
            }
        }
    }


    function __set($property, $value)
    {

        if (array_key_exists($property, $this->getAllFields()) || array_key_exists($property, $this->row_data)) {
            $field = $this->getFields($property);
            if ($field['type'] == 'image' || $field['type'] == 'file') {
                $this->$property = $value;
            } else {
                if (array_key_exists("l10n", $field)
                    && $field['l10n']
                    && web::instance()->l10n->isNotDefault()) {
                    $this->set(
                        $property,
                        $value,
                        web::instance()->l10n->getSelectedLang()
                    );
                } else {
                    $this->row_data[$property] = $value;
                }
            }
        } else {
            $this->$property = $value;
        }
    }

    public function set($property, $value, $lang = null)
    {
        if (!$lang)
            $this->row_data[$property] = $value;
        else
            $this->row_data_l10n[$lang][$property] = $value;
    }



    public function getPrimaryKeys()
    {
        $metadata = &$this->getMetadata();
        return isset($metadata["primary_keys"]) ? $metadata["primary_keys"] : array();
    }
    public function getFirstPrimaryKey()
    {
        $metadata = $this->getMetadata();
        $pass1 = array_filter($metadata["fields"], create_function('$a', 'return array_key_exists("primary_key", $a) && $a["primary_key"];'));
        $pass2 = array_keys($pass1);
        $primaryKey = array_shift($pass2);

        return $primaryKey;
    }

    public function getFields($field = '')
    {
        $metadata = &$this->getMetadata();
        if ($field) return isset($metadata["AllFields"][$field]) ? $metadata["AllFields"][$field] : null;
        return $metadata["fields"];
    }

    // With the special type fields "image" and "file".
    public function getAllFields()
    {
        $metadata = &$this->getMetadata();
        if (isset($metadata["AllFields"]) && is_array($metadata["AllFields"])) return $metadata["AllFields"];
        return array();
    }


    public function deleteAll()
    {
        $this->database->exec("delete from ".$this->getDatabaseTable());
    }

    public function delete($args = '')
    {
        if (func_num_args() > 0) {
            $args = func_get_args();

            $results = call_user_func_array(array($this, "selectSql"), $args);
            foreach ($results as $item) {
                $item->deleteItem();
            }
        } else {
            $this->deleteItem();
        }
    }

    //TODO: Tenemos que borrar archivos si tiene, fotos si tiene y las tablas relaccionadas.
    protected function deleteItem()
    {
        $conditions = array();
        $this->setWherePK();
        $sql = "delete from ".$this->getDatabaseTable()." where ".$this->where_primary_keys;
        //        web::debug(__FILE__, __LINE__, $sql);

        if (is_a($this, "Model")) {
            $this->_deleteAsociatedFiles();
            log::add(
                web::auth()->get("user"),
                $this->getTitle()." [$this->id] DELETED",
                log::WARNING,
                $sql
            );
        }
        $this->database->exec($sql);
    }

    public function saveFromRequest()
    {
        $images = array();
        $files = array();

        foreach ($this->getAllFields() as $field => $attrs) {
            if (array_key_exists($field, $_REQUEST) || array_key_exists($field, $_FILES) ) {
                switch($attrs['type']) {

                    case 'image':
                        $images[]= $field;
                        break;

                    case "file":
                        $files[] = $field;
                        break;

                    case 'date':
                        $fecha = explode('/', $_REQUEST[$field]);
                        $this->$field = $fecha[2].'-'.$fecha[1].'-'.$fecha[0];

                        break;

                    default:
                        if (array_key_exists("autocomplete", $attrs)
                            && array_key_exists("newvalues", $attrs)
                            && !$_REQUEST[$field]
                            && $_REQUEST[$field."_autocomplete"]) {    // We've to insert the new value in the related table.
                            $model_name = $attrs["belongs_to"];
                            $model = new $model_name;
                            $name = $model->getTitleField();
                            $primary_key = array_shift($model->getPrimaryKeys());
                            $model->$name = $_REQUEST[$field."_autocomplete"];
                            $model->save();
                            $this->set($field, $model->$primary_key);
                        } else {
                            $this->set($field, $_REQUEST[$field]);
                        }
                        foreach (l10n::instance()->getNotDefaultLanguages() as $lang) {
                            if ($_REQUEST[$field."|$lang"]) $this->set($field, $_REQUEST[$field."|$lang"], $lang);
                        }

                }

            }
        }
        $this->setWherePK();
        //        $this->save();
        $this->select($this->save());
        foreach ($images as $image) {
            if (!$this->uploadImage($image)) {
                if (!array_key_exists('_file_'.$image, $_REQUEST) || $_REQUEST['_file_'.$image] != 'no-delete') {
                    if ($this->$image) {
                        $this->$image->delete();
                    }
                }
            }
        }

        foreach ($files as $file) {
            if (!$this->uploadFile($file)) {
                if (!array_key_exists('_file_'.$file, $_REQUEST) || $_REQUEST['_file_'.$file] != 'no-delete') {
                    if ($this->$file) $this->$file->delete();
                }
            }
        }
        return $this;
    }

    public function exists($args = '')
    {
        $args = func_get_args();
        if (func_num_args() == 0) {
            if ($this->exists) return true;
            if (!$this->where_primary_keys) return False;
            $results = call_user_func_array(
                array($this, "count"),
                array($this->where_primary_keys)
            );

        } else {
            $results = call_user_func_array(
                array($this, "count"),
                $args
            );
        }
        return $results[0];
            }

    
        public function __call($method, $args=array())
        {
            $model = strtolower(preg_replace("/^get/", "", $method));

            if (isset($this->belongs_to) && in_array($model, $this->belongs_to)) {
                $fk_field = $model."_id";
                $model = new $model($this->$fk_field);

                return $model;
                //return $model->selectFirst($this->$fk_field);
            }

            if (isset($this->has_many) && in_array($model, $this->has_many)){
                $fk_field = $model."_id";
                $model = new $model();
                return $model->select($this->database_table."_id='$this->id'");
            }

            if (!web::instance()->isInProduction() || web::instance()->inDevelopment) {
                web::error('No existe el método:'.$method);
                throw new Exception("No existe el método: $method en la clase ".get_class($this));
                //            echo
            }
        }

        public function setCurrentPage($page = 1)
        {
            if (is_numeric($page)) $this->current_page = $page;
            return $this;
        }

        public function setPageSize($page_size)
        {
            $this->page_size = $page_size;
            return $this;
        }

        public function setPrivateData($data)
        {
            $this->row_data = $data;
            $this->row_data_l10n = null;
        }

        public function getRowData() {
            return $this->row_data;
        }

        public function getSelectedColumns()
        {
            return $this->select_columns ? split(" ?, ?", $this->select_columns) : array_keys($this->getFields());
        }

        public function getDatabaseTable()
        {
            return $this->database_table;
        }

        public function fields($field) {
            return new fields(
                ActiveRecord::$metadata[$this->database->uri][$this->database_table]["AllFields"][$field],
                $field,
                $this->database_table
            );
        }

        public function getAllFieldsForForm()
        {
            return $this->fields;
        }

        public function toArray($fields)
        {
            $datos = array();
            if ($fields) {
                foreach ($fields as $field)
                    $datos[$field] = $this->get($field);
            } else {
                foreach ($this->getFields() as $field => $attrs)
                    $datos[$field] = $this->get($field);
            }

            return $datos;
        }

        public function toJson($fields)
        {
            return json_encode($this->toArray($fields));
        }

        public function forceCreation()
        {
            unset($this->where_primary_keys);
        }

        public function &getMetadata()
        {
            if (!array_key_exists($this->database->uri, ActiveRecord::$metadata)) {
                ActiveRecord::$metadata[$this->database->uri] = array();
            }

            if (!array_key_exists($this->getDatabaseTable(), ActiveRecord::$metadata[$this->database->uri])) {
                ActiveRecord::$metadata[$this->database->uri][$this->getDatabaseTable()] = array(
                    "created" => false
                );
            }

            $metadata = &ActiveRecord::$metadata[$this->database->uri][$this->getDatabaseTable()];
            return $metadata;
        }

}

class fields
{
    protected $_attrs;
    private $_table;
    private $_name;

    public function __construct(&$attrs, $name, $table)
    {
        $this->_attrs = &$attrs;
        $this->_name = $name;
        $this->_table = $table;
    }

    public function __call($method, $args)
    {
        switch($method) {
            case "belongsTo":
                if (empty($args)) {
                    return $this->_attrs[$method] ? $this->_attrs[$method] : $this->_attrs["belongs_to"];
                } else {
                    $this->_attrs[$method] = $args[0];
                    $this->_attrs["belongs_to"] = $args[0];
                }
                break;

            case 'required':
                if (empty($args))
                    $this->_attrs["not null"] = true;
                else
                    $this->_attrs["not null"] = $args[0];
                break;

            case "getSql":
                if (array_key_exists('getSql', $this->_attrs) && $this->_attrs["getSql"]) return $this->_attrs["getSql"];

                if (array_key_exists('show', $this->_attrs) && $this->_attrs["show"]) {
                    $this->_attrs["getSql"] = $this->_attrs["show"];
                } elseif(array_key_exists('belongs_to', $this->_attrs) && $relatedTable = $this->_attrs['belongs_to']) {
                    $relatedModel = new $relatedTable;
                    $fieldToSelect = $relatedModel->getTitleField();

                    $field = $this->_name;
                    if (substr($field, -3) == "_id") $field = substr($field, 0, -3);
                    $this->_attrs["getSql"] = "$field.$fieldToSelect";
                } elseif($this->_attrs) {
                    $this->_attrs["getSql"] = $this->_table.'.'.$this->_name;
                } else {
                    $this->_attrs["getSql"] = $this->_name;

                }
                return $this->_attrs["getSql"];


            case "getSqlColumn":
                if (isset($this->_attrs["getSqlColumn"])) return $this->_attrs["getSqlColumn"];

                if (array_key_exists('belongs_to', $this->_attrs) && isset($this->_attrs['belongs_to'])) $relatedTable = $this->_attrs['belongs_to'];

                if (isset($this->_attrs["show"]) && $this->_attrs["show"]) {
                    $this->_attrs["getSqlColumn"] = $this->_attrs["show"]." as $this->_name";
                } elseif (isset($relatedTable)) {
                    $relatedModel = new $relatedTable;
                    $fieldToSelect = $relatedModel->getTitleField();

                    $field = $this->_name;
                    if (substr($field, -3) == "_id") $field = substr($field, 0, -3);
                    if ($field == $this->_table) $field.="2";
                    $this->_attrs["getSqlColumn"] = "$field.$fieldToSelect as $this->_name";
                } elseif($this->_attrs) {
                    $this->_attrs["getSqlColumn"] = $this->_table.'.'.$this->_name;
                } else {
                    $this->_attrs["getSqlColumn"] = $this->_name;
                }
                return $this->_attrs["getSqlColumn"];

            default:
                if (empty($args)) {
                    return $this->_attrs[$method];
                } else {
                    $this->_attrs[$method] = $args[0];
                }
        }

        return $this;
    }

    public function label($label)
    {
        if ($label) {
            $this->_attrs["label"] = $label;
            return $this;
        } else {
            return $this->_attrs["label"] ?  $this->_attrs["label"] : $this->_name;
        }

    }
}
