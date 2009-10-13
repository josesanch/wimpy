<?php

class helpers_geolocation
{
    public $view;
    function __construct ()
    {
        $this->view = new html_template(dirname(__FILE__)."/../views/helpers/geolocation/index.html");

    }

    public function search($vlatitud, $vlongitud)
    {
        $value = wmaps::getGeoIPPoint();
		$latitud = $value['latitude'];
		$longitud = $value['longitude'];

        $map = new wmaps();
        $map->key(web::instance()->googlekey)
            ->width(425)
            ->height(400)
            ->center($latitud, $longitud)
            ->zoom(10);


        $str .= "

            <fieldset class='admin_form geolocation'>
				<legend>Geolocalización</legend>
        <form name='form'>
                <label for='localiza'>
                    Búsqueda rápida:
                    <span class='info'> ciudad, calle, código postal</span>
                    <div class='inputs'>
                        <input id='latitud_hide' type='text' style='display: none;'/>
                        <input id='longitud_hide' type='text' style='display: none;'/>
                        <input id='localiza' class='valid' type='text' size='20' value='' name='localiza'/>
                        <input type='button' id='localizar' onclick='mostrarDireccion($(\"#localiza\").val())' value='¡Localizar!'/>
                         <input type='button' value='Enviar &raquo;' class='enviar' onclick=\"actualizarFormulario('$vlatitud', '$vlongitud');\"/>
                    </div>

			        <div class='comprueba'>
				        <b>Dirección:</b><br/><input name='direccion' id='direccion' value='' type='text' size='40'><br>

			        </div>
                </label>
        </form>
			".$map->display()."
		</fieldset>
		<script>
				var geocoder = new GClientGeocoder();
			    if(window.opener.$('#$vlatitud').val() && window.opener.$('#$vlongitud').val()) {
			        lat = Number(window.opener.$('#$vlatitud').val())
			        long = Number(window.opener.$('#$vlongitud').val())

                    point = new GLatLng(lat, long);
       			    var marker = new GMarker( point, {draggable: true});
       			    map.setCenter(point);
   		            mostrar_popup_direccion(map, point);
			    } else {
    			    var marker = new GMarker(map.getCenter() , {draggable: true});
			    }


	            GEvent.addListener(marker,'dragstart', function() {  map.closeInfoWindow();   });

	            GEvent.addListener(marker, 'dragend', function() {
		            platitud = marker.getPoint().lat();
		            plongitud = marker.getPoint().lng();
		            pointx = new GLatLng(marker.getPoint().lat(),marker.getPoint().lng());
		            mostrar_popup_direccion(map, pointx);
		            $('#latitud_hide').val(platitud)
		            $('#longitud_hide').val(plongitud)

            	});

            	map.addOverlay(marker);
            	marker.openInfoWindowHtml(\"<div style='width:200px'><h2>¡¡Arrástrame!!</h2><ul><li>Arrastra el pin hasta el sitio exacto</li> </ul></div>\");
        </script>

			";

        $this->view->content = $str;
    }

    public function __destruct()
    {
        $this->view->display();
    }
}
