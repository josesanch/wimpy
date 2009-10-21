<?php

class sms_plantillas extends Model
{
    public $title = "Plantillas";
    protected $fields = array(
                            "id" => "int primary_key auto_increment",
                            "nombre" => "varchar(255) not_null",
                            "asunto" => "varchar(255) not_null",
                            "texto" => "text not_null html",
                            "Ficheros adjuntos" => "---",
                            "adjuntos" => "files"
                            );


//    public $belongs_to = array('secciones');
//    public $grid_columns = "id, nombre, secciones_id";

}
