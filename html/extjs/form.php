<?
class html_extjs_form extends html_object {

	private $model;

	public function __construct($model, $colums = '', $sql = '') {
			$this->__data .= init_extjs().js_once("ext/UploadDialog/Ext.ux.UploadDialog").js_once("ext/UploadDialog/locale/es.utf-8").css_once("ext/UploadDialog/css/Ext.ux.UploadDialog");
			$this->model = $model;
	}

	public function toHtml() {
		$other_js_data  = "";
		$model_name =  strtolower(get_class($this->model));
		$items 	 = $this->getItems();
		$buttons = $this->getButtons();

		if($this->model->has_images) {
//			$this->__data .= js_once("ext/UploadDialog/Ext.ux.UploadDialog").js_once("ext/UploadDialog/locale/es.utf-8").css_once("ext/UploadDialog/css/Ext.ux.UploadDialog").
			$this->__data .= js_once('ext/dataview/data-view-plugins').css_once('ext/dataview/data-view');
			$other_js_data = $this->uploadDialog();
		}

		$a = array();
		foreach($this->model->getFields() as $field => $attr) {
			if($attr["type"] == 'text') $a[] = $field;
		}

		$store_fields = "'".join("','", $a)."'";
		$id = $this->model->id;
		$this->__data .=<<<EOF
<style>
	.icon_remove{ background-image:url(/resources/icons/delete.gif) !important; }
	.icon_up 	{ background-image:url(/resources/icons/arrow-up.gif) !important; }
	.icon_down 	{ background-image:url(/resources/icons/arrow-down.gif) !important; }
	.icon_save 	{ background-image:url(/resources/icons/save.gif) !important; }
	.icon_add 	{ background-image:url(/resources/icons/add.gif) !important;     }
	.icon_edit 	{ background-image:url(/resources/icons/edit.gif) !important; }
	.icon_upload{ background-image:url(/resources/js/ext/UploadDialog/images/upload-start.gif) !important; }
</style>
<script>

	Ext.QuickTips.init();
	Ext.onReady(function()
	{
		var form = new Ext.FormPanel(
		{
			fileUpload : true,
			url : '/admin/$model_name/save/',
			frame : true,
			title : '$model_name',
			bodyStyle : 'padding:5px 5px 0',
			width : '85%',
			items : [ $items ],
			buttons : [ $buttons ]
		});

		$other_js_data

		function formOk(form, action) {
			history.go(-1);
		}

		function formError(form, action) {
			alert("error");
		}
		form.render("form_div");
	});

		 </script>
		 <div id="form_div" style="margin-left: 10%; padding-top: 5px; margin-bottom: 40px;"></div>
 		 <div id=images></div>
EOF;


		return $this->__data;
	}

	protected function getItems() {
		$items = array();
		$l10n = array();
		$fields = $this->model->getAllFields();
		foreach($fields as $field  => $attr) {
			if($attrs['l10n']) $l10n[]= $field;
			$items[]= html_extjs_field::toHtml($this->model, $field);
		}

		foreach(l10n::instance()->getNotDefaultLanguages() as $lang) {

			$str = "new Ext.DataView(";
			$items[]= html_extjs_field::toHtml($this->model, $field, null, $lang);
		}
		return implode(",", $items);
	}

	protected function getButtons() {
		return "{ text: 'Guardar', iconCls: 'icon_save', handler : function () {
//		form.getForm().submit()
		form.getForm().submit({ waitMsg:'Guardando datos...', success: formOk, failure : formError })
		} },
		{ text: 'Volver' , handler : function () { history.go(-1); } }";

	}

	public function uploadDialog() {
		$iditem = $this->model->id;
		$model_name =  strtolower(get_class($this->model));
		$data = "
			var dialog = null;
			var button = null;
			function getDialog() {
			if (!dialog) {
				  dialog = dlg = new Ext.ux.UploadDialog.Dialog({
					url: '/ajax/$model_name/saveImages/$iditem',
					reset_on_hide: false,
					allow_close_on_upload: true,
					upload_autostart: true
				  });

				  dialog.on('uploadsuccess', onUploadSuccess);
				}
				return dialog;
			}
			function onUploadSuccess(dialog, filename, resp_data)  { images_store.reload();	}
			function showDialog(button) {	getDialog().show(button.getEl());  }

			function deleteImage(button) {
			   	var selected_records = view.getSelectedRecords();
			   	id = selected_records[0].data['id'];
				Ext.Ajax.request({
					   url: '/ajax/$model_name/deleteImages/' + id,
					   success: function () { images_store.reload() }
				});
			}
			function reorderUp(button) {
			   	var selected_records = view.getSelectedRecords();
			   	id = selected_records[0].data['id'];
				Ext.Ajax.request({  url: '/ajax/$model_name/reorderImages/' + id + '/up',  success: function () { images_store.reload() } });
			}
			function reorderDown(button) {
			   	var selected_records = view.getSelectedRecords();
			   	id = selected_records[0].data['id'];
				Ext.Ajax.request({  url: '/ajax/$model_name/reorderImages/' + id + '/down',  success: function () { images_store.reload() } });
			}


			var reorderUp = new Ext.Button( { iconCls	: 'icon_up', handler : reorderUp })
			var reorderDown = new Ext.Button( { iconCls	: 'icon_down', handler : reorderDown  })

			var upload_button = new Ext.Button({
				id: 'show-button',
				text: 'Subir archivos',
				handler: showDialog,
				iconCls : 'icon_upload'
			});
			var delete_image_button = new Ext.Button({
	            iconCls:'icon_remove',
				text: 'Eliminar archivo',
				handler: deleteImage
			});

			var tpl = new Ext.XTemplate(
				'<tpl for=\".\">',
				'<div class=\"thumb-wrap\" id=\"{nombre}\">',
				    '<div class=\"thumb\"><img src=\"{url}\" title=\"{nombre}\"></div>',
				    '<span class=\"x-editable\">{nombre}</span></div>',
				'</tpl>',
				'<div class=\"x-clear\"></div>'
			);

			var images_store = new Ext.data.JsonStore({
						url: '/ajax/$model_name/listImages/$iditem',
						autoLoad : true,
						root: 'items',
						fields: [
							'id', 'nombre', 'extension', 'url'
						]
					});

			var view = new Ext.DataView({
				store: images_store,
				tpl: tpl,
				autoHeight:true,
				width: '100',
				height: 200,
				singleSelect: true,
				overClass:'x-view-over',
				itemSelector:'div.thumb-wrap',
				emptyText: 'No hay imágenes para mostrar',
				plugins: [
//		            new Ext.DataView.DragSelector({dragSafe:true}),
		            new Ext.DataView.Reorder(),
		            new Ext.DataView.LabelEditor({dataIndex: 'nombre'})
				    ]
				});

			var images_panel = new Ext.Panel({
				id:'images-view',
				frame:true,
				width: '100%',
				autoHeight:true,
				collapsible:true,
				layout:'fit',
				title:'Imágenes asociadas a la ficha',
				items : [ view ]
			});

			images_panel.addButton(reorderUp, { tooltip : 'Reordenar la imagen seleccionada'});
			images_panel.addButton(reorderDown, { tooltip : 'Reordenar la imagen seleccionada'});

			images_panel.addButton(upload_button);
			images_panel.addButton(delete_image_button);
		    form.add(images_panel);
			";
		return $data;

	}
}
?>
