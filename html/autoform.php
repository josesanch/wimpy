<?
class html_autoform extends html_form {

	public function __construct($model = null) {

		if(is_object($model)) {
			$this->setModel($model);
			parent::__construct(get_class($model), "/admin/".get_class($model)."/save".web::params());
		} else  {
			parent::__construct(get_class($model));
		}
		$this->construct_head();
	}

	private function construct_head() {

		$this->add("
			<fieldset style='width: 70%; margin: auto; ' class='admin_form'>
				<legend>".get_class($this->model)."</legend>
			");

		if(!$this->model->id) {
			$tmp_upload = get_class($this->model)."_".rand();
		 	$this->hidden("tmp_upload")->value($tmp_upload);
		}

		foreach($this->model->getAllFields() as $field => $attrs) {
			if($attrs['primary_key']) {
				$id =$this->model->$field;
				$this->hidden($field)->value($this->model->$field);
			} else {
				$this->auto($field, null, $tmp_upload);
			}
			$this->add("\n");
		}


	//	return $this->data.js_once("jquery").js_once("jquery.validate");


		if($this->model->hasImages()) {
			$this->add("<div id='images' style='margin-top: 10px; margin-bottom: 10px;'></div>");
			$this->add(init_extjs().js_once("ext/UploadDialog/Ext.ux.UploadDialog").js_once("ext/UploadDialog/locale/es.utf-8").css_once("ext/UploadDialog/css/Ext.ux.UploadDialog"));
			$this->add(js_once('ext/dataview/data-view-plugins').css_once('ext/dataview/data-view'));
			$this->add($this->uploadDialog($tmp_upload));

		}
	}

	private function construct_foot() {

		$this->add("
		<script>
				function delete_item(id) {

					if(confirm('Está seguro')) {
						document.location='/admin/".get_class($this->model)."/delete/' + id + '".web::params()."';
					}
				}
			</script>
		<div style='margin: auto; width: 250px; padding-top:10px;'>
				<input class='submit' type='button' value=volver onclick=\"document.location='/admin/".get_class($this->model)."/list".web::params()."'\">
				<input class='submit' type='button' value=eliminar onclick=\"delete_item('".$this->model->id."');\">
				<input class='submit' type='submit' value=enviar>
			</div>");
		$this->add("</fieldset>");

	}

	public function toHtml() {
		$this->construct_foot();
		return parent::toHtml();
	}

public function uploadDialog($tmp_upload) {
		$iditem = $this->model->id;
		$model_name =  strtolower(get_class($this->model));
		$data = "
		<script>
		    Ext.QuickTips.init();
			var dialog = null;
			var button = null;
			function getDialog() {
			if (!dialog) {
				  dialog = dlg = new Ext.ux.UploadDialog.Dialog({
					url: '/ajax/$model_name/saveImages/$iditem/tmp_upload=$tmp_upload',
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
					   url: '/ajax/$model_name/deleteImages/' + id + '/tmp_upload=$tmp_upload',
					   success: function () { images_store.reload() }
				});
			}
			function reorderUp(button) {
			   	var selected_records = view.getSelectedRecords();
			   	id = selected_records[0].data['id'];
				Ext.Ajax.request({  url: '/ajax/$model_name/reorderImages/' + id + '/up/tmp_upload=$tmp_upload',  success: function () { images_store.reload() } });
			}
			function reorderDown(button) {
			   	var selected_records = view.getSelectedRecords();
			   	id = selected_records[0].data['id'];
				Ext.Ajax.request({  url: '/ajax/$model_name/reorderImages/' + id + '/down/tmp_upload=$tmp_upload',  success: function () { images_store.reload() } });
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
						url: '/ajax/$model_name/listImages/$iditem/tmp_upload=$tmp_upload',
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
				cls: 'files_images_panel',
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
			images_panel.render('images');
//		    form.add(images_panel);
</script>
			";
		return $data;

	}


}
?>
