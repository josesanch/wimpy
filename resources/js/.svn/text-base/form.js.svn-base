// (c) Jose Sanchez Moreno - Oxigenow E-Solutions
// www.oxigenow.com

var campoRojo = "";	// En esta variable almacenamos el campo que actualmente est? rojo.
// Comprueba que todos los campos que se le pasan como par?metros.

function form(formulario) {

	// Expresiones regulares para controlar los valores del formulario.
	var f = formulario;
	var re_numerico = /^\d*$/;
	var re_float = /^\d*(\.\d*)?$/;
	var re_telefono = /^[\d\s]*$/;
	var re_fecha    = /^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$|^$/;
	var re_email 	 = /^[\w\-\_\.\d]+@[\w\-\_\.\d]+\.[\w\-\_\.\d]+$|^$/;
	var re_image = /(\.(gif|jpg)|^)$/i;
	var fechas = new Array();
	var valido = true;

	for(var i = 0; i < f.elements.length; i++) {
	 	var campo = f.elements[i];
		var check_value = campo.getAttribute("CHECKS");
		if(!check_value) check_value = campo.getAttribute("ID");
		if(!check_value) check_value = campo.getAttribute("class");
		if(check_value == null) continue;
		var checks = check_value.split(",")
		for(var pos = 0; pos < checks.length; pos++) {
			var check = checks[pos].toLowerCase();
		 	if (check == "not null" || check == "required") { valido = campo.value != ""; msg = "Debe introducir un valor para el campo."; }
			if (check == "number")	{ valido = valido & re_numerico.test(campo.value); msg = "El valor del campo debe ser numérico.";}
			if (check == "email")	{ valido = valido & re_email.test(campo.value); msg = "No es un formato de e-mail.";}
		 	if (check == "date")		{ fechas.push(campo); valido = valido & re_fecha.test(campo.value); msg = "El formato de fecha no es válido\nEl formato correcto es dd/mm/yy.";}
		 	if (check == "float")   { valido = valido & re_float.test(campo.value); msg = "El campo es un campo numerico con decimales. Debe expresarlo de la forma 123.45";}
		 	if (check == "image")  { valido = valido & re_image.test(campo.value); msg = "El fichero debe ser una imagen en jpg o gif.";}
		 	if (check == "ccc") { valido = valido & check_ccc(campo.value); msg = "El Código de cuenta cliente es no es correcto.";}
		 	if (check == "cif")  { valido = valido & check_cif(campo.value); msg = "El NIF o CIF es no es correcto.";}
		 	if (check == "time")    { valido = valido & time_blur(campo); }
		 	if (check == "checked")    { valido = valido & campo.checked;  msg = "El campo es obligatorio."; }

			if(campo.getAttribute("msg")) msg = campo.getAttribute("msg");

			 	/*if (func == "telefono") 	{ valido = re_telefono.test(campo.value);	}*/
		 	if (!valido) {
				alert(msg);
				setFocus(campo);
				return false;
		 	}

		}
	}
	for(var i = 0; i < fechas.length; i++) {
		if(re_fecha.exec(fechas[i].value) && fechas[i].value != "") {
			fechas[i].value = RegExp.$3 + "-" + RegExp.$2 + "-" + RegExp.$1;
		}
	}
	return true;
}
function form_submit(f)
{
	if(form(f)) f.submit();
}


function setFocus(campo) {
	campo.style.borderColor="red";
	campo.style.borderWidth="2px";
	campo.style.borderStyle="dashed";
	campoRojo = campo;
	campo.focus();
}

function quitarRojo() {
	if(campoRojo) {
		campoRojo.style.borderColor="";
		campoRojo.style.borderStyle="inset";
		campoRojo ="";
		//if(typeof(cerrarMensaje) == "function") { cerrarMensaje(); }

	}
}

function verTecla(e) {
	if(window.event) {
		if(window.event.srcElement == campoRojo) quitarRojo();
	} else {
		if(e.target == campoRojo) quitarRojo();
	}
}

