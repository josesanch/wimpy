<?
class nodes extends Model {

	protected $fields = array(
        "id"                => "int primary_key auto_increment",
        "nombre"            => "varchar(255) not_null l10n",
        "titulo"            => "varchar(255) not_null l10n",
        "subtitulo"         => "varchar(255) l10n",

        'sections_id'      => "int label='Sección a la que pertenece'",
        "texto"             => "text not_null html l10n",

        'foto'              => 'image',
        'orden'             => 'int hidden',
        'archivos'          => "files title='Archivos asociados'",

        "Otros" => "--- accordion collapsed",
        'plantilla'          => "varchar(255) title='Para uso interno'",

        "SEO" => "--- accordion collapsed",
        'descripcion' => 'varchar(255) l10n',
        'keywords' => 'text l10n',
    );


	public $belongs_to = array('sections');
	public $grid_columns = "id, nombre, sections_id, titulo";
//	public $has_images = true;

	public function adminList() {

		$filter = web::request('filter');
		if($filter) $sql = "contenidos.sections_id='$filter'
                            or contenidos.secciones_id in
                             (select id from sections where sections_id='$filter')";

		return "<br/>".html_base_grid::toHtml($this, $sql, $this->grid_columns);
		break;
	}

	public function adminEdit($id) {
		$model = new contenidos();
		if(isset($id)) $model->select($id);
		$filter = web::request('filter');
		$edit = new html_autoform($model);
		$select = $edit->get("secciones_id");
		$select->clear();
		$rs = web::database()->query("	SELECT id, nombre FROM secciones s where id=$filter
									union
										SELECT id, CONCAT('&nbsp;&nbsp;>&nbsp;', nombre) as nombre from secciones where secciones_id=$filter");

		foreach($rs as $row) {
			$select->add(array($row['id'] => $row['nombre']));
		}

		return $edit->toHtml();
	}

	public function url() {
        $arr[]= $this->nombre;
        $seccion = $this->getSecciones();
        $arr[]= $seccion->nombre;

        if ($subseccion = $seccion->getSecciones()) {
            $arr[]=$subseccion->nombre;
        }

        $url = implode("/", array_map(convert_to_url, array_reverse($arr)));
        if (substr($url, 0, 1) != "/") $url = "/$url";
        return $url;

	}

	public function breadCrumb() {
		$rs = web::database()->query("SELECT
				(select nombre from secciones s1 where c.secciones_id=s1.id) as seccion,
				(select secciones_id from secciones s1 where c.secciones_id=s1.id) as seccion_id,
				(select nombre from secciones where seccion_id=secciones.id) as seccion_padre,
			c.id, c.nombre
			FROM contenidos c where c.id='$this->id'")->fetch();

		$str = "<ul id='breadcrumb'>
				<li><a href='/'>Inicio</a>&raquo;</li>\n";
		if($rs) {

			if($rs['seccion_padre']) {
				$url = "/".convert_to_url($rs['seccion_padre']);
				$str .= "\t\t\t\t<li><a href='$url'>".$rs['seccion_padre']."</a>&raquo;</li>\n";
			}
			$url .= "/".convert_to_url($rs['seccion']);
			$str .= "\t\t\t\t<li><h3><a href='$url'>".$rs['seccion']."</a></h3>&raquo;</li>\n";
			$str .= "\t\t\t\t<li><h2>".$this->titulo."</h2></li>\n";
		}
		$str .= "\t\t\t</ul>";
		return $str;
	}


	public function title() {
		$rs = web::database()->query("SELECT
				(select nombre from secciones s1 where c.secciones_id=s1.id) as seccion,
				(select secciones_id from secciones s1 where c.secciones_id=s1.id) as seccion_id,
				(select nombre from secciones where seccion_id=secciones.id) as seccion_padre,
			c.id, c.nombre
			FROM contenidos c where c.id='$this->id'")->fetch();
		return implode(" | ", array_filter(array($rs['seccion_padre'], $rs['seccion'], $this->titulo)));
	}


    public function getPadreSeccion()
    {
        $seccion = $this->getSecciones();
        if ($padre = $seccion->getSecciones()) {
            if ($padre->id)
                return $padre;
        }
        return $seccion;
    }


    public function getSubmenu()
    {
        $seccion = $this->getPadreSeccion();
        $idSeccion = $seccion->id;

        $sql = "
select
  contenidos.id, contenidos.nombre, contenidos.secciones_id, s.nombre as seccion_nombre, s.orden as seccion_orden, contenidos.orden,
  (select secciones_id from secciones s2 where s2.id=contenidos.secciones_id) as padre,
  (select orden from secciones where id=(select secciones_id from secciones s2 where s2.id=contenidos.secciones_id)) as padre_orden,
  (select nombre from secciones where id=(select secciones_id from secciones s2 where s2.id=contenidos.secciones_id)) as padre_nombre
from
  contenidos
inner join secciones s
on s.id=contenidos.secciones_id
where
contenidos.secciones_id='$idSeccion'
or contenidos.secciones_id in (select id from secciones where secciones_id='$idSeccion')
order by padre_orden, seccion_orden, orden, id";


        $rs = web::database()->query($sql)->fetchAll();
        $menu = "\n<ul id='submenu'>\n";
        foreach ($rs as $row) {
            if ($seccionActual != $row["seccion_nombre"] && $row["padre_nombre"]) {
                if (isset($seccionActual)) {
                    $menu .= "     </ul>\n";
                    $menu .= "  </li>\n";
                }
                $menu .= "  <li class='row-".(++$count2)."'><a href=\"#\">$row[seccion_nombre]</a>\n";
                $menu .= "      <ul class='subsubmenu'>\n";
                $seccionActual = $row["seccion_nombre"];
                $count = 0;
            }

            $item = new contenidos($row["id"]);
            $active = $this->id == $item->id ? " class='active'" : "";
            $menu .= "        <li class='row-".++$count."'><a href='".$item->url()."'$active>$item->nombre</a></li>\n";
        }
        if (isset($seccionActual)) $menu .= "    </ul>\n  </li>\n";
        $menu .= "<li class=\"facebook\">
          <a href=\"http://www.facebook.com/gathering.eoisanjavier\" class=\"facebook\"><img src=\"/images/facebook.gif\" alt=\"{t:Sigue a o2w en Facebook}\"/></a></li>
  <li class=\"facebook\">
    <img src=\"/images/consejeria-region-murcia.gif\" alt=\"Logotipo de la carm\"/>
  </li>";

        $menu .= "</ul>";
        return $menu;
    }

    private function _getSubSubMenu($row)
    {

    }
}

