function delete_image(e) {
	arr = this.id.split("-");
	iditem = arr[0]; model = arr[1]; field = arr[2]; id = arr[3]; tmp_upload = arr[4];

	$("#loader").load("/ajax/" + model + '/files/destroy/' + id + "/" + field + "?tmp_upload=" + tmp_upload, function() {
		$('#container-files-' + field).load('/ajax/' + model + '/files/read/' + iditem + '/'  + field + '/?tmp_upload=' + tmp_upload);
	});
	return false;
}

/*****************************************************************************************************************
*    GEOLOCATION
*****************************************************************************************************************/
var html;
function mostrarDireccion(address) {
    if (geocoder) {
        geocoder.getLatLng(address,
            function(point) {
                if (!point) {
                    alert("La dirección buscada: '"+address+"' no ha sido encontrada.\n Prueba con otras palabras o arrastra el puntero por el mapa.");
                } else {
                    map.setCenter(point, 17);
                    marker.setLatLng(point);
                    platitud = marker.getPoint().lat();
                    plongitud = marker.getPoint().lng();

                    $("#latitud_hide").val(platitud)
                    $("#longitud_hide").val(plongitud)
            		mostrar_popup_direccion(map, point)
            }
          }
        );
	}
}




function mostrar_popup_direccion(overlay, latlng) {
	if (latlng) {
		geocoder.getLocations(latlng, function(addresses) {
		if(addresses.Status.code != 200) {
			alert("No conocemos esta dirección: " + latlng.toUrlValue() + ", mueve el puntero a otra zona.");
		} else {
		    address = addresses.Placemark[0];
		    document.form.direccion.value = address.address;
			html='<strong>'+address.address+'</strong><br>';
		    marker.openInfoWindowHtml('<h2>Dirección seleccionada:</h2>'+html+'<br />');
      }
    });
  }
}


function showGeolocationDialog(latitud, longitud) {
    window.open("/helpers/geolocation/search/" + latitud + "/" + longitud, "geolocation","menubar=0,resizable=1,width=500,height=600");
}

function actualizarFormulario(campo1, campo2) {
    window.opener.$("#" + campo1).val($("#latitud_hide").val());
    window.opener.$("#" + campo2).val($("#longitud_hide").val());
    window.close();
}


/*****************************************************************************************************************
*    AUTOCOMPLETE DIALOG
*****************************************************************************************************************/

function showModelDialog(model, field, parent)
{
	$("#" + field + "_dialog").load("/admin/" + model + "/list/no_layout=true/dialog=true/field=" + field  + "/parent=" + parent, function() {
			$("#" + field + "_dialog").dialog('open');
//			$("#usuarios").ajaxForm({ target: "#resultados" })
	}).dialog({
			width: '80%',
			position: ['', 20],
			title: model,
			resizable: true,
			bgiframe: true,
			modal: true,
			autoOpen: true,
			zIndex: 1
	});

}

function updateModelValueDialog(model, field, parent, value)
{
	$("#" + parent + " #" + field).val(value)
    $.get("/ajax/" + model + "/getValue/" + value + "/field=" + field, function(data) {
    	$("#" + parent + " #" + field + "_autocomplete").val(data);

    	if(typeof(autocompleteCallback) != "undefined")
    	    autocompleteCallback(model, field, parent, value);
    	$("#" + field + "_dialog").dialog("close")
    });
}

/*****************************************************************************************************************
*    SMS
*****************************************************************************************************************/
function sms_usuarios_dialog(id)
{
	$("#sms_usuarios_dialog").load("/admin/sms_usuarios/list/grupos_id=" + id + "/no_layout=true/dialog=true", function() {
//			$("#sms_usuarios").ajaxForm({ target: "#dialog-usuarios" })
			$("#sms_usuarios_dialog").dialog('open');
	}).dialog({
			width: '80%',
			position: ['', 80],
			title: "usuarios",
			resizable: true,
			bgiframe: true,
			modal: true,
			autoOpen: false,
			zIndex: 1
	});

}

function sms_usuarios_add(cursos_id, usuarios_id)
{
    $.get("/admin/sms_grupos/edit/add/" + cursos_id + "/" + usuarios_id + "/no_layout=true", function(data) {
		$("#listado-usuarios").html(data);
	});

}

function sms_usuarios_remove(cursos_id, usuarios_id)
{
    $.get("/admin/sms_grupos/edit/remove/" + cursos_id + "/" + usuarios_id + "/no_layout=true", function(data) {
		$("#listado-usuarios").html(data);
	});

}


function sms_usuarios_add_all(cursos_id)
{
    $.get("/admin/sms_grupos/addAll/" + cursos_id + "/" + $("#search").val() + "/no_layout=true", function(data) {
		$("#listado-usuarios").html(data);
	});

}

function sms_usuarios_envio_dialog(id)
{
	$("#sms_usuarios_dialog").load("/admin/sms_usuarios/list/grupos_id=" + id + "/no_layout=true/dialog=true/envios=true", function() {
//			$("#sms_usuarios").ajaxForm({ target: "#dialog-usuarios" })
			$("#sms_usuarios_dialog").dialog('open');
	}).dialog({
			width: '80%',
			position: ['', 80],
			title: "usuarios",
			resizable: true,
			bgiframe: true,
			modal: true,
			autoOpen: false,
			zIndex: 1
	});

}

function sms_usuarios_envio_add(usuarios_id, usuarios_nombre)
{
	$("#listado-usuarios").append("<li id='usuarios_" + usuarios_id + "'><input type='hidden' name='usuarios[]' value='" + usuarios_id + "'/>" + usuarios_nombre + "<a href=javascript:sms_usuarios_envios_delete('" + usuarios_id + "')>eliminar</a></li>");
}

function sms_usuarios_envios_delete(id)
{
    $("#usuarios_" + id).remove();
}
