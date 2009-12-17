<?

class helpers_files extends ActiveRecord
{
	protected $database_table = "files";
	protected $path = "/files";
	protected $output_type = "jpg";
	protected $local_file = null;
	protected $image = null;
	protected $cache_images = true;
	protected $quality = 95;
	protected $fields = array (
	    "id" => "int not_null primary_key auto_increment",
	    "nombre" => "string(255) not_null default=''",
	    "extension" => "varchar(255)",
	    "tipo" => "varchar(255) not_null",
	    "iditem" => "int",
	    "module" => "varchar(255)",
	    "field" => "varchar(35)",
	    "descripcion" => "text",
	    "orden" => "int",
	    "fecha" => "datetime"
    );


	public function __construct($url) {
    	require_once(dirname(__FILE__)."/files/thumbnail.php");
		parent::__construct();
		if(!is_numeric($url)) {
			$this->local_file = $url;
			$this->nombre = basename($url);
			$this->tipo = image_type_to_mime_type(exif_imagetype($this->local_file));
		}
	}

	public function url()
	{
//		return $this->path."/".$this->id.".".$this->extension;
        $info = pathinfo($this->nombre);
        return "/files/$this->id/".$info["filename"].".".$this->extension;
	}

	public function phisical($id = '')
	{
		if($id) $this->select($id);
		if($this->local_file) return $this->local_file;
	 	return $_SERVER["DOCUMENT_ROOT"].$this->path."/".$this->id.".".$this->extension;
	}

	public function getType() { return array_shift(explode('/', $this->tipo));	}
	public function getSubtype() { return array_pop(explode('/', $this->tipo));	}

	public function isVideo() { return $this->getType() == 'video' or $this->tipo == 'application/x-flash-video';  }

	public function isImage() { return $this->getType() == 'image'; }
	public function isTiff() { return ($this->getType() == 'image' && $this->getSubType() == 'tiff'); }

	public function isPDF() { return $this->tipo == 'application/pdf'; }

	public function isAudio() { return $this->getType() == 'audio'; }

	public function isCompressed() { return ($this->getType() == 'application') && ($this->getSubtype() == 'zip' || $this->getSubtype() == 'x-gzip'); }

	public function getTypeByExtension($id = null)
	{
		$tipos = array(
              "image" => array("gif", "jpg", "png", "bmp")
            , "audio" => array("mp3", "wav", "wma", "ogg")
            , "compress" => array("zip", "rar", "ace" )
            , "text" => array("doc", "txt", 'odt')
            , "pdf" => array("ps", "pdf")
            , "video" => array("avi", "mpg", "wmv", 'flv')
            , "spreadsheet" => array("xls")
            , "presentation" => array("ppt", "pps", 'odp')
            , "executable" => array("exe", "com")
        );

		if(isset($id)) $this->id($id);
		$ext = strtolower($this->extension);
		foreach($tipos as $key => $arr) {
			if(in_array($ext, $arr)) return $key;
		}
		return null;
	}


