<?
class Model extends ActiveRecord {
/*	protected  $fields = array(
								"id" => "integer(11) not_null primary_key auto_increment",
								"nombre" => "string(255) not_null default=''",
								"idtipo" => "integer",
								"texto" => "text",
								"vendido" => "enum('si','no')",
								"visto" => "bool");
	public $grid_columns = "id, nombre, tipo_productos_id, marcas_id";
	public $belongs_to = array('tipo_productos', 'marcas');
	public $has_images = true;

*/
	public $field_used_for_ordenation = "orden";
	protected $image_label;
	protected $has_files = False;
	protected $has_images = False;
	protected static $table_created = false;

	public function __construct($createdFromSql = False)
	{
		parent::__construct();
		$this->createTableIfNecessary();
		if(!$this->image_label) $this->image_label = get_class($this);
		if(is_numeric($createdFromSql)) {
			$this->select($createdFromSql);
		} else {
			if($createdFromSql) $this->exists = True;
		}
	}

	private function createTableIfNecessary() {
		if(!ActiveRecord::$metadata['created_tables'][$this->getDatabaseTable()]) {
			web::instance()->database->createTable($this->getDatabaseTable(), ActiveRecord::$metadata	[$this->getDatabaseTable()]["fields"]);
			ActiveRecord::$metadata['created_tables'][$this->getDatabaseTable()] = true;
		}
	}

	public function __get($item) {
		if($this->has_files && $item == "files") {
			$primary_key = array_shift($this->getPrimaryKeys());
			$files = new helpers_files();
			return $files->select("module='".$this->image_label."' and iditem='".$this->$primary_key."'", "order:orden");
		} elseif ($this->has_images && $item == "images") {
			$primary_key = array_shift($this->getPrimaryKeys());
			$images = new helpers_images();
			return $images->select("module='".$this->image_label."' and iditem='".$this->$primary_key."' and field=''", "order:orden");
		}
		return parent::__get($item);
	}


	protected function upload($field, $type = 'file', $ajax = false) {

		if($ajax) $file = $_FILES['file'];
		else $file = $field ? $_FILES[$field] : $_FILES['file'];

//		log::to_file("Upload $field, tipo: $type");
//		log::to_file("vardump ".var_export($_FILES, true));
		if(is_uploaded_file($file['tmp_name']) && checkFileSafety($file)) {

//			log::to_file("El fichero existe ".$file['tmp_name']);
			$module = web::request("tmp_upload") ? web::request("tmp_upload") : $this->image_label;
			$primary_key = array_shift($this->getPrimaryKeys());
			$p = pathinfo($file["name"]); $extension = strtolower($p["extension"]);
			$items = $type == 'image' ? new helpers_images() : new helpers_files();
			$orden = $items->count("module='$module' and iditem='".$this->$primary_key."'");
			$item = $type == 'image' ? new helpers_images() : new helpers_files();
			$mime_type = mime_content_type($file['tmp_name']);
			$values = array(
							"iditem" => $this->id,
						//	"descripcion" => $descripcion,
							"extension" => $extension,
							"nombre" => $file["name"],
//							"tipo" => $file["type"],
							"tipo" => $mime_type,
							"fecha" =>  date("Y-m-d H:i:s"),
							"module" => $module,
							"orden" => $orden + 1
							);
			if($field) $values['field'] = $field;

			$id = $item->create($values);
			$item->saveUploadedFile($file["tmp_name"], $id, $extension);
			return true;
		}
//		log::to_file("no existe fichero ".$file['tmp_name']);
		return false;
	}

	public function uploadFile($field = '') {
		return $this->upload($field, 'file');

	}
	public function uploadImage($field = '', $ajax = false) {
		return $this->upload($field, 'image', $ajax);
	}

	public function hasImages() {
		return $this->has_images;
	}

	public function hasFiles() {
		return $this->has_files;
	}

	public function saveFromRequest() {
		$item = parent::saveFromRequest();

		if(web::request("tmp_upload")) {
			$this->database->exec("UPDATE images set iditem='$item->id',module='".$this->image_label."' where module='".web::request("tmp_upload")."'");
		}
	}

	public function getTitleField() {
		$fields = array("name", 'nombre', 'title', 'titulo');
		foreach($fields as $field) {
			if($this->getFields($field)) { return $field; }
		}
	}
}
?>
