<?
class helpers_l10n extends Model
{
	protected $database_table = 'l10n';
	protected $title = "Traducción";
	public $grid_columns =  "id, field";

	public function adminList() {

		return "<br>".html_base_grid::toHtml($this, "row=0 and lang='".web::instance()->l10n->getDefaultLanguage()."'", $this->grid_columns);
	}

	public function adminEdit($id) {
        $this->select("id='$id'");

		$form = new html_form(get_class($this), '/admin/'.get_class($this)."/save".web::params());

		$row = web::instance()->database->query("select field from l10n where id='$id'")->fetch();

		if($row) {
			$id = $row['field'];
		}

        $auto = new html_autoform($this);

		$auto->add("<input type='hidden' name='field' value='$id'>");

		foreach(l10n::instance()->getLanguages() as $lang) {
			$value = l10n::instance()->get($id, $lang, False);
			$auto->add("
				<p>
					<label for='data'>Texto ($lang)</label>
					<br><textarea name='data_$lang' rows=10 cols=60 style='width: 80%' >$value</textarea>
				</p>");

        }

		return $auto->toHtml();
	}


	public function adminSave($id) {
		$l10n = l10n::instance();
		foreach($l10n->getLanguages() as $lang) {
			$l10n->set($_REQUEST['field'], $_REQUEST["data_".$lang], $lang);
		}
		web::instance()->location("/admin/helpers_l10n/list".web::params());
		exit;
	}

    public function adminDelete($id) {
        $row = web::instance()->database->query("select field from l10n where id='$id'")->fetch();

		if($row) {
			$id = $row['field'];
		}
        web::database()->query("delete from l10n where field='$id' and (model is null or model ='')");
		web::instance()->location("/admin/helpers_l10n/list".web::params());
		exit;
	}

	public function getFields($field = null) {
		$fields = array("id" => array("type" => "int", 'label' => 'id', 'primary_key' => true),
					"lang" => array("type" => "varchar", 'size' => 6,  'label' => 'lang'),
					"model" => array("type" => "varchar", 'size' => 125,  'label' => 'model'),
					"field" => array("type" => "varchar", 'size' => 255,  'label' => 'field'),
					"data" => array("type" => "text",  'label' => 'data'),
					"row" => array("type" => "int", 'size' => 11,  'label' => 'row'));
		if($field) return $fields[$field];
		return $fields;
	}



	public function listAjax() {
		$sta = web::instance()->database->query("select * from l10n where row=0 and lang='".l10n::instance()->getDefaultLanguage()."'");
		$rows = $sta->fetchAll();
		echo json_encode(array("items" => $rows, "count" => count($row)));
	}
}