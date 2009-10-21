<?php

class sms_grupos extends Model
{
    public $title = "Grupos";

    protected $fields = array(
                            "id" => "int primary_key auto_increment",
                            "nombre" => "varchar(255) not_null",
                            );


//    public $belongs_to = array('secciones');
//    public $grid_columns = "id, nombre, secciones_id";


    public function adminEdit($id, $grupos_id, $usuarios_id)
    {
        if($id == "add") {
            $this->select($grupos_id);
            $this->_insertUser($usuarios_id);
            echo $this->listadoUsuarios();
            exit;
        } elseif($id == "remove") {
            $this->select($grupos_id);
            web::database()->query("delete from sms_usuariosgrupos where grupos_id='$grupos_id' and usuarios_id='$usuarios_id'");
            echo $this->listadoUsuarios();
            exit;
        }

        if(isset($id)) $this->select($id);
		$edit = new html_autoform($this);
		if($id) {
			$edit->add(css("main").js("cursos")."
				<div id='sms_usuarios_dialog'></div>
				".$this->listadoUsuarios()."
				<br/>
				<input type='button' id='add_usuarios' value='añadir usuarios' onclick='sms_usuarios_dialog($this->id)'/>
			");
        }
		return $edit->toHtml();
    }

    public function listadoUsuarios()
    {

        if(!web::database()->tableExists("sms_usuariosgrupos")) {
            web::database()->query("CREATE TABLE  `sms_usuariosgrupos` (
                      `grupos_id` int(11) NOT NULL,
                      `usuarios_id` int(11) NOT NULL
                    ) ENGINE=MyISAM DEFAULT CHARSET=latin1");
        }
		if(!$this->id) return;
		$rs = web::database()->query("select * from sms_usuariosgrupos where grupos_id='$this->id'");

		$str .= "
		<div id='listado-usuarios'>
		<h3>Usuarios en el grupo (".($rs->rowCount()).")</h3>

		<table id='sms_usuariosgrupos'>
		<tr>
			<th>Nombre</th>
			<th>Móvil</th>
			<th>E-mail</th>
			<th></th>";


		foreach($rs as $row) {
			$persona = new sms_usuarios($row['usuarios_id']);
			$str .= "
				<tr class='{$row[estado]}'>
					<td>{$persona->nombre}</td>
					<td>{$persona->movil}</td>
					<td>{$persona->email}</td>
					<td>
						<a href='javascript:sms_usuarios_remove($this->id,$persona->id)'>[x] eliminar</a>
					</td>
				";
		}

		$str .="</table>
				</div>";

		return $str;
    }

    public function countUsers()
    {
        $rs = web::database()->query("select count(*) as total from sms_usuariosgrupos where grupos_id='$this->id'")->fetch();
        return $rs['total'];
    }

    public function adminAddall($grupo, $buscar)
    {
        $this->select($grupo);
        $model = new sms_usuarios();
        if($buscar) {
            if($columns) $c = split(" ?, ?", $columns);
            else $c = array_keys($model->getFields());
            $search = array();
            foreach($c as $field) {
                $search[]= "$field like '%".urldecode($buscar)."%'";
            }
            $search = " (".join(" or ", $search).")";
            $sql = $sql ? $sql." and ".$search : $search;
        }

        $usuarios = $model->select($sql);
        foreach ($usuarios as $usuario) {
            $this->_insertUser($usuario->id);
        }
        echo $this->listadoUsuarios();
    }

    private function _insertUser($usuarios_id)
    {
        $rs = web::database()->query("SELECT *  from sms_usuariosgrupos where grupos_id='$this->id' and usuarios_id='$usuarios_id'");
        if($rs->rowCount() == 0)
            web::database()->query("INSERT INTO sms_usuariosgrupos (grupos_id, usuarios_id) values ('$this->id', '$usuarios_id')");
    }
}
