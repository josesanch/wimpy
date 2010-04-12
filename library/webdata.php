<?php

class webdata extends Model
{
    public $title = "Datos de la web";
    protected $fields = array(
                            "id" => "int primary_key auto_increment",
                            "nombre" => "varchar(255) not_null",

                            "Dirección" => "---",
                            "direccion" => "varchar(125)",
                            "codigopostal" => "varchar(15)",
                            "poblacion" => "varchar(25)",
                            "provincia" => "varchar(25)",
                            "telefono" => "varchar(25)",
                            "fax" => "varchar(25)",
                            "email" => "varchar(125)",

                            "Descripción de la empresa" => "---",
                            "texto" => "text l10n",
                            "Geolocalización" => "---",
                           	'latitud' => 'varchar(10)',
							'longitud' => 'varchar(10)',

							'Imágenes' => '---',
                            "galeria" => "files"
                            );

	public function adminEdit($id)
	{
        if(isset($id)) $this->select($id);
		$edit = new html_autoform($this);
		$edit->addAfter("latitud", "<input type='button' value='Localizar en el mapa' style='margin-top: 1.2em;' onclick='showGeolocationDialog(\"latitud\", \"longitud\")'/>");
		return $edit->toHtml();
	}

	public function adminList()
	{
	    web::instance()->location("/admin/webdata/edit/1");
	    exit;
	}
}
