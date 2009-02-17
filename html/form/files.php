<?
class html_form_files extends html_form_input {

	protected $model;
	public static $instance = false;
	private $prepend = '';
	private $tmp_upload;
	protected $attrs = array
	(
		'type'    => 'file',
		'class'   => 'textbox',
		'value'   => ''
	);

	public function __construct($field, $model, $tmp_upload) {
		$this->model = $model;
		$this->tmp_upload = $tmp_upload;
		if(!html_form_files::$instance) {
			html_form_files::$instance = true;
			$this->prepend =  init_extjs().
				js_once("ext/UploadDialog/Ext.ux.UploadDialog")."\n".
				js_once("ext/UploadDialog/locale/es.utf-8")."\n".
				css_once("ext/UploadDialog/css/Ext.ux.UploadDialog")."\n".
				js_once('ext/dataview/data-view-plugins')."\n".
				css_once('ext/dataview/data-view')."\n";
		}
		parent::__construct($field);
	}

	public function toHtml() {

		$model_name = get_class($this->model);
		$iditem = $this->model->id;
		$field = $this->attrs['name'];
		$tmp_upload = $this->tmp_upload;

		if($this->attrs['label']) {
			$str .= "<label for='".($this->attrs['id'] ? $this->attrs['id'] : $this->attrs['name'] )."' class='autoform'>".$this->attrs['label']."</label>";
		}

		/* CÓDIGO JAVASCRIPT */
		$str .= "
		<div id='div_files_panel_$field' style='margin-top: 10px; margin-bottom: 10px;'></div>
		<script>
		    Ext.QuickTips.init();
			var dialog_$field = null;
			var button_$field = null;

			/* STORE DE IMAGENES */
			var images_store_$field = new Ext.data.JsonStore ({
					url: '/ajax/$model_name/listImages/$iditem/tmp_upload=$tmp_upload/field=$field?' + Math.random(),
					autoLoad : true,
					root: 'items',
					fields: [
						'id', 'nombre', 'extension', 'url'
					]


			});

			function files_getDialog_$field() {
				if (!dialog_$field) {
					dialog_$field = dlg = new Ext.ux.UploadDialog.Dialog({
						url: '/ajax/$model_name/saveImages/$iditem/tmp_upload=$tmp_upload/field=$field?' + Math.random(),
///						reset_on_hide: false,
						allow_close_on_upload: true,
						upload_autostart: true,
						post_var_name: '$field'
				  	});

					dialog_$field.on('uploadsuccess', onUploadSuccess_$field);
				}
				return dialog_$field;
			}

			function onUploadSuccess_$field(dialog, filename, resp_data)  { images_store_$field.reload();	}

			function showDialog_$field(button_$field) { files_getDialog_$field().show(button_$field.getEl());  }

			function deleteImage_$field(button) {
			   	var selected_records = files_view_$field.getSelectedRecords();
			   	id = selected_records[0].data['id'];
				Ext.Ajax.request({
					   url: '/ajax/$model_name/deleteImages/' + id + '/tmp_upload=$tmp_upload/field=$field?' + Math.random(),
					   success: function () { images_store_$field.reload() }
				});
			}

			function reorderUp_$field(button) {
			   	var selected_records = view.getSelectedRecords();
			   	id = selected_records[0].data['id'];
				Ext.Ajax.request({  url: '/ajax/$model_name/reorderImages/' + id + '/up/tmp_upload=$tmp_upload/field=$field',  success: function () { images_store_$field.reload() } });
			}

			function reorderDown_$field(button) {
			   	var selected_records = view.getSelectedRecords();
			   	id = selected_records[0].data['id'];
				Ext.Ajax.request({  url: '/ajax/$model_name/reorderImages/' + id + '/down/tmp_upload=$tmp_upload/field=$field',  success: function () { images_store_$field.reload() } });
			}


			// BUTTONS
			var reorderUp_$field 	= new Ext.Button( { iconCls	: 'icon_up', handler : reorderUp_$field })
			var reorderDown_$field 	= new Ext.Button( { iconCls	: 'icon_down', handler : reorderDown_$field })
			var upload_button_$field = new Ext.Button({ id: 'show-button', text: 'Subir archivos', handler: showDialog_$field, iconCls : 'icon_upload' });
			var delete_image_button_$field = new Ext.Button({ iconCls:'icon_remove', text: 'Eliminar archivo', handler: deleteImage_$field });

			// TEMPLATE FOR ITEMS
			var tpl = new Ext.XTemplate(
				'<tpl for=\".\">',
				'<div class=\"thumb-wrap\" id=\"{nombre}\">',
				    '<div class=\"thumb\"><img src=\"{url}\" title=\"{nombre}\"></div>',
				    '<span class=\"x-editable\">{nombre}</span></div>',
				'</tpl>',
				'<div class=\"x-clear\"></div>'
			);


			var files_view_$field = new Ext.DataView({
				store		: images_store_$field,
				tpl			: tpl,
				autoHeight	:true,
				width		: '100',
				height		: 200,
				singleSelect: true,
				overClass	:'x-view-over',
				itemSelector:'div.thumb-wrap',
				emptyText	: 'No hay imágenes para mostrar',
				plugins		: [
				//	            new Ext.DataView.DragSelector({dragSafe:true}),
								new Ext.DataView.Reorder(),
								new Ext.DataView.LabelEditor({ dataIndex: 'nombre' })
							  ]
				});

			var files_images_panel_$field = new Ext.Panel({
				id:'images-view-$field',
				cls: 'files_images_panel',
				frame: true,
				width: '100%',
				autoHeight:true,
				collapsible:true,
				layout:'fit',
				title:'Imágenes asociadas a la ficha',
				items : [ files_view_$field ]
			});

			files_images_panel_$field.addButton(reorderUp_$field, { tooltip : 'Reordenar la imagen seleccionada'});
			files_images_panel_$field.addButton(reorderDown_$field, { tooltip : 'Reordenar la imagen seleccionada'});

			files_images_panel_$field.addButton(upload_button_$field);
			files_images_panel_$field.addButton(delete_image_button_$field);
			files_images_panel_$field.render('div_files_panel_$field');
//		    form.add(files_images_panel_$field);
		</script>
";


		return $this->prepend."$str";
	}
}



