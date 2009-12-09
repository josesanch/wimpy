<?php

class thumb
{
    const NORMAL = 1;
    const CROP = 2;
}

class helpers_files_thumbnail
{
    private $_img;
    private $_file;
    private $_imgId;
    private $_rootThumbnails = "/cached";
    private $_output = "jpg";
    function __construct ($file)
    {
        $this->_file = $file;

        if($file->id) $this->_imgId = $file->id;
        else $this->_imgId = md5($file->phisical());

    }

    public function getUrl($width, $height, $operation = thumb::NORMAL)
    {

		$img = $this->_file->image ? $this->_file->image : new image($this->_file->phisical());
		
        $info = pathinfo($this->_file->nombre);
        switch ($operation) {
            case thumb::CROP:
            case "OUTABOX":
                $url = $this->_rootThumbnails."/".$this->_imgId."-{$width}x{$height}-crop-".$info["filename"].".".$this->_output;
                if(file_exists($_SERVER["DOCUMENT_ROOT"].$url)) return $url;

//                $this->_img = new Imagick($this->_file->phisical().'[0]');
//                $this->_img->cropThumbnailImage($width, $height);
            	$img = $img->thumbnailOutABox($width, $height, $ycenter, $xcenter);
  				$img->setQuality($this->_file->quality());
        		$img->save($url, $this->_output);  				
                break;

            case thumb::NORMAL:
            case "INABOX":
            case "THUMB":
            default:
                $url = $this->_rootThumbnails."/".$this->_imgId."-{$width}x{$height}-".$info["filename"].".".$this->_output;
                if(file_exists($_SERVER["DOCUMENT_ROOT"].$url)) return $url;

                $this->_img = new Imagick($this->_file->phisical().'[0]');
                $this->_img->thumbnailImage($width, $height, true);
                break;
        }
    	if(!is_dir($_SERVER["DOCUMENT_ROOT"].$this->_rootThumbnails)) mkdir($_SERVER["DOCUMENT_ROOT"].$this->_rootThumbnails, 0777, true);
        $this->_img->writeImage($_SERVER["DOCUMENT_ROOT"].$url);
        return $url;
    }

    public function setOutput($output)
    {
        $this->_output = $output;
    }

}
