<?
class Model extends ActiveRecord
{
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
	public $image_label;
    public $layout;
	protected $label;
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

	private function createTableIfNecessary()
	{
        $metadata = &$this->getMetadata();
     	if (!array_key_exists("created", $metadata) && $this->fields && $this->database) {
			$this->database->createTable(
                $this->getDatabaseTable(),
                $metadata["fields"]
            );
			$metadata["created"] = true;
		}
	}

	public function __get($item)
	{
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


	protected function upload($field, $type = 'file', $ajax = false, $campoEnElModelo = null)
	{

		if($ajax) $file = $_FILES['file'];
		else $file = $field ? $_FILES[$field] : $_FILES['file'];

		if (is_uploaded_file($file['tmp_name']) && checkFileSafety($file)) {

			$module = web::request("tmp_upload") ? web::request("tmp_upload") : $this->image_label;

			$primary_key = array_shift($this->getPrimaryKeys());

			$p = pathinfo($file["name"]); $extension = strtolower($p["extension"]);
			$items = $type == 'image' ? new helpers_images() : new helpers_files();
			$item = $type == 'image' ? new helpers_images() : new helpers_files();

			$mime_type = getMimeType($file["tmp_name"]);
			$orden = $items->count("module='$module' and iditem='".$this->$primary_key."'");
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

			if ($campoEnElModelo) $values['field'] = $campoEnElModelo;
			elseif($field) $values['field'] = $field;

			$id = $item->create($values);
			$item->saveUploadedFile($file["tmp_name"], $id, $extension);
			return true;
		}
		return false;
	}

	public function uploadFile($field = '', $ajax = false, $campoEnElModelo = null)
	{
		return $this->upload($field, 'file', $ajax, $campoEnElModelo);

	}

	public function uploadImage($field = '', $ajax = false, $campoEnElModelo = null)
	{
		return $this->upload($field, 'image', $ajax, $campoEnElModelo);
	}

	/**
	* Añade un fichero al model en el campo que hayamos indicado
	*
	* @param  string $field nombre del campo en el modelo dónde vamos a añadir el archivo.
	* @param  string $filename ruta al fichero.
	* @return helper_files;
	*/
	public function addFile($field, $filename, $name = null)
	{
		if (!$name) $name = $filename;
		$fieldData = $this->getFields($property);
		$type = $fieldData["type"];
		$items 	= $type == 'file' 	? new helpers_files() : new helpers_images();
		$item 	= $type == 'file' 	? new helpers_files() : new helpers_images();
		$module = get_class($this);
		$p = pathinfo($filename); $extension = strtolower($p["extension"]);
		$mime_type = getMimeType($filename);
		$orden = $items->count("module='$module' and iditem='".$this->id."'");
		$values = array(
			"iditem" 	=> $this->id,
			"extension" => $extension,
			"nombre" 	=> $name,
			"tipo" 		=> $mime_type,
			"fecha" 	=> date("Y-m-d H:i:s"),
			"module" 	=> $module,
			"orden" 	=> $orden + 1,
			"field" 	=> $field
		);
		$id = $item->create($values);
		$item->saveFile($filename, $id, $extension);
		return $item;
	}

	public function hasImages()
	{
		return $this->has_images;
	}

	public function hasFiles()
	{
		return $this->has_files;
	}

	public function saveFromRequest()
	{
		$item = parent::saveFromRequest();

		if(web::request("tmp_upload")) {
			$this->database->exec("UPDATE images set
			    iditem='$item->id',
			    module='".$this->image_label."'
			    WHERE module='".web::request("tmp_upload")."'"
			 );
			 $this->database->exec("UPDATE files set
			    iditem='$item->id',
			    module='".$this->image_label."'
			    WHERE module='".web::request("tmp_upload")."'"
			 );
		}
	}

	public function getTitleField()
	{
		if($this->titleField) return $this->titleField;
		$fields = array("name", 'nombre', 'title', 'titulo');
		foreach($fields as $field) {
			if($this->getFields($field)) { return $field; }
		}
	}

	public function getTitle()
	{
		return $this->title ? $this->title : get_class($this);
	}

	public function adminRedir()
	{
		if (web::request("redir")) {
			web::instance()->location(web::request("redir"));
		} else {
			web::instance()->location(
				'/admin/'.get_class($this).
				"/list".web::params(null, false)
			);
		}
        exit;
	}

	protected function _deleteAsociatedFiles()
	{
		$primary_key = array_shift($this->getPrimaryKeys());

		$images = new helpers_images();
		$images =  $images->select("module='".$this->image_label."' and iditem='".$this->$primary_key."'", "order:orden");
		foreach ($images as $image)
			$image->delete();

		$files = new helpers_files();
		$files =  $files->select("module='".$this->image_label."' and iditem='".$this->$primary_key."'", "order:orden");
		foreach ($files as $file)
			$file->delete();
	}

	public function url()
	{
		return "/".get_class($this)."/view/$this->id/".convert_to_url($this->nombre);
	}
}