/*
																listeners: {
																				'onSave':
																						function(editor, value, oldvalue) {
																							console.log('beforecomplete');
																							console.log(editor, value, oldvalue);
																							// Always cancel edit event to make database changes first
																							// and then at callback success make the record changes
																							editor.cancelEdit();
																							// !!!!! cancelEdit() do not work,
																							// make a infinite beforecomplete loop !!!!!!

																							var newValue = value + this.originalFile.extension;
																							if (oldvalue != newValue) { // Make database changes
																								alert('/ajax/$model_name/files/rename/' + this.galleryLabel)
																								indicatorOn();

																								Ext.Ajax.request({
																									method: 'post',
																									url: '/ajax/$model_name/files/rename/' + this.galleryLabel + '&s=images',

																									params: {
																										oldname: oldvalue,
																										newname: newValue,
																									},

																									success: function(result, b) {
																										indicatorOff();
																										var response = Ext.util.JSON.decode(result.responseText);
																										if (response.success) {
																											// All OK Set record change
																											this.setValue(newValue); // do not work
																											this.activeRecord.set('name', newValue); // do not work
																											// Other posibility is to reload the store
																											// but make another database request
																											// this.reset();
																											// this.view.store.reload();
																										} else
																											Ext.MessageBox.alert('Error', response.message);
																									},

																									failure: function() {
																										indicatorOff();
																										Ext.MessageBox.alert('Error', 'Lost server connection.');
																									},
																								 	scope: this
																								});
																							}
																						}

																			}
*/
?>
