<?php

class AdminController extends AdministrationController {
	public $logo = "/img/logo.gif";



	public function __construct() {
		$this->menu = array(
            "Secciones" => array("noticias"),
            "Contenidos" => array("documentos", "enlaces"),
            "Productos" => array("productos", "tipo_productos" => "Tipos", "marcas"),
            "Centros" => array("centros", "paises", "provincias"),
            "Otros" => array("modules_translate" => "textos"));

		parent::__construct();
	}

	public function indexAction()
    {

    }
	public function marcasfEdit() {
		$str .= init_extjs();
		$str.=<<<EOF
			<script>
			    Ext.QuickTips.init();

				Ext.onReady(function(){
					var text = new Ext.form.HtmlEditor({
						id: "hola",
						grow : true,
						width: 600
					});
					var text2 = new Ext.form.TextField({
						id: "hola"
					});

				text.render("apellidos");
				text2.render("nombre");
				});
			 </script>
			 <div id="nombre"></div>
			 <div id="apellidos"></div>
			 <div id="botones"></div>
EOF;
		$this->view->content = $str;

	}
}
?>
