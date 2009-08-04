<?php

/**
* @class        Wimpy Maps 2.0
* @author       José Sánchez <jose@o2w.es>
*/


class WMaps {

    private $width 	= 100;
    private $height = 100;
	private $centerMap = array();
    private $apiKey = "";    public $show_control = true;
    public $show_type = true;
    public $control_type = 'small';
    public $zoomLevel = 4;
    private $points = array();
    private $id = "map";
	private $map_type = "G_NORMAL_MAP";
	private $icons = array();
	public $clustered = false;
	public $fitToMarkers = false;


	public function __call($method, $args) {
		if(!in_array($method, array("width", "height", "key", "show_control", "zoom", "id", "clustered", "fitMapToMarkers", "map_type"))) return;

		if (empty($args)) {
			return $this->$method;
		} else {
			switch($method) {
				case 'width':
				case 'height':
					$args[0] = is_numeric($args[0])	? $args[0]."px" : $args[0];

				default:
					$this->$method = $args[0];
			}

		}


		return $this;
	}
	public function center($lat, $long) 	{ $this->centerMap = array($lat, $long); return $this; }

	public function setClustered($set, $title =  'Pulsa para ver %count marcas' ) {
		$this->clustered = $set;
		$this->clustered_title = $title;
		return $this;
	}


	public function &add($lat, $long, $title, $message, $icon = null, $callback = null, $id = null) {
		$point = new MapsPoint($lat, $long, $title, $message, $id, $icon, $callback);
		$this->points[]= &$point;
		return $point;
    }


	public function addIcon($name, $url) {
		$icon = new MapsIcon($name, $url);
		if($url) $icon->image = "\"$url\"";
		$this->icons[$name]= $icon;
		return $icon;
	}

	public function initialize() {
		$str = "
<div id='{$this->id}' style='width: $this->width; height: $this->height;'></div>
<script src='http://maps.google.com/maps?file=api&amp;v=2.x&amp;key=".$this->key."' type='text/javascript'></script>
<script type='text/javascript'>
	var {$this->id}; var myPano; var svOverlay;

    function initialize_GoogleMAPS() {
		if (GBrowserIsCompatible()) {
		    myPano = new GStreetviewPanorama(document.getElementById('streetview'));
			GEvent.addListener(myPano, 'error', handleNoFlash);
	  		{$this->id} = new GMap2(document.getElementById('$this->id'));
			{$this->id}.enableScrollWheelZoom();
			";

		if ($this->show_control) {
			if ($this->control_type == 'small') { $str.= "map.addControl(new GSmallMapControl());\n"; }
          	if ($this->control_type == 'large') { $str.= "map.addControl(new GLargeMapControl());\n"; }
		}

		if($this->show_type) $str.= "map.addControl(new GMapTypeControl());\n";
      	if($this->centerMap) $str.= "map.setCenter(new GLatLng(".$this->centerMap[0].",".$this->centerMap[1]."), $this->zoomLevel);";
      	if($this->map_type != "G_NORMAL_MAP") $str.= "map.setMapType($this->map_type);";

	$str .= "
		}
	}
	function handleNoFlash(errorCode) { if (errorCode == FLASH_UNAVAILABLE) { alert('Error: Flash doesn\'t appear to be supported by your browser'); return; } }
	initialize_GoogleMAPS();
</script>";
	   	return $str;
	}



	function geoPoints() {

		$str = "
<script type='text/javascript'>
			var marker, markersArray=[];";


		foreach($this->icons as $icon) $str .= $icon->js();
		foreach($this->points as $point) {
			$data = $point->js($this);
			$str .= $data[0];
			$events .= $data[1];
		}

		// Esto es para clustermarker
       	if($this->clustered)  {
       		$str .= "var cluster=new ClusterMarker(map, { markers:markersArray, clusterMarkerTitle : '$this->clustered_title' } );";
	       	$str .= "cluster.fitMapToMarkers();";
	    }
	    // Ponemos los eventos al final para que los puntos se cargen antes.
	    $str .= $events;
		$str .= "</script>";
		return $str;
	}



