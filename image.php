<?php
define("ERR_IMAGE_FILE_NOT_EXISTS", 1);

/**
 * Objeto para hacer .
 *
 * @author    Jose Sanchez Moreno
 * @copyright Oxigenow eSolutions
 * @version 1.0
 * \ingroup utils
 */
class image
{
	public $img;
	private $quality = 95;
	private $mode = "resample";
	public $file;
	public $type = "jpg";

	function __construct($fileOrResource)
	{
		if(is_resource($fileOrResource)) {
			$this->img = $fileOrResource;
		} else {
			$this->__open($fileOrResource);
		}
	}

	/**
	* @access private
	*/
	private function __open($file)
	{
		$this->file = $file;
		$f = new file($file);

//		if(!$f->exists()) return new error("El archivo de imagen no existe",  ERR_IMAGE_FILE_NOT_EXISTS);

//		$this->debug("Creando imagen de: $file");
		//$type = strtolower($f->getExtension());
		list($ancho, $altura, $tipo, $atr) = getimagesize($file);
		$ftype_array = array(
		    1 => "gif", 2 => "jpg", 3 => "png", 4 => "swf",
		    5 => "psd", 6 => "bmp", 7 => "tiff", 8 => "tiff"
        );
        $type =  $ftype_array[$tipo];
		//echo "$file --> $type";
		switch($type)
		{
			case "jpg":
				$type = "jpeg";
				break;
			case "gif":
			case "png":
			case "psd":

			case "tiff":
				break;
			default:
				return false;
//			   return new error("Type not known",  ERR_IMAGE_FILE_NOT_EXISTS);

		}
		$function = "imagecreatefrom$type";
		$this->img = $function($file);
		imageAlphaBlending($this->img, false);
		imageSaveAlpha($this->img, true);
	}

	function getWidth()
	{
		return imagesx($this->img);
	}

	function getHeight()
	{
		return imagesy($this->img);
	}

	function thumbnail($width, $height)
	{
		$dest_image = imagecreatetruecolor($width, $height);
		imagealphablending($dest_image,false);
 		imagesavealpha($dest_image, true);
		imagecopyresampled(
		    $dest_image,
		    $this->img,
		    0, 0, 0, 0,
		    $width,
		    $height,
		    $this->getWidth(),
		    $this->getHeight()
        );
		return new image($dest_image);

	}

	function thumbnailInABox($width, $height)
	{
   		bcscale(5);
   		$distx = bcdiv($this->getWidth(), $width);
   		$disty = bcdiv($this->getHeight(), $height);

   		$div = ($distx > $disty) ? $distx : $disty;


   		$width = abs(bcdiv($this->getWidth(), $div));
		$height = abs(bcdiv($this->getHeight(), $div));

		return $this->thumbnail($width, $height);
	}



	function thumbnailOutABox($width, $height, $ycenter = 1, $xcenter = 1)
	{
		// Si center vale 1 recorta en el centro .. si recorta con el y  arriba.
		bcscale(5);
   		$distx = bcdiv($this->getWidth(), $width);
   		$disty = bcdiv($this->getHeight(), $height);

   		$div = ($distx < $disty) ? $distx : $disty;	// La menor distancia.


   		$_width = abs(bcdiv($this->getWidth(), $div));
		$_height = abs(bcdiv($this->getHeight(), $div));

		$thumb = $this->thumbnail($_width, $_height);


		$x = $this->getPositionToCut("x", $xcenter, $_width, $width);
		$y = $this->getPositionToCut("y", $ycenter, $_height, $height);
		//echo "$x, $y  - $width, $height - ".$thumb->getWidth().", ".$thumb->getHeight()."<br>";
		return $thumb->cut($x, $y, $width, $height);

	}

	private function getPositionToCut($type = "x", $pos, $size, $size2)
	{

		switch($pos)
		{
			case 0:
				$x = 0;
				break;
			case 1:
				$x = ($size / 2) - ($size2 / 2) ;
				break;
			case 2:
				$x = ($size / 2);
				break;
		}
		return $x;
	}

	function cut($x, $y, $width, $height)
	{
		$dest_image = imagecreatetruecolor($width, $height);
		imagealphablending($dest_image,false);
 		imagesavealpha($dest_image, true);
		imagecopy($dest_image, $this->img, 0, 0, $x, $y, $width, $height);
		return new image($dest_image);
	}

	function display($type = null, $quality = null)
	{
		$this->save(null, $type, $quality);
	}

	function save($url = null, $type = null, $quality = null)
	{

		$type = isset($type) ? $type : $this->type;
		$type = $type == "jpg"  ?  "jpeg" : $type;
		if(!$url) header("Content-type: image/$type");
		$quality = isset($quality) ? $quality : $this->quality;
		if($type == "png") $quality = null;
		$function  = "image$type";
		$function($this->img, $url, $quality);
	}

	function setQuality($quality)
	{
		$this->quality = $quality;
	}

	// Escribe un texto sobre la imagen
	function putText($str, $x, $y, $color= 10)
	{
		imagestring($this->img, 6, $x, $y, $str, $color);
	}

	// Copia una imagen en otra en la posición especificada
	function putImage($img, $x, $y, $alfa = null)
	{
		if(isset($alfa)) ImageAlphaBlending($this->img, true);
		imagecopy($this->img, $img->img, $x, $y, 0, 0, $img->getWidth(), $img->getHeight());
	}
	// Implementación del cache para el manipulado de imagenes
}