function file_edit(id)
{
	document.getElementById("file_editar_" + id).style.visibility = "hidden";
	document.getElementById("file_descripcion_" + id).innerHTML = "<input type='text' name='file_descripcion_" + id + "' value='" + document.getElementById("file_descripcion_" + id).innerHTML + "'>";
}



function is_time_ok(value)
{
	var re_hora    = /^(\d{1,2}):(\d{2})(:\d{2})?$|^$/;
	if(re_hora.exec(value))
	{
		if((RegExp.$1 >=0 && RegExp.$1 < 24) && (RegExp.$2 >=0 && RegExp.$2 < 60))
		{
		//	if(RegExp.$3 && (RegExp.$3 >=0 && RegExp.$3 < 60)) return true;
			return true;
		}
	}
	return false;

}

function time_blur(hora)
{
		if(hora.value.length > 4)
			hora_value = hora.value.replace(/(\d{2})(\d{2})$/, ":$1:$2");
		else
			hora_value = hora.value.replace(/(\d{2})$/, ":$1");

		// Ahora comprobamos la hora.
		if(is_time_ok(hora_value))  {
			hora.value = hora_value;
		} else {
			alert("El formato de la hora no es correcta");
			setFocus(hora);
			return false;
		}
		return true;
}

function time_focus(hora)
{
		hora.value = hora.value.replace(/:/g, "");
		hora.select();
}

function check_ccc(ccc)
{
	if(!ccc) return true;
	valores = new Array(1, 2, 4, 8, 5, 10, 9, 7, 3, 6);
	ccc = ccc.replace(/-/g, "");

	banco = ccc.substr(0, 4); oficina = ccc.substr(4, 4); dc = ccc.substr(8, 2); cuenta = ccc.substr(10, 10);
	validar = banco +""+ oficina;

	control_1 = 0;	control_2 = 0
	for (i=0; i < 8; i++) control_1 += parseInt(validar.charAt(i)) * valores[i+2];
	for (i=0; i < 10; i++) control_2 += parseInt(cuenta.charAt(i)) * valores[i];

    control_1 = 11 - (control_1 % 11);     control_2 = 11 - (control_2 % 11);
	if (control_1 == 11) control_1 = 0;	else if (control_1 == 10) control_1 = 1;
	if (control_2 == 11) control_2 = 0;	else if (control_2 == 10) control_2 = 1;
	if(dc.charAt(0) != control_1 || dc.charAt(1) != control_2) return false;

	return true;
}

function check_cif(valor)
{
	if(!valor) return true;
	valor = valor.replace(/-/g, "");
	if (valor.length != 9) return false;
	letra_cif = valor.substr(0, 1).toUpperCase()
	// Vemos si es un NIF o Un CIF
	if(letra_cif == "X" || !isNaN(parseInt(letra_cif))) 	// Es un NIF
	{
		if (letra_cif  == "X") { valor = "0" + valor.substr(1); }
		letra = valor.substr(valor.length - 1 , 1).toUpperCase();
		valor = valor.substr(0, valor.length - 1);
		return ( "TRWAGMYFPDXBNJZSQVHLCKE".substr((valor % 23), 1) == letra);
	} else {	// Es un CIF
		if ("ABCDEFGHPQSKLMX".indexOf(letra_cif) == -1) return false;
		numero_control = valor.substr(8,1);
		cif = valor.substr(1,7);
		suma = (cif.substr(1, 1) * 1) + (cif.substr(3,1) * 1) + (cif.substr(5,1) * 1);
		for (i=0; i<=6 ; i = i + 2)
		{
			numero = cif.substr(i,1) * 2;
			suma = suma + numero % 10;
			suma = suma + (Math.floor(numero / 10));
		}
		control = 10 - (suma % 10);
		if (letra_cif == "P") return ( numero_control == String.fromCharCode((64 + control)));
		if (control == 10) control = 0;
		return ( numero_control == control );
	}
}

document.onkeydown = verTecla;
