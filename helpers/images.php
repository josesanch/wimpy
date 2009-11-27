<?

class helpers_images extends helpers_files {
	protected $database_table = "images";
	protected $path = "/imgs";

	protected $cached_image_url;


	public function setQuality($quality) { $this->quality = $quality; }


	public function url()
	{
		return $this->path."/".$this->id.".".$this->extension;
        //$info = pathinfo($this->nombre);
//        return "/images/$this->id/".$info["filename"].".".$this->extension;
	}

}
?>
