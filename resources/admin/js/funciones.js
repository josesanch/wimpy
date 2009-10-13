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
		    document.form.direccion.value=address.address
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
