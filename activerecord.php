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
    protected $row_data;
    protected $row_data_l10n;
    protected static $metadata = array();

    private $select_columns;
    private $select_conditions;
    private $select_limit;
    private $select_order;
    private $results;


    public function __construct()
    {
        if (!$this->database_table) $this->database_table = strtolower(get_class($this));
        $this->database = web::database();
        $this->readMetadata();
        $this->setWherePK();
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

                if (is_null($this->row_data[$name]) || ($this->row_data[$name] == "" && in_array($attrs['type'], array('int', 'decimal')))) {
                    $fields_to_update[] = $name."=Null";
                } else {
                    $fields_to_update[] = $name."='".mysql_escape_string($this->row_data[$name])."'";
                }
            }
            $sql .= join(", ", $fields_to_update)." where ".$this->where_primary_keys;
        } else {  			// INSERT
            $insert = true;
            $fields = array(); $values = array();
            foreach ($this->getFields() as $name => $attrs) {
                if (!$this->row_data[$name] && in_array($attrs['type'], array('int', 'decimal'))) continue;
                if (isset($this->row_data[$name])) {
                    $fields[] = $name;
                    $values[] = mysql_escape_string($this->row_data[$name]);
                }
            }
            $fields = join(", ", $fields);

            $values = "'".join("', '", $values)."'";
            $sql = "INSERT into $this->database_table ($fields) values ($values)";

        }
