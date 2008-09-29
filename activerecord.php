<?

class ActiveRecord {
// implements Iterator {
	protected $database;
	protected $database_table;
//	protected $fields;

	protected $exists = false;
	private $select_columns;
	private $select_conditions;
	private $select_limit;
	private $select_order;
	protected $where_primary_keys;
	public $current_page = 1;
	public $page_size = null;
	public $total_results;
	private $results;
	static $metadata = array();
	protected $row_data;
	protected $row_data_l10n;

	public function __construct() {
		if(!$this->database_table) $this->database_table = strtolower(get_class($this));
		$this->database = web::instance()->database;
		$this->readMetadata();
		$this->setWherePK();
	}

	private function setWherePK() {
		$where = array();
		foreach($this->getPrimaryKeys() as $key) {
			if(isset($this->row_data[$key])){
				$where[]= " {$key} = '{$this->$key}'";
			} else {
				return false;
			}
		}
		$this->where_primary_keys = implode(" AND ", $where);
	}

	public function select($args = '') {
		$args = func_get_args();
		$this->results = call_user_method_array("selectSql", $this, $args);
		$this->dumpValues();
		if(count($this->results) == 0) return array();
		if(isset($args[0]) && is_numeric($args[0])) return $this->results[0];
		return $this->results;
	}

	public function selectFirst($what = null) {
		$params = func_get_args();
		array_push($params, "limit: 1");
		call_user_method_array("select", $this, $params);
		if(count($this->results) == 0) return False;
		return $this->results[0];
	}

	public function count($args = '') {
		$args = func_get_args();
		array_push($args, "columns: count(*) as total");
		$results = call_user_method_array("selectSql", $this, $args);
		return $results[0]->total;
	}

	public function create($values = '') {

		foreach($this->getFields() as $field => $attrs) {
			$this->row_data[$field] = array_key_exists($field, $values) ? $values[$field] : null;
		}
		return $this->save();
	}

	public function lastInsertId() {
		return $this->database->lastInsertId();
	}
	public function update($values) {

	}

	public function save()	{

		if($this->exists()) {
			$insert = false;
			$sql = "update $this->database_table set ";
			$fields_to_update = array();
			foreach($this->getFields() as $name => $attrs) {
				if(is_null($this->row_data[$name])) {
					$fields_to_update[] = $name."=Null";
				} else {
					$fields_to_update[] = $name."='".mysql_real_escape_string($this->row_data[$name])."'";
				}
			}

			$sql .= join(", ", $fields_to_update)." where ".$this->where_primary_keys;
		} else {
			$insert = true;
			$fields = array(); $values = array();
			foreach($this->getFields() as $name => $attrs) {
				if(isset($this->row_data[$name])) {
					$fields[] = $name;
					$values[] = mysql_real_escape_string($this->row_data[$name]);
				}
			}
			$fields = join(", ", $fields);

			$values = "'".join("', '", $values)."'";
			$sql = "INSERT into $this->database_table ($fields) values ($values)";
		}

//		var_dump($sql);

		if(!$this->database->exec($sql) && $this->database->errorCode() > 0 )
			var_dump($sql, $this->database->errorInfo());
		else
			$id =  $this->lastInsertId();

		if(!$insert) $id = $this->row_data['id'];
		$this->savel10n($id);
		return $id;

	}

	protected function savel10n($id) {
		$this->database->exec("DELETE FROM l10n WHERE  model='".get_class($this)."' and row='$id'");
		$sql = "INSERT INTO l10n (lang, model, field, data, row) values ";
		$values = array();
		foreach($this->row_data_l10n as $lang => $rows) {
			foreach($rows as $field => $data) {
				$values[]= "('$lang', '".get_class($this)."', '$field', '$data', '$id')";
			}
		}
		$sql .= implode(",", $values);
		$this->database->exec($sql);
	}

