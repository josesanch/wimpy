<?php

class thumb
{
    const NORMAL = 1;
    const CROP = 2;
    const NO_RESIZE_SMALLER = 4;
}

class helpers_files_thumbnail
{
    private $_img;
    private $_file;
    private $_fileName;
    private $_imgId;
    private $_rootThumbnails = "/cached";
    private $_output = "jpg";
    private $_filters = array();
    private $_height;
    private $_width;
    private $_pngDepth = 8;
    function __construct ($file)
    {
        $this->_file = $file;
		if (is_string($file)) {
			$info = pathinfo($file);
			$this->_fileName = $file;
			$this->_imgId = $info["filename"];
		} else {
			$this->_fileName = $file->phisical();
			if($file->id) {
				$this->_imgId = $file->id;
			} else {
				$this->_imgId = md5($file->phisical());
			}

		}
    }

	public function addFilter($filter)
	{
		$this->_filters[]= $filter;
	}
	public function _checkHeight()
	{
		$this->_width = $this->_img->getImageWidth();
		$this->_height = $this->_img->getImageHeight();
	}

	private function _isSmaller($width, $height)
	{
		return ($this->_width < $width && $this->_height < $height );
	}

    public function getUrl($width, $height, $operation = thumb::NORMAL)
    {
        $info = pathinfo($this->_fileName);
        $url = $this->_getCachedUrl($width, $height, $operation);
		if(file_exists($_SERVER["DOCUMENT_ROOT"].$url)) return $url;

        $this->_img = new Imagick($this->_fileName.'[0]');
        $this->_checkHeight();
		$this->_img->setImageFormat($this->_output);

        if ($this->_output == "png") {
            $this->_img->setImageDepth($this->_pngDepth);
        }
        switch ($operation) {

            case thumb::CROP:
            case "OUTABOX":
				$this->_cropImage($width, $height);
                break;

            case thumb::NORMAL:
            case "INABOX":
            case "THUMB":
            default:
				if (!(($operation & thumb::NO_RESIZE_SMALLER) == thumb::NO_RESIZE_SMALLER && $this->_isSmaller($width, $height))) {

					if ($width > $height)
						$this->_img->thumbnailImage($width, null, 0);
					else
						$this->_img->thumbnailImage(null, $height, 0);

//						$this->_img->thumbnailImage($width, $height, true);
				}
					//$this->_img->thumbnailImage($width, $height, true); // funciona correctamente en la versión 3.0
                break;
        }

        $this->_applyFilters();
        $this->_saveImage($url);
        return $url;
    }

    public function setOutput($output)
    {
        $this->_output = $output;
    }

    private function _getCachedUrl($width, $height, $operation = thumb::NORMAL)
    {
		$info = pathinfo($this->_file->nombre);
		$cachedUrl = $this->_rootThumbnails."/".$this->_imgId."-{$width}x{$height}";

		switch ($operation) {
            case thumb::CROP:
            case "OUTABOX":
				$cachedUrl.= "-crop-";
				break;
		}
		if (($operation & thumb::NO_RESIZE_SMALLER) == thumb::NO_RESIZE_SMALLER) $cachedUrl.= "-noresize-";
		$strFiltros = array();
		// Process the filters
		foreach ($this->_filters as $filtro) {
			$operation = array_shift($filtro);

			$args = array();
			foreach ($filtro as $arg) {
				if (is_a($arg, "Imagick"))
					$args[]= basename($arg->getImageFileName());
				else
					$args[]= $arg;
			}

			$strFiltros[]= $operation."-".implode("-", $args);
		}
		$cachedUrl.= implode("-", $strFiltros);

		$cachedUrl.= $info["filename"];
		return $this->_convertUrl($cachedUrl).".".$this->_output;;
	}

	private function _cropImage($width, $height)
	{
		// Si center vale 1 recorta en el centro .. si recorta con el y  arriba.
		$imageWidth = $this->_img->getImageWidth();
		$imageHeight = $this->_img->getImageHeight();

		bcscale(5);
   		$distx = bcdiv($imageWidth, $width);
   		$disty = bcdiv($imageHeight, $height);

   		$div = ($distx < $disty) ? $distx : $disty;	// La menor distancia.
   		$twidth = ceil(bcdiv($imageWidth, $div));
		$theight = ceil(bcdiv($imageHeight, $div));

		$this->_img->thumbnailImage($twidth, $theight, true);

		$x = $this->_getPositionToCut(1, $twidth, $width);
		$y = $this->_getPositionToCut(1, $theight, $height);
		$this->_img->cropImage($width, $height, $x, $y);
	}

	private function _getPositionToCut($pos = 1, $size, $size2)
	{
		bcscale(5);
		switch($pos)
		{
			case 0:
				$x = 0;
				break;
			case 1:
				$x = floor(bcsub(bcdiv($size, 2), bcdiv($size2, 2)));
				break;
			case 2:
				$x = ($size / 2);
				break;
		}
		return $x;
	}

	private function _saveImage($url)
	{
		if(!is_dir($_SERVER["DOCUMENT_ROOT"].$this->_rootThumbnails)) mkdir($_SERVER["DOCUMENT_ROOT"].$this->_rootThumbnails, 0777, true);
		$this->_img->writeImage($_SERVER["DOCUMENT_ROOT"].$url);

	}

	private function _applyFilters()
	{
		foreach ($this->_filters as $filtro) {
			if (!is_array($filtro)) {
				$filtro = array($filtro, array());
			}

			$operation = array_shift($filtro);
			call_user_func_array(
                array($this->_img, $operation),
                $filtro
            );
		}
	}

	private function _convertUrl($url)
	{
		$arr = array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', '"' => '-', '.' => '_', 'ñ' => 'n', 'Ñ' => 'n');
		$str= str_replace(' ', '-', strtr(strtolower($url), $arr));
		return implode("/", str_replace('%', '-', array_map("rawurlencode", explode("/", $str))));
	}



}
