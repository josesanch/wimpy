$(document).ready(function() {

});



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
		$('#' + field + '_dialog').load(url);
		//, function() {
			//$('#' + modelName).ajaxForm({ target: '#' + field + '_dialog' });
		//});

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

/*****************************************************************************************************************
* FORMULARIOS
*****************************************************************************************************************/
var ModelForms = {
	init : function(form_id) {
		$('.datepicker').datepicker({changeMonth: true, changeYear: true}, $.datepicker.regional['es']);

	}
}

/*****************************************************************************************************************
* GRID DE RESULTADOS
*****************************************************************************************************************/

var GridResults = {
	id : "",
	modelName : "",

	init : function(modelName, sortable) {
		this.modelName = modelName
		$('tr.grid_row').bind('mouseover', function() {	$(this).addClass('grid_row_hover');	});
		$('tr.grid_row').bind('mouseout', function() {	$(this).removeClass('grid_row_hover'); });

		if (sortable) {
			$('#table_body').sortable({
				containment: 'parent',
				axis:	'y',
				update:
					function(e, ui) {
						orden = $(this).sortable('toArray').toString()
						$.get("/ajax/" + modelName  + "/reorderList/", { "orden" : orden});
					}
			});
		}
	}

}
/*****************************************************************************************************************
* GRID DE FICHEROS
*****************************************************************************************************************/

function GridFiles(field, model, vid, vtmp_upload) {
	var self = this;
	var fieldName = field
	var modelName = model
	var id = vid
	var tmp_upload = vtmp_upload



	this.load = function() {
		var self = this;
		$('#container-files-' + fieldName).load("/ajax/" + modelName + "/files/read/" + id + "/" + fieldName + "/?tmp_upload=" + tmp_upload, function(data) {
				$('#container-files-' + fieldName +' a.images-delete').bind('click', function (e) {
					arr = $(this).attr("id").split("-");
					tiditem = arr[0]; tmodel = arr[1]; tfield = arr[2]; tid = arr[3]; ttmp_upload = arr[4];
					if (confirm('¿Está seguro de querer realizar esta operación?')) {
						$.get("/ajax/" + tmodel + '/files/destroy/' + tid + "/" + tfield + "?tmp_upload=" + ttmp_upload, function() {
							self.load();
						});
					}
					return false;
				});

	            $('#container-files-' + fieldName + ' .editable').editable('/ajax/secciones/files/update');

				$("#container-files-" + fieldName + " ul").sortable({
					start: function(event, ui) {
						$('a.dataview-image').unbind('click');
					},

					stop : function(event, ui) {
						setTimeout(function(){ $("a.dataview-image").fancybox({ "hideOnContentClick" : true}); }, 250);
					},

					update: function(e, ui) {
						orden = $(this).sortable('toArray').toString()
						$.get("/ajax/" + modelName  + "/reorderImages/", { "orden" : orden});
					}
				});
				$("a.dataview-image").fancybox({ "hideOnContentClick" : true});
			}
		);
	}

	self.load();


	$("#uploadify_" + fieldName).uploadify({
		'uploader'      : '/resources/uploadify/uploadify.swf',
		'script'        : "/ajax/" + modelName + "/files/save/" + id + "/" + fieldName,
		'scriptData'  	: { 'tmp_upload' : tmp_upload },
		'cancelImg'     : '/resources/uploadify/cancel.png',
		'folder'        : 'uploads',
		'queueID'       : 'fileQueue_' + fieldName,
		'auto'          : true,
		'multi'         : true,
		'fileDataName' 	: fieldName,
		'onComplete' 	: function () {
			self.load();
		}
	});

	return this
}
