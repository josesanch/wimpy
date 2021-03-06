<?
class html_extjs_grid extends html_object {

	private $model;
	public $page_size = 25;
	private $columns;
	public function __construct($model, $columns = '', $sql = '') {
			$this->__data .= init_extjs();
			$this->model = $model;
			$this->columns = $columns;
	}

	public function toHtml() {
		$model_name =  strtolower(get_class($this->model));
		$store_fields = "'".join("', '", array_keys($this->model->getFields()))."'";
		if(isset($this->model->grid_columns)) $selected_columns = split("[ ]?,[ ]?", $this->model->grid_columns);
		if($this->columns) $selected_columns = split("[ ]?,[ ]?", $this->columns);
		$columns = array();
		$store_columns = array();
		foreach($this->model->getFields() as $field => $attrs) {
			if(!isset($selected_columns) || in_array($field, $selected_columns)) {

				$columns[]= "{ id: '$field', header: '".ucfirst($attrs["label"])."', sortable: true, dataIndex: '$field' }";
				if(isset($attrs["belongs_to"])) {
					$table = substr($field, 0, -3);
					$store_columns[]= "(select nombre from $table where id=$field) as $field";
				} else {
					$store_columns[]= $field;
				}
			}
		}

		$columns = join(",", $columns);

		$store_fields_list = implode(",", $store_columns);
		if(in_array("nombre", array_keys($this->model->getFields()))) $autoExpandColumn = "autoExpandColumn: 'nombre',";
		$this->__data.= js_once("ext/searchfield");
		$this->__data.=<<<EOF
		<style>
		  .icon_add {
            background-image:url(/resources/icons/add.gif) !important;
        }
        .icon_edit {
            background-image:url(/resources/icons/edit.gif) !important;
        }
        .icon_remove {
            background-image:url(/resources/icons/delete.gif) !important;
        }

		</style>
<script>
    Ext.QuickTips.init();

	Ext.onReady( function() {
		var store = new Ext.data.JsonStore({
				url: '/ajax/$model_name/list',
				root: 'items',
				totalProperty: 'count',
				fields: [$store_fields],
				baseParams : { fields : '$store_fields_list' },
				remoteSort: true
			});


			// create the Grid

			var grid = new Ext.grid.GridPanel({
//			var grid = new Ext.grid.EditorGridPanel({
				store: store,
				columns: [ $columns ],
				stripeRows: true,
				$autoExpandColumn
				height: 600,
				width: 800,
				title:'$model_name',
//		        listeners: { click : grid_click_on },

				 bbar: new Ext.PagingToolbar({
						pageSize: $this->page_size,
						store: store,
				         displayInfo: true
				    }),
			iconCls:'icon-grid',
		 tbar: [
            	'Búsqueda: ', ' ', new Ext.app.SearchField({  store: store, width: 320 }), '-',
	            	{
	            		text : 'Añadir nuevo', tooltip : 'Crear un nuevo elemento',  iconCls : 'icon_add', listeners :
	            		{ click : function () { document.location='/admin/$model_name/edit'; }  }
	            	}, '-',
			        {
						text:'Editar',
					    tooltip:'Editar el elemento seleccionado',
					    iconCls:'icon_edit',
				        listeners: { click : function () {
//                	alert(grid.getSelectionModel().getSelected().get("id"))
                	document.location='/admin/$model_name/edit/' + grid.getSelectionModel().getSelected().get("id");
/*
					 win = new Ext.Window({
				        layout:'fit',
				        autoLoad : {url : '/admin/$model_name/edit/' + grid.getSelectionModel().getSelected().get("id") },
      				    width:500,
				        height:300,
				        closeAction:'hide'
				    });
		            win.show(this);
*/
                } }
    	    },'-',{
		        text:'Eliminar',
		        tooltip:'Eliminar el elemento seleccionado',
		        iconCls:'icon_remove',
		        listeners: { 	click : function (item) {
											if(grid.selModel.selections.keys.length > 0) {
												Ext.Msg.confirm('ALERTA!','Realmente desea eliminar el registro?', deleteRecord);
											} else {
												Ext.Msg.alert('ALERTA!','Seleccione un registro para eliminar');
											}
		        				 } }
        	}

        ]

			});


	store.load({params:{start:0, limit: $this->page_size}});
	 function deleteRecord(btn) {
		if (btn=='yes') {
			var selectedRow = grid.getSelectionModel().getSelected();
			if(selectedRow){
			    store.remove(selectedRow);
			    store.commitChanges();
			}
			}

		Ext.Ajax.request({
		    url: '/ajax/$model_name/delete',
		    params: {
		        id: selectedRow.get("id")
		    },
		    callback: function (options, success, response) {
		    	if (success) {}
		        else { Ext.MessageBox.alert('Intentelo nuevamente. [Q304]',response.responseText); }
		    },
		    failure:function(response,options) {
		        Ext.MessageBox.alert('Error','Problema eliminando datos');
		    },
		    success:function(response,options){
		        store.reload();
		    }
		});

	};
    grid.render('grid-$model_name');
});

		</script>
		<div id="grid-$model_name" style="width: 800px; margin: 10px auto auto auto; padding-left: 5%;"></div>
EOF;
		return parent::toHtml();
	}

}
?>
