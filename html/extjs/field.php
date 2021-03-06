<?
class html_extjs_field {


	public function toHtml($model, $fieldname, $renderTo = null, $lang = null) {
		$iditem = $model->id;
		$model_name = get_class($model);
		$field = $model->getFields($fieldname);
		$label = ucfirst($field["label"]);

		if(isset($field["belongs_to"])) {
				$model_name = substr($fieldname, 0, -3);
				$model_item = new $model_name;
				$item = $model_item->selectFirst($model->$fieldname);
				$name = $model->getFields("nombre") ? 'nombre' : 'name';
				$store_fields = "'id', '$name'";
				$store_fields_list = "id, $name";
				$item_text = $item ? $item->$name : '';
				$item_value = $item ? $item->id : '';


				$item = "new Ext.form.ComboBox({
								name : 'select_$fieldname',
								hiddenName : '$fieldname',
								value:  '".$item_text."',
								fieldLabel : '$label',
								displayField : '$name',
   							 	valueField : 'id',
								editable : false,
							    selectOnFocus: true,
						        triggerAction: 'all',
								forceSelection : true,
						        store : new Ext.data.JsonStore({
									url: '/ajax/$model_name/list',
									root: 'items',
									totalProperty: 'count',
									fields: [$store_fields],
									baseParams : { fields : '$store_fields_list' },
									autoLoad : true
									}),
								listeners : {
									render : function() {
										document.getElementById('$fieldname').value = '$item_value';
										}
								}
    							})";
		} elseif(isset($field["primary_key"])) {
			$item = "new Ext.form.Hidden({ name : '$fieldname', value: '".$model->$fieldname."' })\n";
		} else {
			if($lang) {
				$name = $fieldname."|".$lang;
				$label = $name;
			} else {
				$name = $fieldname;
			}

			$value = $model->get($fieldname, $lang);
			switch($field["type"]) {
				case "varchar":
					$size = ($field["size"] *  15);
					$size = $size  > 350 ? 350 : $size;
					$item = "new Ext.form.TextField({ width: '$size',fieldLabel : '$label', name : '$name', xtype:'textfield', value: '$value'".($renderTo ? ",renderTo: '$renderTo'" : "")."})\n";
					break;
				case "text":
					$item = "new Ext.form.HtmlEditor ({ fieldLabel : '$label', name : '$name', value: '". str_replace("'", "\'", str_replace(chr(13).chr(10), '\\n', $value))."',xtype:'htmleditor', width: 550, height: 300, autoExpandColumn: true, autoCreate: true".($renderTo ? ",renderTo: '$renderTo'" : "")."})\n";
					//, value: '". str_replace("'", "\'", str_replace(chr(13).chr(10), '\\n', $model->$fieldname))."'
					break;
				case "date":
					$item = "new Ext.form.DateField({ fieldLabel : '$label', name : '$name', xtype:'datefield', value: '$value', autoExpandColumn: true, autoCreate: true".($renderTo ? ",renderTo: '$renderTo'" : "")."})\n";
					break;

				case 'image':
					$action = 'deleteImages';


				case 'file':
					if(!$action) $action = 'deleteFiles';
					if($model->$fieldname) {
						if($field['type'] == 'image') {
							$url = $model->$fieldname->src("80x80");
							$html = "<img src=\"$url\">";
						} else {
							$url = $model->$fieldname->src("80x80");
							$html = "<img src=\"$url\">";
							$html .= $model->$fieldname->nombre;
						}
					}


					$item = "new Ext.form.Field({ fieldLabel : '$label', name : '$name', inputType: 'file'})";
					if($model->$fieldname) {
						$idimagen = $model->$fieldname->id;

						$item .= ",new Ext.Panel({ buttons : [
																new Ext.Button({
																		id: 'miboton',
																		iconCls:'icon_remove',
																		text: 'Eliminar',
																		handler: function () {
																				Ext.Ajax.request({ url: '/ajax/$model_name/$action/$idimagen' });
																				form.findById('panel-$fieldname').destroy(); }
																		})
															],
															width: 90,
															id: 'panel-$fieldname',
															buttonAlign: 'left',
															html: '$html',
															style: 'margin-left: 105px;'
												 })";
					}
/*
					$item = "new Ext.Panel({
								frame:true,
								width: '90',
								autoHeight:true,
								layout:'form',
								title:'$label',
								items : [ new Ext.form.DateField({ fieldLabel : '$label', name : '$fieldname', type:'file', value: '".$model->$fieldname."', autoExpandColumn: true}) ],
								buttons : [  new Ext.Button({
													id: 'show-button',
//													text: '',
													handler: function () {
														var dialog = new Ext.ux.UploadDialog.Dialog({
															url: '/ajax/$model_name/saveImages/$iditem/$fieldname',
															reset_on_hide: false,
															allow_close_on_upload: true,
															upload_autostart: true
														  });
//										  				  dialog.on('uploadsuccess', function(a) { alert(a)) }, this);
														  dialog.show(this.getEl());
														},
													iconCls : 'icon_upload'
												}),
												new Ext.Button({
													iconCls:'icon_remove',
//													text: '',
									//				handler: deleteImage
												})
									]

							})";

*/
					break;

				default:
					$item = "new Ext.form.TextField({ fieldLabel : '$label', name : '$name', xtype:'textfield', value: '$value', autoExpandColumn: true})";
			}
		}
		return $item;
	}
}
