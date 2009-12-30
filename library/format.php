<?
class format {

	function textCut($str, $size) {
		return text::wordCut($str, $size);
	}

	function txtToHtml($str)
	{
		return text::txtToHtml($str);
	}

	public static function date($str, $format = "d-m-Y")
	{
		$time = strtotime($str);

		return date($format, $time);
	}


	function money($str, $dec = 2)
	{
		return number_format($str, $dec);
	}

	/**
	*	Devuelve el tamaño pasado en bytes formateado en mb, kb, gb, tb
	*	@param $size Tamaño en bytes.
	*/
	function bytes($size)
	{
		$kb  = 1024;
		$mb = 1048576;
		$gb  = 1073741824;
		$tb = 1073741824 * 1024;
		if($size < $kb) {
			return $size." B";
		} else if($size < $mb) {
			return round($size/$kb,2)." KB";
		}
		else if($size < $gb) {
			return round($size/$mb,2)." MB";
		}
		else if($size < $tb) {
			return round($size/$gb,2)." GB";
		}
		else {
			return round($size/ $tb ,2)." TB";
		}
	}
}
?>