	public function selectSql($args = '') {
		$this->select_columns = $this->select_order = $this->select_limit = $this->select_conditions = '';
		$args = func_get_args();

		if(count($args) > 0 && !in_array(array_shift(explode(":", $args[0])), array("order", "limit", "columns"))) {
			$what = array_shift($args);
		}

		$this->select_columns = join(", ", array_keys($this->getFields()));

		if(isset($what)) {
			if(is_numeric($what)) {
				$primary_key = array_shift($this->getPrimaryKeys());
				$this->select_conditions = $primary_key."='$what' ";
			} else {
				$this->select_conditions = $what;
			}
		}

		foreach($args as $arg) {
			if(strpos($arg, ":")) {
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
				}
			}
		}

		$sql = "SELECT ".$this->select_columns." FROM ".$this->database_table;
		if($this->select_conditions) $sql .= " WHERE ".$this->select_conditions;
		if($this->select_order)	$sql .= " ORDER BY ".$this->select_order;
		if($this->select_limit) $sql .= " LIMIT ".$this->select_limit;

		if($this->page_size) {
			$statement = $this->database->query("SELECT count(*) as total FROM ".$this->database_table.($this->select_conditions ? " WHERE ".$this->select_conditions : " "));
			if($statement) {
				$total_result = $statement->fetch();
				if(!$total_result) {
					echo "Error: ",get_class($this)." -> $sql";
				}
				$this->total_results = $total_result[0];
			}
			$sql .= " LIMIT ".(($this->current_page - 1) * $this->page_size).", ".$this->page_size;
		}
//var_dump($sql);
//		$statement =  $this->database->query($sql, PDO::FETCH_CLASS, get_class($this), array(True));
		$statement =  $this->database->query($sql, PDO::FETCH_ASSOC);