	public static function getGeoIPPoint() {
		if(web::instance()->dbgeoip) {
			$data = web::instance()->dbgeoip->query("SELECT * FROM ip_group_city where ip_start <= INET_ATON('".$_SERVER['REMOTE_ADDR']."') order by ip_start desc limit 1")->fetch();
			if($date["latitude"] && $data["longitude"]) return $data;
		}
		return array("city" => "Mula", "region_name" => "Murcia", "country_name" => "España", "latitude" =>  38.035112, "longitude" => -1.539459);
    }

	public function display() { return $this->initialize().$this->geoPoints(); }

}


class MapsPoint {

	private $latitude, $longitude, $name, $id, $icon, $message, $callback, $map_id, $count;

	public function __construct($latitude, $longitude, $name, $message = null, $id = null, $icon = null, $callback = null) {
		static $count = 0;
		$this->count = $count++;
		$this->latitude = $latitude;
		$this->longitude = $longitude;
		$this->name = $name;
		$this->message = $message;
		$this->icon = $icon;
		$this->id = $id;
		if($id) $this->count = $id;
		$this->callback = $callback;
	}


	public function js($map) {
			$map_id = $map->id();

			$options = array("title : '".str_replace(array('"'), '\"', str_replace(array("\n","\r","\n\r","\r\n","\n\g", "\g"), "", $this->name))."'");
			if($this->icon) $options[]= "icon : icon_{$this->icon}";

			$options = "{ ".implode(", ", $options)." }";

			$str = "\nvar marker{$this->count} = new GMarker(new GLatLng($this->latitude, $this->longitude), ".($options).");markersArray.push(marker{$this->count});";
			if(!$map->clustered) $str .= "{$map_id}.addOverlay(marker{$this->count});";

			// Mostramos el mensaje al pulsar
			if($this->message) $events .= "\nGEvent.addListener(marker{$this->count}, 'click', function() { ".$this->getMessage()." } );";

			// procesamos los callbacks
			foreach($this->callback as $event => $function)
				$events .= "\nGEvent.addListener(marker{$this->count}, '$event', function() { $function(marker{$this->count}, '$this->id'); } );";

			return array($str, $events);
	}

	private function getMessage() {
		if(is_array($this->message)) {
			// Creamos las pestañas
			$tabs = array();
			foreach($this->message as $titulo => $contenido) {
				$message = str_replace(array('"'), "'", str_replace(array("\n","\r","\n\r","\r\n","\n\g", "\g"), "", $contenido));
				$tabs[]= "\n	new GInfoWindowTab(\"$titulo\",\"".$message."\")";
			}
			$str.= "marker".$this->count.".openInfoWindowTabsHtml([".implode(",", $tabs)."]);\n";
		} else {
			$message = str_replace(array('"'), '\"', str_replace(array("\n","\r","\n\r","\r\n","\n\g", "\g"), "", $this->message));
			$str = "marker".$this->count.".openInfoWindowHtml(\"".$message."\");\n";
		}
		return $str;

	}

	public function __call($method, $args) {
		if (empty($args))
			return $this->$method;
		else
			$this->$method = $args[0];

		return $this;
	}
}

class MapsIcon {
	public $image, $shadow, $iconSize, $shadowSize, $iconArchor;

	public function __construct($name, $image, $shadow, $size, $ssize) {
		$this->name = $name;
		$this->image = $image;
		$this->shadow = $shadow;
		$this->iconSize = $size;
		$this->shadowSize = $ssize;
	}

	public function __call($method, $args) {
		if (empty($args))
			return $this->$method;
		else
			$this->$method = $args[0];

		return $this;
	}

	public function js() {

		$str = "var icon_{$this->name} = new GIcon();\n";
		foreach(get_object_vars($this) as $item => $value) {
			if($value && $item != 'name') $str .= "icon_{$this->name}.$item = $value;";
		}
		return $str;
	}
}

?>
