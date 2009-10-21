<?php

class sms_envios extends Model
{
    public $title = "Envio de SMS";

    protected $fields = array(
                            "id" => "int primary_key auto_increment",
                            "nombre"  => "varchar(255) not_null",
                            "ip"      => "varchar(25)",
                            'usuarios_id'   => 'int',
                            "movil"  => "varchar(25)",
                            "email"  => "varchar(80)",
                            "texto" => "text",
                            "fecha"   => "datetime",
                            "tipo"   => "enum('sms','email')",
                            "usuario" => "varchar(255)",

                            );

//    public $belongs_to = array('secciones');
    public $grid_columns = "id, nombre, movil, texto, fecha";

    public function adminDelete()
    {
        web::instance()->location("/admin/sms_envios");
        exit;
    }

    public function adminEdit($id)
    {
        if($id) {
            $this->select($id);
            $edit = new html_autoform($this);
            $edit->buttons = array("back");
            return "<br>".$edit->toHtml();

        }
        return "
        <form method='post' action='/admin/sms_envios/send/'>
          <fieldset class='admin_form ".get_class($this)."'>
                <legend>".$this->getTitle()."</legend>
        <label for='texto' class='autoform'>	<span>Texto del mensaje</span>
        <br>
            <textarea name='texto' id='texto' rows='10' cols=40 class='textbox'></textarea>
        </label>



        <h2>Grupos seleccionados</h2>
        ".$this->_listGroups()."
        <br/>
        <h2>Usuarios seleccionados</h2>
        <ul id='listado-usuarios' class='envio'>

        </ul>

        <div id='sms_usuarios_dialog'></div>
		<input type='button' id='add_usuarios' value='añadir usuarios' onclick='sms_usuarios_envio_dialog($this->id)'/>
        <input type='submit' id='enviar' value='enviar mensaje' style='display: block; margin: 2em auto;'/>
        </form>
		";

    }

    private function _listGroups()
    {
        $grupos = new sms_grupos();
        $str .= "<ul id='sms-grupos'>";
        foreach ($grupos->select() as $grupo) {
            $str .= "<li><label><input type='checkbox' name='grupos[]' value='$grupo->id'/>$grupo->nombre (".$grupo->countUsers().")</label></li>";
        }
        $str .= "</ul>";
        return $str;
    }

    public function adminSend()
    {
        $str .="<fieldset class='admin_form ".get_class($this)."'>
                <legend>".$this->getTitle()."</legend>
                <ul>";

        $usuarios = new sms_usuarios();
        $conds = array();
        if(!$_REQUEST['usuarios']) $_REQUEST['usuarios'] = array(0);
        if(!$_REQUEST['grupos']) $_REQUEST['grupos'] = array(0);
        $conds[]= "id in (".implode(",", $_REQUEST['usuarios']).")";
        $conds[]= "id in (select usuarios_id from sms_usuariosgrupos where grupos_id in (".implode(",", $_REQUEST['grupos'])."))";

        $sql = implode(" or ", $conds);
        $usuarios = $usuarios->select($sql);
        $sqls = array();
        $usuarioEnviados = "";
		$sms = new net_sms();
		/*
		$sms->username("o2w")
		    ->password("o2w2004");
		    */
        if(count($usuarios) > 0) {
            foreach ($usuarios as $usuario) {
                $sqls[] = "insert into sms_envios
           		    (nombre, usuario, ip, fecha, tipo, usuarios_id, movil, email, texto)
				    values (
				    '$usuario->nombre', '".web::auth()->get("user")."',
				    '".$_SERVER['REMOTE_ADDR']."', NOW(), 'sms', '$usuario->id', '$usuario->movil', '', '".$_REQUEST['texto']."'
			        )";
                $usuariosEnviados .=  "<li>Enviado a $usuario->nombre ($usuario->movil)</li>";
        		$sms->addNumber($usuario->movil);
            }

            if($sms->send($_REQUEST['texto'])) {
        		$str .= "<li style='color: green; font-weight:bold; padding: 0.4em; '>Envío realizado con éxito (".count($sql)." mensajes)</li>";
        		foreach($sqls as $sql) {
        		    web::database()->query($sql);
        		}
        		$str .= $usuariosEnviados;
		    } else {
           		$str .= "<li style='color: red; '>Ha ocurrido un problema al realizar el envío ($sms->errorCode - $sms->errorMessage)</li>";
		    }
        } else {
       		$str .= "<li style='color: red; '>No se ha realizado ningún envío</li>";
        }
        return $str."</ul>

                <input class='submit boton-volver'
                style='display: block; margin: 1em auto;' id='boton-volver'
                type='button' value=volver  onclick=\"document.location='/admin/".
                get_class($this)."/list".web::params()."'\"/>
        </fielset>";

    }

}