		if(!$statement) {
			echo "Error: ",get_class($this)." -> $sql";
		} else {
			$rows = $statement->fetchAll();
			$results = array();

			$class_name = get_class($this);
			$item = new $class_name();
			if($this->page_size) {
				$item->select_columns = $this->select_columns;
				$item->total_results = $this->total_results;
				$item->current_page = $this->current_page;
				$item->page_size = $this->page_size;
			}

			foreach($rows as $row) {
				$current_item = clone $item;
				$current_item->setPrivateData($row);
				$results[] = $current_item;
			}
			return $results;
		}
	}



	protected function dumpValues() {
//		foreach($this->getFields() as $field => $attrs) {
//			if(isset($this->results[0]->$field)) $this->$field = $this->results[0]->$field;
//		}
		if(isset($this->results[0])) $this->row_data = $this->results[0]->getRowData();
		$this->setWherePK();
	}

	private function readMetadata() {
		if(!array_key_exists($this->database_table, ActiveRecord::$metadata))	{
			if($this->fields) {
				$metadata[$this->database_table]= array("fields" => array(), "primary_keys" => array());
				foreach($this->fields as $name => $attrs) {

					ActiveRecord::$metadata[$this->database_table]["AllFields"][$name] = array();
					$field = &ActiveRecord::$metadata[$this->database_table]["AllFields"][$name];
					if(substr($name, -3) == "_id") {
						$fk_field = ereg_replace('_id$', '', $name);
						if(in_array($fk_field, $this->belongs_to)) {
							$field["belongs_to"] = $fk_field;
						}
					}

					$attrs = eregi_replace("^(integer)", "int", $attrs);
					$attrs = eregi_replace("^(string)", "varchar", $attrs);

					eregi("^(int|varchar|enum|text|decimal|datetime|time|date|bool|image|html|file|files)(\(([^\)]+)\))?", $attrs, $egs);
					eregi("(default)='(.*)'", $attrs, $defaultegs);
					eregi("(label)='([^']*)'", $attrs, $labelregs);
					$field["type"] = $egs[1];
					$field["size"] = $egs[3];
					if($defaultegs[2]) $field["default_value"] = $defaultegs[2];
					$field["label"] = $labelregs[2] ? $labelregs[2] : $name;

					$attrs = explode(" ", $attrs);
					if(in_array("primary_key", $attrs)) {
						ActiveRecord::$metadata[$this->database_table]["primary_keys"][]= $name;
						$field["primary_key"] = true;
					}

					// Set the values of the enum
					if($field['type'] == 'enum') {
						$field['values'] = split_csv($field['size']);
//						$field['values'] = split("\'*\s*,\'", $field['size']);
						unset($field['size']);
					}
					if(in_array("not_null", $attrs)) $field["not null"] =  true;
					if(in_array("l10n", $attrs)) $field["l10n"] =  true;
					if(in_array("html", $attrs)) $field["html"] =  true;
					if(in_array("auto_increment", $attrs) || in_array("autoincrement", $attrs)) $field["autoincrement"] = true;
					if($field['type'] != 'image' && $field['type'] != 'file' && $field['type'] != 'files' )
						ActiveRecord::$metadata[$this->database_table ]["fields"][$name] = &$field;

					unset($defaultegs);
					unset($labelregs);
				}
			}
		}
	}


	public function __get($property) {
		return $this->get($property);
	}


	public function get($property, $selected_lang = null, $return_default_lang_is_not_exists = true) {
		// We can see if is a photo or an file.
		if(array_key_exists($property, $this->getAllFields()) || array_key_exists($property, $this->row_data)) {
		 	$field = $this->getFields($property);
		 	if($field['type'] == 'image') {
		 		$primary_key = array_shift($this->getPrimaryKeys());
				$this->$property = new helpers_images();
				$this->$property = $this->$property->selectFirst("module='".get_class($this)."' and iditem='".$this->row_data[$primary_key]."' and field='$property'");
				$item = $this->$property;
				return $this->$property;
			} elseif($field['type'] == 'file') {
		 		$primary_key = array_shift($this->getPrimaryKeys());
				$this->$property = new helpers_files();
				$this->$property = $this->$property->selectFirst("module='".get_class($this)."' and iditem='".$this->row_data[$primary_key]."' and field='$property'");
				return $this->$property;
			} elseif($field['type'] == 'files') {
		 		$primary_key = array_shift($this->getPrimaryKeys());
				$item = new helpers_images();
				$item = $item->select("module='".get_class($this)."' and iditem='".$this->row_data[$primary_key]."' and field='$property'");
				$this->$property = $item;
				return $item;
//				return $this->$property;
			}


			if($field['l10n'] && web::instance()->l10n->isNotDefault($selected_lang)) {
				if(!$selected_lang) $selected_lang = web::instance()->l10n->getSelectedLang();
				if($this->row_data_l10n[$selected_lang][$property]) return stripslashes($this->row_data_l10n[$selected_lang][$property]);

				$sta = $this->database->query("select data, field from l10n where lang='$selected_lang'
																			and model='".get_class($this)."'
																			and row='".$this->row_data['id']."'");
//																		and field='".$property."'

				// If not exists the value
				if(!$sta || $sta->rowCount() == 0) {
					return $return_default_lang_is_not_exists ? stripslashes($this->row_data[$property]) : null;
				}

				if($rows = $sta->fetchAll()) {
					foreach($rows as $row) {
						$this->set($row['field'], $row['data'], $selected_lang);
					}
					return stripslashes($this->row_data_l10n[$selected_lang][$property]);
				}
			}
			return stripslashes($this->row_data[$property]);
		}
	}


	function __set($property, $value) {

		if(array_key_exists($property, $this->getAllFields()) || array_key_exists($property, $this->row_data)) {
		 	$field = $this->getFields($property);
		 	if($field['type'] == 'image' || $field['type'] == 'file') {
		 		$this->$property = $value;
		 	} else {
				if($field['l10n'] && web::instance()->l10n->isNotDefault()) {
					$this->set($property, $value, web::instance()->l10n->getSelectedLang());
				} else {
					$this->row_data[$property] = $value;
				}
			}
		} else {
			$this->$property = $value;
		}
	}

	public function set($property, $value, $lang = null) {
		if(!$lang)
			$this->row_data[$property] = $value;
		else
			$this->row_data_l10n[$lang][$property] = $value;
	}



	public function getPrimaryKeys() {
		return ActiveRecord::$metadata[$this->database_table]["primary_keys"];
	}

	public function getFields($field = '') {
		if($field) return ActiveRecord::$metadata[$this->database_table]["AllFields"][$field];
		return ActiveRecord::$metadata[$this->database_table]["fields"];
	}

	// With the special type fields "image" and "file".
	public function getAllFields() {
		return ActiveRecord::$metadata[$this->database_table]["AllFields"];
	}

	//TODO: Tenemos que borrar archivos si tiene, fotos si tiene y las tablas relaccionadas.
	protected function deleteItem() {
		$conditions = array();
		$this->setWherePK();
/*
		foreach($this->getFields() as $field => $attrs) {
			if(isset($this->row_data[$field]))	$conditions[] = $field."='".$this->row_data[$field]."'";
		}
*/

//		$sql = "delete from $this->database_table where ".join(" and ", $conditions);
		$sql = "delete from $this->database_table where ".$this->where_primary_keys;
//		var_dump($this);
//		var_dump($sql);
		$this->database->exec($sql);
	}

	public function deleteAll() {
		$this->database->exec("delete from $this->database_table");
	}

	public function delete($args = '') {
		if(func_num_args() > 0) {
			$args = func_get_args();
			$results = call_user_method_array("selectSql", $this, $args);
			foreach($results as $item) {
				$item->deleteItem();
			}
		} else {
			$this->deleteItem();
		}
	}

	public function saveFromRequest() {
		$images = array();
		$files = array();
		foreach($this->getAllFields() as $field => $attrs) {
			if(array_key_exists($field, $_REQUEST) || array_key_exists($field, $_FILES) ) {
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
						$this->set($field, $_REQUEST[$field]);
						foreach(l10n::instance()->getNotDefaultLanguages() as $lang) {
//							echo $field."|$lang";
							if($_REQUEST[$field."|$lang"]) $this->set($field, $_REQUEST[$field."|$lang"], $lang);
						}

				}


			}
		}
		$this->setWherePK();
//		$this->save();
		$this->select($this->save());

		foreach($images as $image) {
			if(!$this->uploadImage($image)) {
				if($_REQUEST['_file_'.$image] != 'no-delete') {
					if($this->$image) {
						$this->$image->delete();
					}
				}
			}
		}

		foreach($files as $file) {
			if(!$this->uploadFile($file)) {
				if($_REQUEST['_file_'.$file] != 'no-delete') {
					if($this->$file) $this->$file->delete();
				}
			}
		}
		return $this;
	}

	public function exists($args = '') {
		$args = func_get_args();

		if(func_num_args() == 0) {
			if(!$this->where_primary_keys) return False;
			$results = call_user_method_array("count", $this, $this->where_primary_keys);
		} else {
			$results = call_user_method_array("count", $this, $args);
		}
		return $results[0];
	}


	protected function __call($method, $args=array()){
		$model = strtolower(ereg_replace("^get", "", $method));

		if(isset($this->belongs_to) && in_array($model, $this->belongs_to)){
			$fk_field = $model."_id";
			$model = new $model();
			return $model->selectFirst($this->$fk_field);
		}

		if(isset($this->has_many) && in_array($model, $this->has_many)){
			$fk_field = $model."_id";
			$model = new $model();
			return $model->select($this->database_table."_id='$this->id'");
		}
	}

	public function setCurrentPage($page = 1) {
		if(is_numeric($page)) $this->current_page = $page;
		return $this;
	}

	public function setPageSize($page_size) {
		$this->page_size = $page_size;
		return $this;
	}

	public function setPrivateData($data) {
		$this->row_data = $data;
		$this->row_data_l10n = null;
	}

	public function getRowData() {
		return $this->row_data;
	}

	public function getSelectedColumns() {
		return $this->select_columns ? split(" ?, ?", $this->select_columns) : array_keys($this->getFields());
	}
}
?>
