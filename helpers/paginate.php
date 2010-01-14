<?
class helpers_paginate {

	static function toHtml($array, $no = array(), $texto = "Páginas: ", $mostrar_paginas = 10, $interPages = "&nbsp;", $varPages = 'page')
	{
		$html = "";

		$current_page_class = $varPages == "paginas" ? "pagina_actual" : "query_current_$varPages";
		$page_class = $varPages == "paginas" ? "paginas" : "query_$varPages";
		$nextPages = "&rsaquo;&rsaquo;";
		$previousPages = "&lsaquo;&lsaquo;";
		$arrowStyle = "paginas";
		if(!is_array($array)) return;
		$object = $array[0];

		$total_pages = ceil($object->total_results / $object->page_size);

		if($total_pages <= $mostrar_paginas) 	// Si todos los resultados caben en una página
       	{
			$desde = 1;
			$hasta = $total_pages;  // Se muestran todas las páginas.
		} else {
			if($object->current_page <= ($mostrar_paginas / 2)) {
		    	$desde = 1; $hasta = $mostrar_paginas;
			} else {
				$hasta = $object->current_page + floor($mostrar_paginas / 2);
				if($hasta > $total_pages) {
					$dif = $hasta - $total_pages;
	                $hasta = $total_pages;
				} else { $dif = 0; }

				$desde = ($object->current_page - floor($mostrar_paginas / 2)) - $dif;
			}
		}

		if($total_pages > 1) {

			$html.= $texto == "Páginas: " ? _("Páginas").": " : $texto;

			$url = "?".query_string($no);
			$url = eregi_replace("&?$varPages=[0-9]*", "", $url);
			$max = $mostrar_paginas  < $total_pages ? $mostrar_paginas  : $total_pages;
			if($desde > 1) $html.= "<a href='$url&$varPages=".($desde - 1)."' class='$arrowStyle'>$previousPages</a>&nbsp;";

			for($i = $desde; $i <= $hasta; $i++)
			{
				$inter = ($i != $hasta) ? $interPages : "";
				$html.= $i == $object->current_page ? "<span class='$current_page_class'><b>$i</b></span>$inter" : "<a href='".web::uri("/$varPages=$i")."' class='$page_class'>$i</a>$inter";
			}

			if($hasta < $total_pages) $html.="&nbsp;<a href='".web::uri("/$varPages=".$i++)."' class='$arrowStyle'>$nextPages</a>";
		}

		return $html;
	}

	public static function toArray($array, $mostrarPaginas = 10, $varPages = 'page')
	{
		if(!is_array($array)) return array();
		$object = $array[0];
		$arrResults = array();

		$total_pages = ceil($object->total_results / $object->page_size);

		if($total_pages <= $mostrarPaginas) 	// Si todos los resultados caben en una página
       	{
			$desde = 1;
			$hasta = $total_pages;  // Se muestran todas las páginas.
		} else {
			if($object->current_page <= ($mostrarPaginas / 2)) {
		    	$desde = 1; $hasta = $mostrarPaginas;
			} else {
				$hasta = $object->current_page + floor($mostrarPaginas / 2);
				if($hasta > $total_pages) {
					$dif = $hasta - $total_pages;
	                $hasta = $total_pages;
				} else { $dif = 0; }

				$desde = ($object->current_page - floor($mostrarPaginas / 2)) - $dif;
			}
		}

		if($total_pages > 1) {

			$max = $mostrarPaginas  < $total_pages ? $mostrarPaginas : $total_pages;

			if($desde > 1)
				$arrResults[]= array("prev", web::uri("/$varPages=".$desde - 1));

			for ($i = $desde; $i <= $hasta; $i++) {
				$arrResults[]= array($i, web::uri("/$varPages=$i"), ($i == $object->current_page) ? "selected" : "");
			}

			if($desde > 1)
				$arrResults[]= array("next", web::uri("/$varPages=".$i++));

		}
		return $arrResults;
	}
}