	public function delete($id = null) {
		if($id) $this->select($id);
		try {
			unlink($this->phisical());
		} catch(Exception $e) {
		    echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
		parent::delete();
	}

	public function size($id = '') {
		if($id) $this->select($id);
	 	return filesize($this->phisical());
	}

	public function saveUploadedFile($file, $id, $extension) {
		$dir = $_SERVER['DOCUMENT_ROOT'].$this->path;
       	if(!is_dir($dir)) mkdir($dir, 0755);
   	   	move_uploaded_file($file, $_SERVER['DOCUMENT_ROOT'].$this->path."/".$id.".".$extension);
	}


	private function generateCachedUrl($width, $height, $op, $xcenter, $ycenter) {

		$string = "$this->path $this->database_table ".$this->phisical()." $width $height $op $this->output_type $xcenter $ycenter";
		$file = md5($string);
	    return "/cached/imgs/$file";
	}

    private function isCached($url) {
		if(file_exists($_SERVER["DOCUMENT_ROOT"].$url.".jpg")) return $url.".jpg";
		if(file_exists($_SERVER["DOCUMENT_ROOT"].$url.".png")) return $url.".png";
		return false;
	}


	public function getThumbnail($width = null, $height = null, $ycenter = 1, $xcenter = 1, $op = "THUMB", $border = null, $img = null) {
		if(isset($border)) $this->setBorder($border);
    	$this->cached_image_url =  $this->generateCachedUrl($width, $height, $ycenter, $xcenter, $op);
    	$cached = $this->isCached($this->cached_image_url);
    	if(!$cached) {
   			$this->init();
			if(!$img) $img = $this->image;
			switch($op) {
				case "THUMB":
					if(isset($width) && isset($height)) $img = $img->thumbnail($width, $height);
					break;

				case "OUTABOX":
					$img = $img->thumbnailOutABox($width, $height, $ycenter, $xcenter);
					break;
				case "INABOX":
					$img = $img->thumbnailInABox($width, $height);
					break;
			}

     		$img = $this->putBorder($img);
			$img->setQuality($this->quality);
			if($this->cache_images) {
				$this->cacheImage($this->cached_image_url , $img);
				return $this->cached_image_url.".".$this->output_type;
			} else {
				return $img;
			}
		}
		return $cached;
	}


	function src($size = null, $operation = thumb::NORMAL, $options = array())
	{
		if(!$size) return $this->url();

		$size = explode("x", $size);

		if (($this->isImage() || $this->isPDF()) && !$this->isTiff()) {
            $thumb = new helpers_files_thumbnail($this);
            if(isset($options["outputFormat"])) $thumb->setOutput($options["outputFormat"]);
            try {
                $url = $thumb->getUrl($size[0], $size[1], $operation);
                return $url;
          	} catch(Exception $e) {
          	    if($this->isPDF()) return '/resources/admin/images/pdf.gif';
          	    return '';
			}
        }

    	$this->cached_image_url = $this->generateCachedUrl($size[0], $size[1], $ycenter, $xcenter, $operation);

		$cached = $this->isCached($this->cached_image_url);
    	if($cached)	return $cached;

        if($this->isVideo()) {
			if(class_exists('ffmpeg_movie')) {
				$movie = new ffmpeg_movie($this->phisical());
//				$frame = $movie->getFrame(1);
				$frame = $movie->getFrame(round($movie->getFrameCount() / 3));
				$img = $this->getThumbnail($size[0], $size[1], $ycenter, $xcenter, $operation, null, new image($frame->toGDImage()));
			} else {
				return '/resources/admin/images/video_small.gif';
			}

		} elseif($this->getType() == 'audio') {
			return '/resources/admin/images/music_small.gif';

		} else {
			if(class_exists('imagick')) {
				try {
					$image = new Imagick($this->phisical().'[0]');
				    $image->setImageFormat("png");
					$image->thumbnailImage($size[0], $size[1], true);
	    	    	if(!is_dir($_SERVER["DOCUMENT_ROOT"]."/cached/imgs/")) mkdir($_SERVER["DOCUMENT_ROOT"]."/cached/imgs/", 0744, true);
					$image->writeImage($_SERVER["DOCUMENT_ROOT"].$this->cached_image_url.".png");
					$image->clear();
					return $this->cached_image_url.".png";
				} catch(Exception $e) {
//					var_dump($e);
					return '/resources/admin/images/pdf.gif';
				}

			} else {
				if($this->isPDF()) {
					return '/resources/admin/images/pdf.gif';
				}
			}
		}
		return $img;
	}


    private function cacheImage($url, $img) {
    	if(!is_dir($_SERVER["DOCUMENT_ROOT"]."/cached/imgs/")) mkdir($_SERVER["DOCUMENT_ROOT"]."/cached/imgs/", 0777, true);
		$img->save($_SERVER["DOCUMENT_ROOT"].$url.".".$this->output_type, $this->output_type);
	}

	// Para descargar el archivo.
	public function download($disposition = "attachment")
	{
		header("Content-Type: $this->tipo");
		header("Content-Disposition: $disposition; filename=\"$this->nombre\"");
		header("Content-Transfer-Encoding: binary");
		//set file length so browser can calculate download time
		header("Content-length: " . $this->size());
		//read file
		echo file_get_contents($this->phisical());
	}

	// Copia una imagen en otra en la posición especificada
	function putImage($img, $x, $y, $alfa = null)
	{
		$this->init();
//		$this->image = $img;
		if(isset($alfa)) ImageAlphaBlending($this->image->img, true);
		imagecopy($this->image->img, $img->img, $x, $y, 0, 0, $img->getWidth(), $img->getHeight());
	}
	// Implementación del cache para el manipulado de imagenes

	public function init() {
		$this->image = $this->image ? $this->image : new image($this->phisical());
	}

	function setBorder($marca, $pos = "leftup", $marginx = 0, $marginy = 0)
	{
		$this->_border= $marca;
		$this->_borderFile = $marca->file;
		$this->_borderPos = $pos;

		$this->_borderX = $marginx;
		$this->_borderY = $marginy;
	}


	public function getDuration() {
		if ($this->isVideo()) {
			if(class_exists('ffmpeg_movie')) {
				$movie = new ffmpeg_movie($this->phisical());
				return floor($movie->getDuration());
			}
		}
	}


	private function putBorder($img)
	{
		if(!is_a($this->_border , "image") ) return $img;
		switch($this->_borderPos)
		{
			case "leftup":
				$x = 0 + $this->_borderX;
				$y = 0 + $this->_borderY;
				break;
			case "rightup":
				$x =  $img->getWidth() - $this->_border->getWidth() -  $this->_borderX;
				$y = 0 + $this->_borderY;
				break;
			case "leftdown":
				$x =  0 + $this->_borderX;
				$y = $img->getHeight() - $this->_border->getHeight() -  $this->_borderY;
				break;
			case "rightdown":
				$x = $img->getWidth() - $this->_border->getWidth() -  $this->_borderX;
				$y = $img->getHeight() - $this->_border->getHeight() -  $this->_borderY;
				break;
			case "center":
				$x = (($img->getWidth() - $this->_border->getWidth()) / 2) -  $this->_borderX;
				$y = (($img->getHeight()  - $this->_border->getHeight()) / 2) -  $this->_borderY;
				break;

			default:
				$x = 0; $y = 0;
		}
		$img->putImage($this->_border, $x, $y, true);

		return $img;
	}
	public function setOutput($output = 'jpg'){ $this->output_type = $output; }

    public function quality($quality = null)
    {
        if(!$quality) return $this->quality;
        $this->quality = $quality;

    }
}
?>
