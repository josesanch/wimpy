<?php

class html_template_filters
{
	/** Deprecated */
	public function textCut($text, $size = null)
	{
		if(!$size) return $text;
		$str = wordwrap( $text, $size, "||@||", 1);
		$str = explode("||@||", $str);
		$str = $str[0];
		$str .= strlen($str) != strlen($text) ? " ..." : "";
		return $str;

	}
	public function nl2br($text)
	{
		return nl2br($text);
	}


	function nobr($text)
	{
		return preg_replace("/<br[^>]*>/", "d ", $text);
	}

	public function replace($text, $replace, $for)
	{
		return str_replace($replace, $for, $text);
	}
	public function money($text, $decimal = 0, $sepdecimales = ',', $separador = '.')
	{
		return number_format($text, $decimal, $sepdecimales, $separador);
	}
	public function capitalize($str)
	{
		return ucfirst(strtolower($str));
	}

	public function trans($str)
	{
		return _t($str);
	}

	public function date($date, $format = '%d/%m/%y')
	{
		return strftime($format, strtotime($date));
	}

	public function bytes($size)
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

	public function url($url) {
		return convert_to_url($url);
	}

	public function seconds($time) {
		$diff = $time;
		$daysDiff = floor($diff/60/60/24);
		$diff -= $daysDiff*60*60*24;
		$hrsDiff = floor($diff/60/60);
		$diff -= $hrsDiff*60*60;
		$minsDiff = floor($diff/60);
		$diff -= $minsDiff*60;
		$secsDiff = $diff;
		$str = "";
		$arr = array();
		if($daysDiff) $str.= $daysDiff.' días ';
		if($hrsDiff) $arr[] = $hrsDiff < 10 ? "0".$hrsDiff : $hrsDiff;
		$arr[]= $minsDiff < 10 ? "0".$minsDiff : $minsDiff;
		$arr[]= $secsDiff < 10 ? "0".$secsDiff : $secsDiff;
		$str .= join(":", $arr);
		return $str;

	}

    public function striptags($texto)
    {
        return (strip_tags($texto));
    }
}

?>
