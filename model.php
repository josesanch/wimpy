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

	/*********************************************************************************************
	****	Llamadas a ajax
	**********************************************************************************************/
	public function listAjax() {
			$limit = $where = $order = "";
			if(isset($_REQUEST["limit"])) $limit = "limit: ".$_REQUEST["start"].", ".$_REQUEST["limit"];
			if(isset($_REQUEST["query"])) $where =  "nombre like '%".$_REQUEST["query"]."%'";
			if(isset($_REQUEST["sort"]))  $order = "order: ".$_REQUEST["sort"]." ".$_REQUEST["dir"];
			$count = $this->count($where);
			if ($_REQUEST["fields"]) $columns = "columns: ".$_REQUEST["fields"];
			$data =  $this->select($where, $limit, $columns, $order);

			$items = array();
			foreach($data as $item) $items[] = $item->getRowData();
			echo json_encode(array("items" => $items, "count" => $count));
	}

	public function listImagesAjax($id) {
			$this->select($id);
			$primary_key = array_shift($this->getPrimaryKeys());
			$images = new helpers_images();

			$module = web::request("tmp_upload") ? web::request("tmp_upload") : $this->image_label;
			$field = web::request("field");
			$field_condition = " and field='$field' ";

			$data =  $images->select("module='$module' and iditem='".$this->$primary_key."' $field_condition", "order:orden", "columns: id, nombre, extension, tipo");
			foreach($data as $item) {
				$item->set('url', $item->src("80x60", "INABOX"));
			}

			$count = count($data);
			$items = array();
			foreach($data as $item) $items[] = $item->getRowData();

			echo json_encode(array("items" => $items, "count" => $count));
	}

	public function loadAjax($id = null) {
		$item = $this->select($id);
		$arr = array();
		foreach($this->getFields() as $field => $attr) {
			$arr[$field] = $this->$field;
		}
#		var_dump($arr);
//		echo  '{ data: '.json_encode($arr).', success: true, "recordCount" : 1}';
//		echo  json_encode(array('success' => 'true',"data" => $arr));
		header('Content-type: text/xml;');
		echo '<?xml version="1.0" encoding="UTF-8"?><response success="true"><data>';
		foreach($this->getFields() as $field => $attr) {
			if($attr["type"] == 'text')
				echo "<$field><![CDATA[".$item->$field."]]></$field>";
		}

		echo '</data></response>';

		exit;
	}


	public function saveImagesAjax($id) {
		$this->select($id);
		$field = web::request("field");
		if(!$this->uploadImage($field, true)) {
			echo "{ success : false }";
		} else {
			echo "{ success : true }";
		}
		exit;
	}


	protected function upload($field, $type = 'file', $ajax = false) {

		if($ajax) $file = $_FILES['file'];
		else $file = $field ? $_FILES[$field] : $_FILES['file'];

//		log::to_file("Upload $field, tipo: $type");
//		log::to_file("vardump ".var_export($_FILES, true));
		if(is_uploaded_file($file['tmp_name']) && $this->checkFileSafety($file)) {

//			log::to_file("El fichero existe ".$file['tmp_name']);
			$module = web::request("tmp_upload") ? web::request("tmp_upload") : $this->image_label;
			$primary_key = array_shift($this->getPrimaryKeys());
			$p = pathinfo($file["name"]); $extension = strtolower($p["extension"]);
			$items = $type == 'image' ? new helpers_images() : new helpers_files();
			$orden = $items->count("module='$module' and iditem='".$this->$primary_key."'");
			$item = $type == 'image' ? new helpers_images() : new helpers_files();
			$values = array(
							"iditem" => $this->id,
						//	"descripcion" => $descripcion,
							"extension" => $extension,
							"nombre" => $file["name"],
							"tipo" => $file["type"],
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

	private function checkFileSafety($file) {
		$safeExtensions = array(
		  'html',
		  'htm',
		  'gif',
		  'jpg',
		  'jpeg',
		  'png',
		  'txt',
		  'avi',
		  'mp3',
		  'wav',
		  'pdf',
		  'doc',
		  'exe',
		  'zip',
		  'rar'
		);

		$path_parts = pathinfo($file['name']);
		$extension = $path_parts['extension'];

		if(!in_array(strtolower($extension), $safeExtensions)) {
			unlink($file['tmp_name']);
			return false;
		}
		return true;
	}

	public function uploadFile($field = '') {
		return $this->upload($field, 'file');

	}
	public function uploadImage($field = '', $ajax = false) {
		return $this->upload($field, 'image', $ajax);
	}

	public function deleteImagesAjax($idimagen) {
		$image = new helpers_images();
		$image->delete($idimagen);
		echo "{ success: true }";
		exit;
	}

	public function deleteFilesAjax($idimagen) {
		$image = new helpers_files();
		$image->delete($idimagen);
		echo "{ success: true }";
		exit;
	}


	public function reorderImagesAjax($idimagen, $mode = 'up') {
		$imagen = new helpers_images();
		$imagen->select($idimagen);
		$iditem = $imagen->iditem;
		$images = new helpers_images();
		$images = $images->select("module='".$this->image_label."' and iditem='$iditem'", "order:orden");
		$pos = 1;
		$previous_image = null;
		foreach($images as $image) {
			$image->orden = $pos++;
			if(($image->id == $idimagen && $mode == "up") || (isset($previous_image) && $previous_image->id == $idimagen && $mode == "down")) {
				$previous_image->orden++;
				$image->orden--;
				$previous_image->save();
			}
			$image->save();
			$previous_image = $image;
		}
		exit;
	}


	public function deleteAjax() {
		$this->delete($_REQUEST["id"]);
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
