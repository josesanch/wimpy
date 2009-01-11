<?

class Database extends PDO
{

	public function __construct($database) {
		parent::__construct($database[0], isset($database[1]) ? $database[1] : null, isset($database[2]) ? $database[2] : null, isset($database[3]) ? $database[3] : null);
	}


	public function tableExists($table) {
//		echo "Table exists: $table<br/>";
		$rs = $this->query("show tables like '$table'");
		if(!$rs) return False;
		$value = $rs->fetch();
		if ($value) return True;
		return False;

	}

	public function sql($sql) {

	}

	public function createTable($table, $fields) {
		if($this->tableExists($table)) return;
		$primary_keys = array();
		$sql_lines = array();
//		echo "sadfasf";
		if($this->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
			$sql = "CREATE TABLE `$table` (";
			foreach($fields as $name => $attrs) {
				$sql_line = "`$name` ".$attrs["type"];
				if(array_key_exists("size", $attrs) && $attrs["size"]) $sql_line .= " (".$attrs["size"].")";
				if(array_key_exists("values", $attrs) && $attrs["values"]) $sql_line .= " ('".join("','", $attrs["values"])."')";
				if(array_key_exists("primary_key", $attrs)) $primary_keys[]= $name;
				if(array_key_exists("not_null", $attrs)) $sql_line .= " NOT NULL ";
				if(array_key_exists("autoincrement", $attrs)) $sql_line .= " auto_increment ";
				if(array_key_exists("default_value", $attrs))	$sql_line .= " default '".$attrs["default_value"]."' ";
				$sql_lines[]= $sql_line;
			}
			$sql .= join(", \n", $sql_lines).", \n";
			$sql .= "PRIMARY KEY(".join(", ", $primary_keys)."))  DEFAULT CHARSET=utf8;";

		}
	//	echo $sql;

		$exec = $this->exec($sql);
		return $exec;
	}
}

?>
