<?php

/**
 * Clase para controlar el tiempo consumido
 * \ingroup utils
*/
class bench
{
    private $desde;
    private $a;

    function __construct()
    {
 		$this->timing_start = explode(' ', microtime());
    	$array  = gettimeofday ();

        $this->desde = (int)$array["sec"];
        $this->mcsdesde = (int)$array["usec"];
        return true;
    }

    function toHtml($txt = "Pagina renderizada en")
    {
//    	$parser = new html_parser(ob_get_contents());
//		$html_weight = ob_get_length();
//		$imgs = array_unique($parser->getImages());
//		$images_weight = $this->getFilesSize($imgs);
//		$total = $html_weight + $images_weight;
		$mem = memory_get_usage();
    	return "<div style='clear: both; border: 1px solid #ffaaaa; background-color: #fcc; color: #333; font-size: 8pt; padding: 1em; text-align: center; '>
			    	$txt <b>".$this->elapsed()."</b> seg. Mem Used: <b>".format::bytes($mem)."</b>
    			</div>";
    }


    function getFilesSize ($arr)
    {
    	$size = 0;
    	if(is_array($arr))
    	{
	    	foreach ($arr as $file)
	    	{
	    		$size += filesize($_SERVER['DOCUMENT_ROOT']."/".$file);
	    	}
    	}
    	return $size;
    }

    function display()
    {
    	echo $this->toHtml();
    }

    /**
     ** Escribe el tiempo consumido en pantalla.
     ** @returns void
     **/
    function elapsed() {
        $timing_stop = explode(' ', microtime());
        $rendertime = number_format((($timing_stop[0]+$timing_stop[1])-($this->timing_start[0]+$this->timing_start[1])), 4);
	return $rendertime;
    }
}

//$GLOBALS["__bench"] = new bench();