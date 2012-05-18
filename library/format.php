<?
class format {

    function textCut($text, $size) {
        $str = wordwrap($text, $size, "||@||", 1);
        $str = explode("||@||", $str);
        $str = $str[0];
        $str .= strlen($str) != strlen($text) ? " ..." : "";
        return $str;
    }

    function txtToHtml($str)
    {
        return text::txtToHtml($str);
    }

    public static function date($str, $format = "d/m/Y")
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



class text
{

    // Deprecated
    public function wordCut($text, $size)
    {
        return text::cut($text, $size);
    }
    public function cut($text, $size)
    {
        $str = wordwrap( $text, $size, "||@||", 1);
        $str = explode("||@||", $str);
        $str = $str[0];
        $str .= strlen($str) != strlen($text) ? " ..." : "";
        return $str;
    }

    function txtToHtml($text)
    {	// Usamos \040 para denotar espacios que no contengan \n, ya que  \s = [\n\040\t]
        preg_match_all("/(^\040*)(.*)?$/m", $text, $match);
        for($i = 0; $i < count($match[0]); $i++)
        {
            $espacios = preg_replace("/\s/", "&nbsp;", $match[1][$i]);
            $texto =  nl2br($match[2][$i]);
            $txt .= $espacios.$texto;
        }
        return $txt;
    }

    /** @desc  a=b ..etc. a array */
    function pairStringToArray($attr)
    {

        if(is_array($attr)) return $attr;
        $arr = array();
        if(preg_match_all("{(['][^']*[']|[\"][^\"]*[\"]|[^,=\s]*)\s*(=\s*(['][^']*[']|[\"][^\"]*[\"]|[^,=\s]*))?}i", $attr, $regs))
        {
            for($i = 0; $i < count($regs[1]); $i++)
            {
                $value = preg_replace("{^(['\"])?(.*?)(['\"])?$}", "\\2", $regs[3][$i]);
                $value = substr($regs[2][$i], 0, 1) == "=" ? $value : null;
                $arr[trim($regs[1][$i])] = $value;
            }
        }
        return $arr;
    }
    public function capitalize($str)
    {
        return ucfirst(strtolower($str));
    }

}
