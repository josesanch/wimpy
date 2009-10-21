<?php

class sms_usuarios extends Model
{
    public $title = "Contactos para envio de SMS";
    protected $fields = array(
                            "id" => "int primary_key auto_increment",
                            "nombre" => "varchar(255) not_null",
                            "movil" => "varchar(25)",
                            "email" => "varchar(80)"
                            );


//    public $belongs_to = array('secciones');
    public $grid_columns = "id, nombre, movil, email";

    public function adminList($id)
    {
        $grid = new html_base_grid();

        if (web::request("dialog")) {
            $grid->buttons = array("search");

            if(web::request("envios")) {
                $grid->onClick = "sms_usuarios_envio_add(";
            } else {
                $grid->onClick = "sms_usuarios_add(".web::request("grupos_id").",";
                $grid->search = "<input type='button' value='Añadir todos'
                                style='margin-left: 2em;'
                                onclick=\"sms_usuarios_add_all(".web::request("grupos_id").")\"/>";
            }
        }
        return "<br/>".$grid->toHtml($this, null, $this->grid_columns);
    }

}
