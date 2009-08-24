<?php

class helpers_model_ajax  {
	protected $model;

	public function __construct($model) {
		$this->model = $model;
	}

	public function listItems() {
			$limit = $where = $order = "";
			if(isset($_REQUEST["limit"])) $limit = "limit: ".$_REQUEST["start"].", ".$_REQUEST["limit"];
			if(isset($_REQUEST["query"])) $where =  "nombre like '%".$_REQUEST["query"]."%'";
			if(isset($_REQUEST["sort"]))  $order = "order: ".$_REQUEST["sort"]." ".$_REQUEST["dir"];
			$count = $this->model->count($where);
			if ($_REQUEST["fields"]) $columns = "columns: ".$_REQUEST["fields"];
			$data =  $this->model->select($where, $limit, $columns, $order);

			$items = array();
			foreach($data as $item) $items[] = $item->getRowData();
			echo json_encode(array("items" => $items, "count" => $count));
	}


	public function files($action, $id, $field) {

		switch ($action) {

			case 'read':
				$this->model->select($id);
				$images = new helpers_images();
				$module = web::request("tmp_upload") ? web::request("tmp_upload") : get_class($this->model);
				if(!web::request("tmp_upload")) $cond = " and iditem='$id' ";

				$data =  $images->select("module='$module' $cond and field='$field' ", "order:orden", "columns: id, nombre, extension, tipo");
				$str = "
				<h5 class='images'>".count($data)." archivos</h5>
				<ul id='files_$field' class='images-dataview clearfix'>";
				foreach($data as $item) {
					$str .="
								<li id='images-$item->id'>
									<div>
										<img src='".$item->src("80x60", "INABOX")."' title='$item->nombre ".html_template_filters::bytes($item->size())."'/>
										<a href='#' class='images-delete' id='$id-".get_class($this->model)."-$field-$item->id-".web::request("tmp_upload")."'><img src='/resources/icons/cross.gif' border='0'/></a>
									</div>
									<p class='editable' id='file-$item->id'>$item->nombre</p>

								</li>";
				}
				$str .= "</ul>
				<script type='text/javascript'>
					$('a.images-delete').bind('click', delete_image);
					$('.editable').editable('/ajax/".get_class($this->model)."/files/update');

				</script>
				";
				echo $str;
				exit;
			break;

			case 'save':
				$this->model->select($id);
				$this->model->uploadImage($field);
				echo "1";
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


	public function load($id = null) {
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

	/* Reordenar las listas de administración.
	 *
	 */
	public function reorderList($id, $pre) {
		$rows = web::database()->query("SELECT id FROM ".$this->model->getDatabaseTable()." ORDER BY ".$this->model->field_used_for_ordenation.", id")->fetchAll();
		$count = 0;

		if($pre == 'null')
			web::database()->query("UPDATE ".$this->model->getDatabaseTable()." SET ".$this->model->field_used_for_ordenation."=".($count++)." WHERE id=$id");

		foreach($rows as $row) {

			if($row['id'] != $id)
				web::database()->query("UPDATE ".$this->model->getDatabaseTable()." SET ".$this->model->field_used_for_ordenation."=".($count++)." WHERE id=".$row[id]);

			if($row['id'] == $pre)
				web::database()->query("UPDATE ".$this->model->getDatabaseTable()." SET ".$this->model->field_used_for_ordenation."=".($count++)." WHERE id=$id");

		}

		exit;

	}

	public function autocomplete($valor) {
		$q = strtolower($_GET["q"]);
		$primary_key = array_shift($this->model->getPrimaryKeys());
		$name = $this->model->getTitleField();
		$results = $this->model->select("columns: $primary_key, $name", "where: $name like '%$q%'", "order: $name");
		foreach($results as $row) {
			echo $row->$name."|".$row->$primary_key."\n";
		}
		exit;
	}

	public function saveImages($id) {
		$this->model->select($id);
		$field = web::request("field");
		if(!$this->model->uploadImage($field, true)) {
			echo "{ success : false }";
		} else {
			echo "{ success : true }";
		}
		exit;
	}


	public function deleteFiles($idimagen) {
		$image = new helpers_files();
		$image->delete($idimagen);
		echo "{ success: true }";
		exit;
	}

	public function deleteImages($idimagen) {
		$image = new helpers_images();
		$image->delete($idimagen);
		echo "{ success: true }";
		exit;
	}


	public function reorderImages($idimagen, $mode = 'up') {
		$imagen = new helpers_images();
		$imagen->select($idimagen);
		$iditem = $imagen->iditem;
		$images = new helpers_images();
		$images = $images->select("module='".$this->model->image_label."' and iditem='$iditem'", "order:orden");
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


	public function delete() {
		$this->model->delete($_REQUEST["id"]);
	}

	private function parse($request) {
	   return (isset($_REQUEST[$request])) ? json_decode(stripslashes($_REQUEST[$request]), true) : null;

	}
}

?>