//        log::to_file($sql."<br/><hr>");
//        web::debug(__FILE__, __LINE__, $sql);

        // Execute the query.
        if (!$this->database->exec($sql) && $this->database->errorCode() > 0 )
            var_dump($sql, $this->database->errorInfo());
        else
            $id =  $this->lastInsertId();

        if (!$insert) $id = $this->row_data['id'];
        $this->savel10n($id);
        $this->id = $id;
		$this->setWherePK();
        // Save the changes in the log.
        if (is_a($this, "Model")) log::add(web::auth()->get("user"), $this->getTitle()." [$id] ".($insert ? "CREATED" : "MODIFIED"), log::OK, $sql);

        return $id;

    }

    protected function savel10n($id)
    {
        $this->database->exec("DELETE FROM l10n WHERE  model='".get_class($this)."' and row='$id'");
        $sql = "INSERT INTO l10n (lang, model, field, data, row) values ";
        $values = array();
        foreach ($this->row_data_l10n as $lang => $rows) {
            foreach ($rows as $field => $data) {
                $values[]= "('$lang', '".get_class($this)."', '$field', '".mysql_escape_string($data)."', '".mysql_escape_string($id)."')";
            }
        }
        if ($values) {
            $sql .= implode(",", $values);
            $this->database->exec($sql);
        }
    }

    public function selectSql($args = '')
    {
        $this->select_columns = $this->select_order = $this->select_limit = $this->select_conditions = '';
        $args = func_get_args();
		$table = $this->getDatabaseTable();

        // Toma el primer elemento como where
        if (count($args) > 0 && !in_array(array_shift(explode(":", $args[0])), array("order", "limit", "columns"))) {
            $what = array_shift($args);
        }

        $this->select_columns = join(", $table.", array_keys($this->getFields()));
        if ($this->select_columns) $this->select_columns = "$table.$this->select_columns";

        if (isset($what)) {
            if (is_numeric($what)) {
                $primary_key = array_shift($this->getPrimaryKeys());
                $this->select_conditions = $primary_key."='$what' ";
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
                }
            } elseif($arg == ActiveRecord::NORMAL || $arg == ActiveRecord::INNER) {
				$mode = $arg;
			}
        }

		if ($mode == ActiveRecord::INNER) {
			$joins = $this->_getLeftJoins();
		}

        if ($this->select_conditions) 	$where = " WHERE ".$this->select_conditions;
        if ($this->select_order)    	$order = " ORDER BY ".$this->select_order;
        if ($this->select_limit) 		$limit = " LIMIT ".$this->select_limit;

		$sql = "
			SELECT
				".$this->select_columns."
			FROM
				".$this->database_table."
			$joins
			$where
			$order
			$limit";

		//echo "<hr><pre>$sql</pre>";

        if ($this->page_size) {
            $statement = $this->database->query(
                "SELECT
					count(*) as total
                FROM
					".$this->database_table."
				$joins
				$where"
            );

            if ($statement) {
                $total_result = $statement->fetch();
                if (!$total_result) {
                    echo "Error: ",get_class($this)." -> $sql";
                }
                $this->total_results = $total_result[0];
                $statement->fetchAll();    // Para soportar unbuferred queryes
                $sql .= " LIMIT ".(($this->current_page - 1) * $this->page_size).", ".$this->page_size;
            }
        }


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
        }
    }

	private function _getLeftJoins()
	{
		$joins = array();
		$table = $this->getDatabaseTable();
		$count = 2;
		foreach ($this->getAllFields() as $field => $attrs) {
			if ($attrs["belongs_to"]) {
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
        if (!array_key_exists($this->database_table, ActiveRecord::$metadata)) {
            if ($this->fields) {
                $metadata[$this->database_table]= array("fields" => array(), "primary_keys" => array());
                foreach ($this->fields as $name => $attrs) {
                    if (!$attrs || $attrs == 'separator' || $attrs == '---') continue;

                    ActiveRecord::$metadata[$this->database_table]["AllFields"][$name] = array();
                    $field = &ActiveRecord::$metadata[$this->database_table]["AllFields"][$name];
                    if (substr($name, -3) == "_id") {
                        $fk_field = preg_replace('/_id$/', '', $name);
                        if (in_array($fk_field, $this->belongs_to)) {
                            $field["belongs_to"] = $fk_field;
                        }
                    }

                    $attrs = preg_replace("/^(integer)/i", "int", $attrs);
                    $attrs = preg_replace("/^(string)/i", "varchar", $attrs);
                    preg_match("/^(int|varchar|enum|text|decimal|datetime|time|date|bool|image|html|files|file)(\(([^\)]+)\))?/i", $attrs, $egs);
                    preg_match("/(default)='(.*)'/", $attrs, $defaultegs);
                    preg_match("/(label)='([^']*)'/", $attrs, $labelregs);
		            $field["type"] = $egs[1];
                    $field["size"] = $egs[3];
                    if ($defaultegs[2]) $field["default_value"] = $defaultegs[2];
                    $field["label"] = $labelregs[2] ? ucfirst($labelregs[2]) : ucfirst($name);

                    $attrs = explode(" ", $attrs);
                    if (in_array("primary_key", $attrs)) {
                        ActiveRecord::$metadata[$this->database_table]["primary_keys"][]= $name;
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
                        ActiveRecord::$metadata[$this->database_table ]["fields"][$name] = &$field;

                    unset($defaultegs);
                    unset($labelregs);
                }
            }
        }
    }


    public function __get($property)
    {
        return $this->get($property);
    }


    public function get($property, $selected_lang = null, $return_default_lang_is_not_exists = true)
    {
        // We can see if is a photo or an file.
        if (array_key_exists($property, $this->getAllFields()) || array_key_exists($property, $this->row_data)) {
             $field = $this->getFields($property);

             if ($field['type'] == 'image') {
                $primary_key = array_shift($this->getPrimaryKeys());
                $this->$property = new helpers_images();
                $this->$property = $this->$property->selectFirst("module='".get_class($this)."' and iditem='".$this->row_data[$primary_key]."' and field='$property'", "order: orden");
                $item = $this->$property;
                return $this->$property;

            } elseif ($field['type'] == 'file') {
                $primary_key = array_shift($this->getPrimaryKeys());
                $this->$property = new helpers_files();
                $this->$property = $this->$property->selectFirst("module='".get_class($this)."' and iditem='".$this->row_data[$primary_key]."' and field='$property'", "order: orden");
                return $this->$property;

            } elseif ($field['type'] == 'files') {
                $primary_key = array_shift($this->getPrimaryKeys());
                $item = new helpers_images();
                $item = $item->select("module='".get_class($this)."' and iditem='".$this->row_data[$primary_key]."' and field='$property'", "order: orden");
                $this->$property = $item;
                return $item;
//                return $this->$property;
            }


            if ($field['l10n'] && web::instance()->l10n->isNotDefault($selected_lang)) {
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
            return stripslashes($this->row_data[$property]);
        }
    }


    function __set($property, $value)
    {

        if (array_key_exists($property, $this->getAllFields()) || array_key_exists($property, $this->row_data)) {
             $field = $this->getFields($property);
             if ($field['type'] == 'image' || $field['type'] == 'file') {
                 $this->$property = $value;
             } else {
                if ($field['l10n'] && web::instance()->l10n->isNotDefault()) {
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
        return ActiveRecord::$metadata[$this->database_table]["primary_keys"];
    }

    public function getFields($field = '')
    {
        if ($field) return ActiveRecord::$metadata[$this->database_table]["AllFields"][$field];
        return ActiveRecord::$metadata[$this->database_table]["fields"];
    }

    // With the special type fields "image" and "file".
    public function getAllFields()
    {
        return ActiveRecord::$metadata[$this->database_table]["AllFields"];
    }


    public function deleteAll()
    {
        $this->database->exec("delete from $this->database_table");
    }

    public function delete($args = '')
    {
        if (func_num_args() > 0) {
            $args = func_get_args();
            $results = call_user_method_array("selectSql", $this, $args);
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
        $sql = "delete from $this->database_table where ".$this->where_primary_keys;
//        web::debug(__FILE__, __LINE__, $sql);

        if (is_a($this, "Model")) {
			$this->_deleteAsociatedFiles();
			log::add(web::auth()->get("user"), $this->getTitle()." [$this->id] DELETED", log::WARNING, $sql);
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
                        $this->$field = $fecha[2]."-".$fecha[1].'-'.$fecha[0];
                        break;

                    default:
                        if ($attrs['autocomplete'] && $attrs["newvalues"] && !$_REQUEST[$field] && $_REQUEST[$field."_autocomplete"]) {    // We've to insert the new value in the related table.
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
                if ($_REQUEST['_file_'.$image] != 'no-delete') {
                    if ($this->$image) {
                        $this->$image->delete();
                    }
                }
            }
        }

        foreach ($files as $file) {
            if (!$this->uploadFile($file)) {
                if ($_REQUEST['_file_'.$file] != 'no-delete') {
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

    public function &fields($field) {
        return new fields(&ActiveRecord::$metadata[$this->database_table]["AllFields"][$field], $field, $this->database_table);
    }

    public function getAllFieldsForForm()
    {
        return $this->fields;
    }

	public function forceCreation()
	{
		unset($this->where_primary_keys);
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
                    $this->_attrs[$method] = $args[0];
                break;

			case "getSql":
				if ($this->_attrs["getSql"]) return $this->_attrs["getSql"];

				if ($this->_attrs["show"]) {
					$this->_attrs["getSql"] = $this->_attrs["show"];
				} elseif($relatedTable = $this->_attrs['belongs_to']) {
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
				if ($this->_attrs["getSqlColumn"]) return $this->_attrs["getSqlColumn"];

				if ($this->_attrs["show"]) {
					$this->_attrs["getSqlColumn"] = $this->_attrs["show"]." as $this->_name";
				} elseif ($relatedTable = $this->_attrs['belongs_to']) {
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
                if (empty($args))
                    return $this->_attrs[$method];
                else
                    $this->_attrs[$method] = $args[0];
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
