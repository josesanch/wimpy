$(document).ready(function() {

});

function mouseOverResults() {
	$('tr.grid_row').bind('mouseover', function() {
		$(this).addClass('grid_row_hover');
	});

	$('tr.grid_row').bind('mouseout', function() {
		$(this).removeClass('grid_row_hover');
	});
}


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
			//$("#usuarios").ajaxForm({ target: "#resultados" })
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


function goUrl(url, field, modelName)
{
	if(typeof(field) != "undefined") {
		$('#' + field + '_dialog').load(url, function() {
			$('#' + modelName).ajaxForm({ target: '#' + field + '_dialog' });
		});

	} else {
		document.location = url;
	}
}



function confirmGoUrl(url, field, modelName)
{
	if (confirm('¿Está seguro de querer realizar esta operación?')) {
		goUrl(url, field, modelName);
	}
}


/*****************************************************************************************************************
* FUNCIONES DE VALIDACIÓN
*****************************************************************************************************************/

function check_cif(valor)
{
	if(!valor) return true;
	texto = valor.replace(/-/g, "");

    var pares = 0;
    var impares = 0;
    var suma;
    var ultima;
    var unumero;
    var uletra = new Array("J", "A", "B", "C", "D", "E", "F", "G", "H", "I");
    var xxx;

    texto = texto.toUpperCase();

    var regular = new RegExp(/^[ABCDEFGHKLMNPQS]\d\d\d\d\d\d\d[0-9,A-J]$/g);
     if (!regular.exec(texto)) return false || check_nif(valor);

     ultima = texto.substr(8,1);

     for (var cont = 1 ; cont < 7 ; cont ++) {
         xxx = (2 * parseInt(texto.substr(cont++,1))).toString() + "0";
         impares += parseInt(xxx.substr(0,1)) + parseInt(xxx.substr(1,1));
         pares += parseInt(texto.substr(cont,1));
     }
     xxx = (2 * parseInt(texto.substr(cont,1))).toString() + "0";
     impares += parseInt(xxx.substr(0,1)) + parseInt(xxx.substr(1,1));

     suma = (pares + impares).toString();
     unumero = parseInt(suma.substr(suma.length - 1, 1));
     unumero = (10 - unumero).toString();
     if(unumero == 10) unumero = 0;

     if ((ultima == unumero) || (ultima == uletra[unumero]))
         return true;
     else
         return false || check_nif(valor);
}

//la funcion "IsNIF(YourNIF)" chequea si "YourNIF" es un DNI valido
//La variable "YourNIF" es una cadena de caracteres
function check_nif(YourNIF)
{
	YourNIF = YourNIF.replace(/-/g, "");
	if (YourNIF.length != 9) return false //Si la longitud de "YourNIF" es menor que 9 devuelve falso
	else if (!IsUnsignedInteger(YourNIF.substring(0, 8))) return false //Si los ocho primeros digitos no forman un numero entero sin signo valido devuelve falso
	else if (!IsChar(YourNIF.substring(8, 9))) return false //Si el ultimo digito no es una letra valida devuelve falso
	else {
		var ControlValue = 0 //Control de calculos segun el criterio de correccion
		var NIFCharIndex = 0 //Almacenara la posicion de la letra correpondiente a la parte numerica del DNI con respecto al array "NIFChars"
		//El siguiente array "NIFChars" contiene las letras de DNI ordenadas segun el criterio de correccion
		var NIFChars = new Array('T', 'R', 'W', 'A', 'G', 'M', 'Y', 'F', 'P', 'D', 'X', 'B', 'N', 'J', 'Z', 'S', 'Q', 'V', 'H', 'L', 'C', 'K', 'E')
		var NIFNumber = YourNIF.substring(0, 8) //Almacenanos la parte numerica del DNI en "NIFNumber"
		var NIFChar = YourNIF.substring(8, 9) //Almacenamos la letra del DNI en "NIFChar"
		NIFChar = NIFChar.toUpperCase() //Pasamos la letra del DNI a mayusculas por si acaso estaba en minusculas
		//Los siguientes 4 calculos sirven para calcular la posicion de la letra correspondiente al la parte numerica del DNI "NIFNumber" en en array "NIFChars"
		ControlValue = NIFNumber / NIFChars.length
		ControlValue = Math.floor(ControlValue);
		ControlValue = ControlValue * NIFChars.length
		NIFCharIndex = NIFNumber - ControlValue
		return (NIFChar == NIFChars[NIFCharIndex]); //Si la letra coincide con la letra dada devuelve verdadero si no devuelve falso
	}
}


function Message() { }

Message.show = function (msg) {
	$("#alert-messages").html(msg).show();
	$("#alert-messages").bind("click", function() {	Message.hide(); });
}

Message.hide = function () {
	$("#alert-messages").hide();
}
