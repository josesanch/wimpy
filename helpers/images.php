<?

class helpers_images extends helpers_files {
	protected $database_table = "images";
	protected $path = "/imgs";

	protected $cached_image_url;
	protected $cache_images = true;
	protected $quality = 95;

	public function setQuality($quality) { $this->quality = $quality; }

}
?>
